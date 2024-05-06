<?php

namespace Garak\OrmCriteria;

use Doctrine\ORM\QueryBuilder;

final class Filterer
{
    /**
     * @param iterable<\Garak\OrmCriteria\AbstractCriterion> $criteria
     */
    public function __construct(protected readonly iterable $criteria)
    {
    }

    /**
     * @param class-string         $class
     * @param array<string, mixed> $filters
     */
    public function filter(
        string $class,
        array $filters,
        QueryBuilder $builder,
        ?string $alias = null,
        ?string $defaultSort = null,
        ?string $defaultDirection = null,
    ): void {
        if (null == $alias) {
            $alias = $builder->getRootAliases()[0];
        }

        foreach ($filters as $name => $value) {
            if (null === $value || '' === $value) {
                continue;
            }
            foreach ($this->criteria as $criterion) {
                if ($criterion->supports($class, $name)) {
                    $criterion->apply($builder, $value, $alias);
                }
            }
        }

        if (isset($filters['_sort']['field'])) {
            $builder->orderBy($alias.'.'.$filters['_sort']['field'], $filters['_sort']['direction']);
        } else {
            if (empty($defaultSort)) {
                $defaultSort = 'id';
            }
            $builder->orderBy($alias.'.'.$defaultSort, $defaultDirection ?? 'ASC');
        }
    }
}
