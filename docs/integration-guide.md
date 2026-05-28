# Integration Guide

[简体中文](./接入指南.zh-CN.md)

This guide explains how to integrate `hongxunpan/eloquent-query-dsl` into an Eloquent-based application from an open-source user perspective.

The package provides query construction only. Result parsing, pagination execution, response envelopes, authorization, tenancy, and business error handling remain application responsibilities.

---

## 1. Install

After the package is published to Packagist:

```bash
composer require hongxunpan/eloquent-query-dsl
```

Before a stable tag, evaluate with a VCS or path repository and pin an explicit commit or tag. Do not use floating `dev-main` in production.

---

## 2. Define query capabilities per resource

Create one definition close to the application resource or service that owns the list endpoint.

```php
use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefinition;

$definition = DslQueryDefinition::make('article')
    ->allowSearch(['title', 'summary'])
    ->allowKeywordSearch('keyword', ['title', 'summary'])
    ->allowFilter([
        'status' => 'string',
        'category_id' => 'integer',
    ])
    ->allowBetween(['published_at'])
    ->allowSort(['published_at', 'id'])
    ->defaultSort('id', 'desc')
    ->strict();
```

Guidelines:

- expose only fields that are safe and useful for this resource;
- use `strict()` for public APIs unless you are migrating a legacy protocol;
- keep authorization, tenancy, and business default scopes outside the definition;
- do not use raw SQL expressions as field names.

---

## 3. Pass request parameters as plain arrays

The default protocol expects a `query` wrapper and a `page` section:

```php
$params = [
    'query' => [
        'search' => ['keyword' => 'alumni'],
        'filter' => ['status' => 'published'],
        'between' => ['published_at' => ['2026-01-01', '2026-12-31']],
        'sort' => ['published_at' => 'desc'],
    ],
    'page' => ['page' => 1, 'limit' => 20],
];
```

If your framework request object is not an array, adapt it in application code before calling Query DSL.

---

## 4. Apply the DSL to a Builder

```php
use HongXunPan\EloquentQueryDsl\QueryDsl;

$result = QueryDsl::for(Article::query(), $definition)
    ->from($params)
    ->apply();

$builder = $result->builder();
$pagination = $result->pagination();
```

At this point search / filter / between / sort have been applied to the Builder. No SQL has to be executed by the package itself.

---

## 5. Execute pagination in your application

```php
$pagination = $result->pagination();

$total = (clone $result->builder())->count();
$items = $result->builder()
    ->forPage($pagination->page(), $pagination->limit())
    ->get();

return [
    'page' => [
        'page' => $pagination->page(),
        'limit' => $pagination->limit(),
        'total' => $total,
    ],
    'list' => $items,
];
```

This response shape is only an example. The package intentionally does not define it.

---

## 6. Keep existing public parameter names

Use `DslInputMap` when your API already has stable parameter names.

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
        'order_by' => ['published_at' => 'desc'],
        'page' => 1,
        'per_page' => 20,
    ], $inputMap)
    ->apply();
```

Use `flatInput()` for lightweight legacy APIs:

```php
$inputMap = DslInputMap::make()
    ->flatInput()
    ->search('keyword')
    ->filterPrefix('where_')
    ->sort('sort')
    ->page('page')
    ->limit('per_page');
```

---

## 7. Connect application validation through a normalizer

If your filter section needs trimming, enum conversion, required checks, or type coercion, implement `DslFilterNormalizer`.

```php
$result = QueryDsl::for($builder, $definition)
    ->from($params)
    ->filterNormalizer(new ProjectFilterNormalizer())
    ->apply();
```

The normalizer should translate your validation result into `DslNormalizedFilterSet`. It should not make Query DSL depend on your HTTP layer, response helper, or exception class.

---

## 8. Add relation query support explicitly

```php
$definition = DslQueryDefinition::make('article')
    ->relation('author', 'author')
    ->allowFilter(['author.name'])
    ->allowSearch(['title'])
    ->strict();
```

Relation query behavior is explicit to avoid hidden joins. Relation sort is not supported by core; implement a resource-specific adapter when join/group semantics are required.

---

## 9. Handle errors externally

Query DSL throws Query DSL exceptions for invalid definitions or invalid query input. Applications should catch them at their boundary and translate them into the project-specific response format.

Do not require the package to know about:

- HTTP status conventions;
- localized error envelopes;
- business exception classes;
- logging or tracing systems.

---

## 10. Recommended adoption path

1. Pick one high-value list endpoint with repeated search / filter / sort logic.
2. Add a `DslQueryDefinition` with only the currently allowed fields.
3. Keep the public parameter shape stable with `DslInputMap` if needed.
4. Add a `DslFilterNormalizer` only when the default value handling is not enough.
5. Execute pagination and response wrapping in your application.
6. Add regression tests around input, SQL, bindings, and actual result set.
7. Roll the same definition style out to other list endpoints.
