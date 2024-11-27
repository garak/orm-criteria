<?php

namespace Garak\OrmCriteria\Tests;

use Doctrine\ORM\QueryBuilder;
use Garak\OrmCriteria\AbstractCriterion;
use Garak\OrmCriteria\Filterer;
use PHPUnit\Framework\TestCase;

final class FiltererTest extends TestCase
{
    public function testFilter(): void
    {
        $criteria = self::criteriaProvider();
        $filterer = new Filterer($criteria);

        $builder = $this->createMock(QueryBuilder::class);
        $builder->expects($this->exactly(2))
            ->method('andWhere')
            ->withConsecutive(
                ['a.name = :name'],
                ['a.age = :age'],
            );
        $builder->expects($this->once())
            ->method('getRootAliases')
            ->willReturn(['a']);
        $builder->expects($this->once())
            ->method('orderBy')
            ->with('a.id', 'ASC');

        $filterer->filter(
            'App\Entity\User',
            ['name' => 'John', 'age' => 30],
            $builder,
        );
    }

    public static function criteriaProvider(): array
    {
        return [
            new class() extends AbstractCriterion {
                public function supports(string $entityName, string $field): bool
                {
                    return 'name' === $field;
                }

                public function apply(QueryBuilder $builder, mixed $value, string $alias): void
                {
                    $builder->andWhere($alias.'.name = :name')->setParameter('name', $value);
                }
            },
            new class() extends AbstractCriterion {
                public function supports(string $entityName, string $field): bool
                {
                    return 'age' === $field;
                }

                public function apply(QueryBuilder $builder, mixed $value, string $alias): void
                {
                    $builder->andWhere($alias.'.age = :age')->setParameter('age', $value);
                }
            },
        ];
    }
}
