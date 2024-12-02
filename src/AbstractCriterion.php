<?php

namespace Garak\OrmCriteria;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;

abstract class AbstractCriterion
{
    // available QueryBuilder comparison methods
    protected const string EQ = Comparison::EQ;
    protected const string CONTAINS = Comparison::CONTAINS;
    protected const string LIKE = Comparison::CONTAINS; // backward compatible alias
    protected const string GT = Comparison::GT;
    protected const string GTE = Comparison::GTE;
    protected const string LT = Comparison::LT;
    protected const string LTE = Comparison::LTE;
    protected const string NEQ = Comparison::NEQ;
    protected const string IN = Comparison::IN;
    protected const string NIN = Comparison::NIN;
    protected const string STARTS_WITH = Comparison::STARTS_WITH;
    protected const string ENDS_WITH = Comparison::ENDS_WITH;
    protected const string MEMBER_OF = Comparison::MEMBER_OF;

    // mandatory to redefine this property in child class, to match the entity class
    protected static string $entityName = '';
    // mandatory to redefine this property in child class, to match the filtered field
    protected static string $field = '';
    // if empty, it will be composed by alias, a dot, and $field (see "getDbField" method)
    protected static string $dbField = '';
    protected static string $compare = self::EQ;

    public function apply(QueryBuilder $builder, mixed $value, string $alias): void
    {
        if (empty(static::$entityName) || empty(static::$field)) {
            throw new \LogicException('Mandatory property not defined in '.static::class);
        }

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
            ->setParameter($param, self::fixValue($value))
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

    private static function fixValue(mixed $value): mixed
    {
        return match (static::$compare) {
            self::CONTAINS => '%'.$value.'%',
            self::STARTS_WITH => $value.'%',
            self::ENDS_WITH => '%'.$value,
            default => $value,
        };
    }
}
