<?php


return [
    'frontend' => [
        'typo3/georgringer-audit/data-collection' => [
            'target' => \GeorgRinger\Audit\Middleware\DataCollectResponseHeader::class,
            'after' => [
                'typo3/cms-frontend/content-length-headers',
            ],
        ],

    ],
];
