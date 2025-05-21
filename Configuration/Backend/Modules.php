<?php

/**
 * Definitions for modules provided by EXT:backend
 */
return [
    'web_audit' => [
        'parent' => 'web',
        'position' => ['before' => '*'],
        'access' => 'user',
        'path' => '/module/web/audit',
        'iconIdentifier' => 'module-sites',
        'labels' => 'LLL:EXT:audit/Resources/Private/Language/locallang_module.xlf',
        'routes' => [
            '_default' => [
                'target' => \GeorgRinger\Audit\Controller\ModuleController::class . '::overviewAction',
            ],
            'show' => [
                'target' => \GeorgRinger\Audit\Controller\ModuleController::class . '::showAction',
                'methods' => ['GET'],
            ],
//            'save' => [
//                'target' => SiteConfigurationController::class . '::saveAction',
//                'methods' => ['POST'],
//            ],
//            'delete' => [
//                'target' => SiteConfigurationController::class . '::deleteAction',
//                'methods' => ['POST'],
//            ],
        ],
    ],
];
