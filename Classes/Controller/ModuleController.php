<?php
declare(strict_types=1);

namespace GeorgRinger\Audit\Controller;

use GeorgRinger\Audit\Registry\AuditRegistry;
use GeorgRinger\Audit\Service\SubResponseBuilder;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Site\SiteFinder;

#[AsController]
class ModuleController
{

    protected int $statusCode;
    protected int $pageUid = 0;

    public function __construct(
        protected readonly SiteFinder $siteFinder,
        protected readonly IconFactory $iconFactory,
        protected readonly UriBuilder $uriBuilder,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly AuditRegistry $auditRegistry,
        private readonly SubResponseBuilder $subResponseBuilder,
    )
    {


    }

    public function overviewAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);
        $pageId = (int) ($request->getQueryParams()['id'] ?? 0);

        if ($pageId > 0) {
            $content = $this->subResponseBuilder->getFeRequest($pageId, $request);
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
        $content = $this->subResponseBuilder->getFeRequest($pageId, $request);
        $provider->setSubResponseResult($content);
        $provider->init();

        $view->assignMultiple([
            'provider' => $provider,
            'pageId' => $pageId,
        ]);

        return $view->renderResponse('Audit/Show');
    }

}
