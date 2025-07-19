<?php

namespace Garak\OrmCriteria;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

final class CriteriaDataCollector extends DataCollector
{
    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $this->data['criteria'] = Filterer::$appliedCriteria;
    }

    public function getName(): string
    {
        return 'garak.orm_criteria.data_collector';
    }

    public function reset(): void
    {
        $this->data = [];
    }

    public function getCriteria(): array
    {
        return $this->data['criteria'] ?? [];
    }
}
