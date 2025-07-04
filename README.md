# ORM Doctrine Criteria

This library is meant to ease the filtering with Doctrine repositories.
It's an ideal companion for [PUGX FilterBundle][1].

## Setup

Run `composer require garak/orm-criteria`. No configuration is required.

## Usage

The basic idea of this library is applying the Open/Closed principle
(the "O" in SOLID), to avoid being forced to change your code every
time you need to apply a new filter.

> [!TIP]
> for the concept of "filter", refer to the FilterBundle mentioned above

You have two classes available: `AbstractCriterion` and `Filterer`.
Inject the latter into your repositories, then you can start creating
your criteria.

Let's suppose you have a `UserRepository`, and you want to filter your users by
the following fields: "username", "enabled" (yes/no), and "country".

Let's create the following classes:


```php
<?php

namespace YourNamespace\Repository\Criteria\User;

use Doctrine\ORM\QueryBuilder;
use Garak\OrmCriteria\AbstractCriterion;
use YourDomain\Entity\User;

final class CountryUserCriterion extends AbstractCriterion
{
    protected static string $className = User::class;
    protected static string $field = 'country';
}

```

```php
<?php

namespace YourNamespace\Repository\Criteria\User;

use Doctrine\ORM\QueryBuilder;
use Garak\OrmCriteria\AbstractCriterion;
use YourDomain\Entity\User;

final class EnabledUserCriterion extends AbstractCriterion
{
    protected static string $className = User::class;
    protected static string $field = 'enabled';
}

```

```php
<?php

namespace YourNamespace\Repository\Criteria\User;

use Doctrine\ORM\QueryBuilder;
use Garak\OrmCriteria\AbstractCriterion;
use YourDomain\Entity\User;

final class UsernameUserCriterion extends AbstractCriterion
{
    protected static string $className = User::class;
    protected static string $field = 'username';
    protected static string $compare = self::LIKE;
}

```

Then configure the services:

```yaml

services:
    _defaults:
        autowire: true
        autoconfigure: true

    # feel fre to use the tag name your prefer
    _instanceof:
        Garak\OrmCriteria\AbstractCriterion:
            tags: ['garak.criterion']


    # if you changed the tag name above, be sure that you use the same name here
    Garak\OrmCriteria\Filterer:
        bind:
            $criteria: !tagged_iterator garak.criterion

```

Now, let's use it in your repository:

```php
<?php

namespace YourNamespace\Repository\UserRepository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Garak\OrmCriteria\Filterer;
use YourDomain\Entity\User;

readonly class UserRepository
{
    public function __construct(
        private EntityManagerInterface $manager,
        private Filterer $filterer,
    ) {
    };

    public function listUsers(array $filters): array
    {
        $builder = $this->manager
            ->createQueryBuilder()
            ->from(User::class, 'u')
            ->select('u')
        ;

        $this->filterer->filter(User::class, $filters);

        return $builder->getQuery->execute();
    }
}

```

## Advanced Usage

You can pass your sorting options along wit the filters, like in this example:

`$filters['_sort']['field'] = 'username';`

`$filters['_sort']['direction'] = 'DESC';`

If your criterion needs something more sophisticated than the basic operators,
you can define a `filter` method and add your logic. Example:

```php
<?php

namespace YourNamespace\Repository\Criteria\User;

use Doctrine\ORM\QueryBuilder;
use Garak\OrmCriteria\AbstractCriterion;
use YourDomain\Entity\User;

final class UnchartedUserCriterion extends AbstractCriterion
{
    protected static string $className = User::class;
    protected static string $field = 'map';

    protected static function filter(QueryBuilder $builder, string $value, string $alias): void
    {   
        $builder
            ->andWhere($alias.'.coordinates.latitudine is null')
            ->andWhere($alias.'.coordinated.longitudine is null')
        ;
    } 
}

```

You can use different kinds of comparison operators, see the constants defined in 
the `Filterer` class.

By default, the library expects to find a database name matching the name of the
filtered field: for example, in the code above, the filterd field `username` expects
a db field `u.username`.
If it's not the case, you can defined a static property `$dbField` in your criterion class.

[1]: https://github.com/PUGX/filter-bundle
