<?php

namespace Garak\OrmCriteria\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Garak\OrmCriteria\AbstractCriterion;
use Garak\OrmCriteria\Filterer;
use Garak\OrmCriteria\Tests\Fixture\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FiltererTest extends TestCase
{
    #[Test]
    public function itAppliesEverySupportedCriterion(): void
    {
        $builder = $this->builder();

        $this->filterer()->filter(User::class, ['name' => 'John', 'age' => 30, 'skipped' => ''], $builder);

        self::assertSame('a.name = :name AND a.age = :age', (string) $builder->getDQLPart('where'));
        self::assertSame(['a.id ASC'], $this->orderBy($builder));
    }

    #[Test]
    #[DataProvider('defaultDirections')]
    public function itSortsByTheDefaultField(\SortDirection|string|null $direction, string $expected): void
    {
        $builder = $this->builder();

        $this->filterer()->filter(User::class, [], $builder, defaultSort: 'name', defaultDirection: $direction);

        self::assertSame(['a.name '.$expected], $this->orderBy($builder));
    }

    /**
     * The suite fails on any deprecation, so these passing is also what proves that neither
     * form makes Doctrine complain.
     *
     * @return iterable<string, array{\SortDirection|string|null, string}>
     */
    public static function defaultDirections(): iterable
    {
        yield 'none' => [null, 'ASC'];
        yield 'enum ascending' => [\SortDirection::Ascending, 'ASC'];
        yield 'enum descending' => [\SortDirection::Descending, 'DESC'];
        yield 'string' => ['DESC', 'DESC'];
        yield 'lower case string' => ['desc', 'DESC'];
        yield 'unknown string' => ['sideways', 'ASC'];
    }

    #[Test]
    #[DataProvider('requestedDirections')]
    public function itSortsByTheRequestedField(mixed $direction, string $expected): void
    {
        $builder = $this->builder();

        $this->filterer()->filter(User::class, ['_sort' => ['field' => 'age', 'direction' => $direction]], $builder);

        self::assertSame(['a.age '.$expected], $this->orderBy($builder));
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function requestedDirections(): iterable
    {
        yield 'enum' => [\SortDirection::Descending, 'DESC'];
        yield 'descending' => ['DESC', 'DESC'];
        yield 'ascending' => ['asc', 'ASC'];
        yield 'missing' => [null, 'ASC'];
        // A request can carry anything: it must not reach the DQL.
        yield 'garbage' => ['DESC; DROP TABLE user', 'ASC'];
    }

    #[Test]
    public function anExistingOrderIsKept(): void
    {
        $builder = $this->builder()->orderBy('a.created', \SortDirection::Descending);

        $this->filterer()->filter(User::class, [], $builder, defaultSort: 'name');

        self::assertSame(['a.created DESC'], $this->orderBy($builder));
    }

    private function builder(): QueryBuilder
    {
        return new QueryBuilder($this->createStub(EntityManagerInterface::class))
            ->select('a')
            ->from(User::class, 'a')
        ;
    }

    /** @return list<string> */
    private function orderBy(QueryBuilder $builder): array
    {
        return \array_values(\array_map(\strval(...), $builder->getDQLPart('orderBy')));
    }

    private function filterer(): Filterer
    {
        return new Filterer([
            new class extends AbstractCriterion {
                public function supports(string $entityName, string $field): bool
                {
                    return 'name' === $field;
                }

                public function apply(QueryBuilder $builder, mixed $value, string $alias): void
                {
                    $builder->andWhere($alias.'.name = :name')->setParameter('name', $value);
                }
            },
            new class extends AbstractCriterion {
                public function supports(string $entityName, string $field): bool
                {
                    return 'age' === $field;
                }

                public function apply(QueryBuilder $builder, mixed $value, string $alias): void
                {
                    $builder->andWhere($alias.'.age = :age')->setParameter('age', $value);
                }
            },
        ]);
    }
}
