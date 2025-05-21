<?php
declare(strict_types=1);

namespace GeorgRinger\Audit\Controller;

use GeorgRinger\Audit\Dto\SubResponse;
use GeorgRinger\Audit\FrontendRequestBuilder;
use GeorgRinger\Audit\Registry\AuditRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\Request;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Set\SetRegistry;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Frontend\Http\Application;

#[AsController]
class ModuleController
{

    protected int $statusCode;
    protected array $errorHandlerConfiguration;
    protected int $pageUid = 0;
    protected Application $application;
    protected ResponseFactoryInterface $responseFactory;
    protected LinkService $link;
    protected RequestFactoryInterface $requestFactory;
    protected GuzzleClientFactory $guzzleClientFactory;

    public function __construct(
        protected readonly SiteFinder $siteFinder,
        protected readonly IconFactory $iconFactory,
        protected readonly UriBuilder $uriBuilder,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly PageRenderer $pageRenderer,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly AuditRegistry $auditRegistry,
    )
    {

        $container = GeneralUtility::getContainer();
        $this->application = $container->get(Application::class);
        $this->responseFactory = $container->get(ResponseFactoryInterface::class);
        $this->link = $container->get(LinkService::class);
        $this->requestFactory = $container->get(RequestFactoryInterface::class);

    }

    public function overviewAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);
        $pageId = (int) ($request->getQueryParams()['id'] ?? 0);

        if ($pageId > 0) {

            $content = $this->getFeRequest($pageId, $request);
//DebuggerUtility::var_dump(array_keys($GLOBALS));

//        $this->configureOverViewDocHeader($view, $request->getAttribute('normalizedParams')->getRequestUri());
//        $view->setTitle(
//            $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_module.xlf:mlang_tabs_tab')
//        );

            $providers = [];
            foreach ($this->auditRegistry->getProviders() as $provider) {
                $provider->setSubResponseResult($content);
                $provider->init();
                $providers[] = $provider;
            }

            $view->assignMultiple([
                'providers' => $providers,
                'pageId' => $pageId,
//            'unassignedSites' => $unassignedSites,
//            'duplicatedRootPages' => $duplicatedRootPages,
//            'duplicatedEntryPoints' => $this->getDuplicatedEntryPoints($allSites, $pages),
//            'invalidSets' => $this->setRegistry->getInvalidSets(),
            ]);
        }


        return $view->renderResponse('Audit/Overview');
    }

    public function showAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);
        $identifier = $request->getQueryParams()['provider'] ?? '';

        $provider = $this->auditRegistry->getProvider($identifier);
        if (!$provider) {
            return $view->renderResponse('Audit/Show');
        }

        $pageId = (int) ($request->getQueryParams()['id'] ?? 0);
        $content = $this->getFeRequest($pageId, $request);
        $provider->setSubResponseResult($content);
        $provider->init();

        $view->assignMultiple([
            'provider' => $provider,
            'pageId' => $pageId,
        ]);

        return $view->renderResponse('Audit/Show');
    }


    /**
     * Sends an in-process subrequest.
     *
     * The $pageId is used to ensure the correct site is accessed.
     */
    protected function sendSubRequest(ServerRequestInterface $request, int $pageId, ServerRequestInterface $originalRequest): ResponseInterface
    {
        $site = $request->getAttribute('site');
        if (!$site instanceof Site) {
            $site = $this->siteFinder->getSiteByPageId($pageId);
            $request = $request->withAttribute('site', $site);
        }

        $request = $request->withAttribute('subRequestForAudit', true);
        $request = $request->withAttribute('originalRequest', $originalRequest);

        return $this->application->handle($request);
    }


    /**
     * Stash and restore portions of the global environment around a subrequest callable.
     */
    protected function stashEnvironment(callable $fetcher): ResponseInterface
    {
        $parkedTsfe = $GLOBALS['TSFE'] ?? null;
        $parkedRequest = $GLOBALS['TYPO3_REQUEST'] ?? null;
        $parkedBeUser = $GLOBALS['BE_USER'] ?? null;
        $parkedPageRender = $this->pageRenderer->getState();
        $GLOBALS['TSFE'] = null;
        $GLOBALS['BE_USER'] = null;
        $GLOBALS['TYPO3_REQUEST'] = null;


        $result = $fetcher();
        $this->pageRenderer->updateState($parkedPageRender);
        $GLOBALS['TSFE'] = $parkedTsfe;
        $GLOBALS['TYPO3_REQUEST'] = $parkedRequest;
        $GLOBALS['BE_USER'] = $parkedBeUser;
        return $result;
    }

    protected function getFeRequest(int $pageId, $request): SubResponse
    {
//        $resolvedUrl = $this->resolveUrl($request, $urlParams);


        // Build Url
        $resolvedUrl = $this->siteFinder->getSiteByPageId($pageId)->getRouter()->generateUri(
            $pageId,
            ['_language' => 0]
        );

        /**
         *
         * $builder = GeneralUtility::makeInstance(FrontendRequestBuilder::class );
         * $response = $builder->buildRequestForPage($resolvedUrl, null, null);
         *
         *
         * $body = $response->getBody();
         * $body->rewind();
         * $content = $response->getBody()->getContents();
         *
         * $subResponseData = new SubResponse($content, $response->getHeaders());
         * return $subResponseData;
         */

        // Create a sub-request and do not take any special query parameters into account
        $newR = $request;

        $pageArguments = new PageArguments($pageId, '0', []);


        $subRequest = $newR->withQueryParams([])->withUri($resolvedUrl)->withMethod('GET');
        $subRequest = $subRequest->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $subRequest = $subRequest->withAttribute('site', $this->siteFinder->getSiteByPageId($pageId));
        $subRequest = $subRequest->withAttribute('language', $this->siteFinder->getSiteByPageId($pageId)->getDefaultLanguage());
        $subRequest = $subRequest->withAttribute('routing', $pageArguments);
        $subRequest = $subRequest->withoutAttribute('module');
        $subRequest = $subRequest->withoutAttribute('moduleData');
//        $serverParams = [
//            'HTTP_HOST' => 't3-master.ddev.site'
//        ];
//        $subRequest = $subRequest->withAttribute('normalizedParams', NormalizedParams::createFromServerParams($serverParams));

        $subResponse = $this->stashEnvironment(fn(): ResponseInterface => $this->sendSubRequest($subRequest, $pageId, $request));
//       DebuggerUtility::var_dump($subRequest->getAttributes());die;

        $body = $subResponse->getBody();
        $body->rewind();
        $content = $subResponse->getBody()->getContents();

        $subResponseData = new SubResponse($content, $subResponse->getHeaders());
        return $subResponseData;
    }


}
