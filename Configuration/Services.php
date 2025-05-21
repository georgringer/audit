<?php

declare(strict_types=1);

namespace TYPO3\CMS\Backend\RecordList;

use GeorgRinger\Audit\Audit\ReportInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Reports\StatusProviderInterface;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $containerBuilder->registerForAutoconfiguration(ReportInterface::class)->addTag('audit.audit');
};
