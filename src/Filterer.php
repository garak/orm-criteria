<?php

namespace Garak\OrmCriteria;

use Doctrine\ORM\QueryBuilder;

final class Filterer
{
    public static $appliedCriteria = [];

    /**
     * @param iterable<AbstractCriterion> $criteria
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
        \SortDirection|string|null $defaultDirection = null,
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
                    self::$appliedCriteria[$name] = [$criterion, $value];
                }
            }
        }

        if (isset($filters['_sort']['field'])) {
            $sortField = str_contains($filters['_sort']['field'], '.') ? $filters['_sort']['field'] : $alias.'.'.$filters['_sort']['field'];
            $builder->orderBy($sortField, self::direction($filters['_sort']['direction'] ?? null));
        } elseif ([] === $builder->getDQLPart('orderBy')) {
            if (empty($defaultSort)) {
                $defaultSort = 'id';
            }
            $builder->orderBy($alias.'.'.$defaultSort, self::direction($defaultDirection));
        }
    }

    /**
     * Doctrine 3.7 deprecates string directions in the query builder, so Doctrine only ever
     * gets the enum. Strings stay welcome here, as in Doctrine's own findBy(): a sort usually
     * comes from a request, and the enum has no backing value to parse it from. Anything that
     * is not "DESC" (case-insensitive) sorts ascending, so no arbitrary input reaches the DQL.
     */
    private static function direction(mixed $direction): \SortDirection
    {
        if ($direction instanceof \SortDirection) {
            return $direction;
        }

        return \is_string($direction) && 'DESC' === \strtoupper($direction)
            ? \SortDirection::Descending
            : \SortDirection::Ascending;
    }
}
