<?php

namespace Garak\OrmCriteria;

use Doctrine\ORM\QueryBuilder;

abstract class AbstractCriterion
{
    // available QueryBuilder comparison methods
    protected const string EQ = 'eq';
    protected const string LIKE = 'like';
    protected const string GT = 'gt';
    protected const string GTE = 'gte';
    protected const string LT = 'lt';
    protected const string LTE = 'lte';

    // mandatory to redefine this property in child class, to match the entity class
    protected static string $entityName = '';
    // mandatory to redefine this property in child class, to match the filtered field
    protected static string $field = '';
    // if empty, it will be composed by alias, a dot, and $field (see "getDbField" method)
    protected static string $dbField = '';
    protected static string $compare = self::EQ;

    public function apply(QueryBuilder $builder, mixed $value, string $alias): void
    {
        // if you define a static function named "filter", it will be used instead of comparison
        if (\method_exists(static::class, 'filter')) {
            static::filter($builder, $value, $alias);

            return;
        }

        $method = static::$compare;
        $param = 'p_'.static::$field;
        $dbField = self::getDbField($alias);
        $builder
            ->andWhere($builder->expr()->$method($dbField, ':'.$param))
            ->setParameter($param, self::LIKE === static::$compare ? '%'.$value.'%' : $value)
        ;
    }

    /**
     * @param class-string $entityName
     */
    public function supports(string $entityName, string $field): bool
    {
        return static::$entityName === $entityName && static::$field === $field;
    }

    private static function getDbField(string $alias): string
    {
        if ('' !== static::$dbField) {
            return static::$dbField;
        }

        return $alias.'.'.static::$field;
    }
}
