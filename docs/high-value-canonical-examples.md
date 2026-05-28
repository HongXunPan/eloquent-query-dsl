# High-value Canonical Examples

[简体中文](./高价值%20canonical%20示例.zh-CN.md)

This document collects high-value usage patterns for `hongxunpan/eloquent-query-dsl`. README intentionally keeps only the minimal example.

---

## 1. Standard list query

```php
use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefaultSort;
use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefinition;
use HongXunPan\EloquentQueryDsl\QueryDsl;

$definition = DslQueryDefinition::make('article')
    ->allowSearch(['title', 'summary'])
    ->allowFilter(['status', 'category_id'])
    ->allowBetween(['published_at'])
    ->allowSort(['published_at', 'sort_order', 'id'])
    ->defaultSortMany(
        DslQueryDefaultSort::desc('sort_order', 'article'),
        DslQueryDefaultSort::desc('id', 'article')
    )
    ->strict();

$result = QueryDsl::for(Article::query(), $definition)
    ->from($params)
    ->apply();

$builder = $result->builder();
$pagination = $result->pagination();
```

Use this for standard admin or API list endpoints where field allowlists and sort rules must be explicit while response wrapping remains application-owned.

---

## 2. Single keyword across multiple fields

```php
$definition = DslQueryDefinition::make('article')
    ->allowKeywordSearch('keyword', ['title', 'summary'])
    ->strict();

$result = QueryDsl::for(Article::query(), $definition)
    ->from([
        'query' => [
            'search' => ['keyword' => 'alumni'],
        ],
    ])
    ->apply();
```

Semantics:

- `keyword` is the input field;
- `title` and `summary` are target fields;
- target fields are OR-grouped;
- relation keyword search should be implemented through an explicit derived search handler.

---

## 3. Application filter normalizer

```php
use HongXunPan\EloquentQueryDsl\Filter\Contract\DslFilterNormalizer;
use HongXunPan\EloquentQueryDsl\Filter\Value\DslNormalizedFilterSet;

final class ProjectFilterNormalizer implements DslFilterNormalizer
{
    public function normalize(array $payload, array $rules, array $options = []): DslNormalizedFilterSet
    {
        // Connect your validator / normalizer here and return neutral facts.
        // Do not return application response envelopes from shared core.
    }
}

$definition = DslQueryDefinition::make('article')
    ->allowFilter([
        'status' => 'required|string|trim',
        'category_id' => 'nonNegativeInt',
    ])
    ->strict();

$result = QueryDsl::for(Article::query(), $definition)
    ->from($params)
    ->filterNormalizer(new ProjectFilterNormalizer())
    ->apply();
```

Boundary:

- shared core only depends on `DslFilterNormalizer`;
- application validator bridges, localized errors, and exception types stay in adapters;
- normalized empty values do not generate ordinary `where` clauses.

---

## 4. Custom external parameter names

```php
use HongXunPan\EloquentQueryDsl\Input\DslInputMap;

$inputMap = DslInputMap::make()
    ->filter('where')
    ->sort('order_by')
    ->page('page')
    ->limit('per_page')
    ->arrayInput();

$result = QueryDsl::for($builder, $definition)
    ->from([
        'where' => ['status' => 'published'],
        'order_by' => [
            ['field' => 'published_at', 'order' => 'desc'],
        ],
        'page' => 1,
        'per_page' => 20,
    ], $inputMap)
    ->apply();
```

Use this when your public API already has stable parameter names and you only need key mapping.

---

## 5. Flat input

```php
$inputMap = DslInputMap::make()
    ->flatInput()
    ->search('keyword')
    ->filterPrefix('where_')
    ->sort('sort')
    ->page('page')
    ->limit('per_page');

$params = [
    'keyword' => 'alumni',
    'where_status' => 'published',
    'sort' => [
        ['field' => 'published_at', 'order' => 'desc'],
    ],
    'page' => 1,
    'per_page' => 20,
];

$result = QueryDsl::for($builder, $definition)
    ->from($params, $inputMap)
    ->apply();
```

Use this for legacy or lightweight APIs where changing the caller-facing protocol is not desirable.

---

## 6. Pagination facts policy

```php
use HongXunPan\EloquentQueryDsl\Page\DslPaginationPolicy;

$policy = DslPaginationPolicy::default()
    ->withMaxLimit(50)
    ->withMaxExportLimit(500);

$result = QueryDsl::for($builder, $definition)
    ->from($params)
    ->paginationPolicy($policy)
    ->apply();

$pagination = $result->pagination();
```

Semantics:

- the package only returns pagination facts;
- `limit` and `export_limit` are capped by policy;
- application code executes `paginate()`, `limit()`, exports, and response wrapping.

---

## 7. Relation filter

```php
$definition = DslQueryDefinition::make('article')
    ->relation('comments', 'comments')
    ->allowFilter(['comments.status'])
    ->strict();

$result = QueryDsl::for(Article::query(), $definition)
    ->from([
        'query' => [
            'filter' => [
                'comments.status' => 'visible',
            ],
        ],
    ])
    ->apply();
```

Semantics:

- relation fields require declared relation mapping;
- filters are applied through relation scopes;
- relation sort is not supported by core to avoid implicit join / group semantics.

---

## 8. Suggested integration checklist

1. Define `DslQueryDefinition` in the resource service or adapter.
2. Expose only the search / filter / sort fields that are actually allowed.
3. Use a project adapter implementing `DslFilterNormalizer` when filters require validation or normalization.
4. Use `QueryDsl::for(...)->from(...)->apply()` to get Builder and neutral facts.
5. Execute pagination, exception translation, and response wrapping in application code.
6. Add regression tests around input, SQL, bindings, and result set.
