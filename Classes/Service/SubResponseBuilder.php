<?php
declare(strict_types=1);

namespace GeorgRinger\Audit\Service;

use GeorgRinger\Audit\Dto\SubResponse;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Http\Application;

class SubResponseBuilder {

    protected Application $application;
    protected ResponseFactoryInterface $responseFactory;
    protected LinkService $link;
    protected RequestFactoryInterface $requestFactory;
    protected GuzzleClientFactory $guzzleClientFactory;

    public function __construct(
        protected readonly SiteFinder $siteFinder,
        private readonly PageRenderer $pageRenderer,
    )
    {
        $container = GeneralUtility::getContainer();
        $this->application = $container->get(Application::class);
        $this->responseFactory = $container->get(ResponseFactoryInterface::class);
        $this->link = $container->get(LinkService::class);
        $this->requestFactory = $container->get(RequestFactoryInterface::class);

    }


    public function getFeRequest(int $pageId, $request): SubResponse
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

}