<?php

use Garak\OrmCriteria\CriteriaDataCollector;
use Garak\OrmCriteria\Filterer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return function (ContainerConfigurator $container) {
    $services = $container->services();

    $services
        ->set(CriteriaDataCollector::class)
        ->args([service(Filterer::class)])
        ->tag('data_collector', [
            'template' => '@GarakOrmCriteria/Collector/criteria_collector.html.twig',
            'id' => 'garak.orm_criteria.data_collector',
        ])
    ;
};
