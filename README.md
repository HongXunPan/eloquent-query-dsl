# Eloquent Query DSL

[简体中文文档](./README.zh-CN.md)

`hongxunpan/eloquent-query-dsl` is an adapter-friendly Eloquent / Builder query DSL core for list endpoints. It turns recurring list-query concerns—`search`, `filter`, `between`, `sort`, and pagination input—into explicit, testable, reusable query definitions.

It does **not** own HTTP requests, response envelopes, business exceptions, pagination responses, authorization, tenant rules, or resource-specific field policies. Those concerns belong in your application adapter or service layer.

## Current Status

This repository is in **pre-1.0** development.

Already in place:

- Composer package and namespace `HongXunPan\EloquentQueryDsl\`;
- recommended entrypoint: `QueryDsl` / `QueryDslResult`;
- query capability declaration model: `DslQueryDefinition`;
- default input parser, custom parameter maps, flat input, and fully custom parser contracts;
- `search / filter / between / sort` readers and builder appliers;
- neutral filter normalizer contract and filter-value facts;
- safe identifier validation for fields, entity aliases, and each segment in relation paths;
- pagination facts and pagination policy limits;
- PHPUnit, legacy smoke tests, PHPStan level 8, PHP-CS-Fixer, Composer audit, and GitHub Actions matrix;
- release checklist and `0.1.0` pre-release changelog section.

Still required before public release:

- final commit and remote CI observation;
- `0.1.0` tag and GitHub Release;
- Packagist synchronization;
- application-side integration against a pinned tag and application smoke tests.

## Documentation

- [CHANGELOG](./CHANGELOG.md)
- [Contribution Guide](./CONTRIBUTING.md)
- [Security Policy](./SECURITY.md)
- [Public Contracts and Stability](./docs/public-contracts.md)
- [API Reference and Extension Points](./docs/api-reference.md)
- [Integration Guide](./docs/integration-guide.md)
- [Query Capability Matrix](./docs/query-capability-matrix.md)
- [High-value Canonical Examples](./docs/high-value-canonical-examples.md)
- [Release Checklist (Chinese)](./docs/发布检查清单.zh-CN.md)
- [MIT License](./LICENSE)

If you are evaluating whether a list-query abstraction belongs in a shared Composer package, start with the selection summary and boundaries below. If you are ready to integrate the package, read the integration guide and canonical examples.

## Selection Summary

This package is not an API framework, a repository layer, or a response-format library. It is a low-business-coupling query protocol core for Eloquent list queries.

Typical reasons to choose it:

- you repeatedly implement keyword search, field filters, range filters, sorting, and pagination input across list endpoints;
- you want a declarative definition to own allowed fields, sort directions, required filters, default sorts, and strict mode;
- you want shared core logic to return neutral query facts while application code keeps responses, exceptions, authorization, and pagination execution;
- you need one query protocol and regression suite across multiple Eloquent-based projects;
- you want relation search/filter, derived filters, keyword search, and default sorting semantics to be explicit and testable.

Typical reasons not to choose it:

- you only have one temporary list endpoint and no reuse or maintenance pressure;
- you expect the package to return `{ page, list }`, HTTP responses, or business error codes;
- you want to put authorization, tenant isolation, resource field policies, or business defaults into shared core;
- you need DTO/projection/presentation restructuring. Use a projection layer or application presenter instead.

## Core vs Application Responsibilities

Shared core owns:

- parsing external input into `DslQueryInput / DslPageInput`;
- query capability declarations;
- applying `search / filter / between / sort` to an Eloquent Builder;
- neutral filter values and pagination facts;
- extension contracts such as `DslInputParser`, `DslInputMap`, `DslFilterNormalizer`, and `DslPaginationPolicy`.

Application code owns:

- reading HTTP requests;
- adapting application validation / normalization into `DslFilterNormalizer`;
- translating exceptions;
- executing `count`, `paginate`, cursor pagination, export limits, or response wrapping;
- authorization, tenant isolation, and business default scopes;
- resource-specific field allowlists.

Do not push the following into shared core:

- `App\` namespace classes;
- framework-specific adapters;
- `ApiException`, HTTP status, or response envelopes;
- application pagination traits;
- legacy compatibility runtimes;
- resource permissions or field policies.

## 30-second Quick Start

```php
<?php

use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefinition;
use HongXunPan\EloquentQueryDsl\QueryDsl;

$definition = DslQueryDefinition::make('article')
    ->allowSearch(['title', 'summary'])
    ->allowFilter(['status'])
    ->allowSort(['published_at', 'id'])
    ->defaultSort('id', 'desc')
    ->strict();

$result = QueryDsl::for(Article::query(), $definition)
    ->from([
        'query' => [
            'search' => ['title' => 'alumni'],
            'filter' => ['status' => 'published'],
            'sort' => [
                ['field' => 'published_at', 'order' => 'desc'],
            ],
        ],
        'page' => ['page' => 1, 'limit' => 20],
    ])
    ->apply();

$builder = $result->builder();
$pagination = $result->pagination();
$filterValues = $result->filterValues();
```

This example shows the smallest default path:

- declare allowed query behavior through `DslQueryDefinition`;
- apply input with `QueryDsl::for(...)->from(...)->apply()`;
- read the Builder and neutral facts from `QueryDslResult`;
- let application code execute pagination and return responses.

For richer use cases, see [High-value Canonical Examples](./docs/high-value-canonical-examples.md).

## Public API Quick Reference

This table is intentionally short. For responsibilities, signatures, extension points, and examples, read [API Reference and Extension Points](./docs/api-reference.md).

| API | Role |
| --- | --- |
| `QueryDsl` | Recommended entrypoint that wires Builder, definition, input, extensions, and returns `QueryDslResult` |
| `QueryDslResult` | Holds the mutated Builder, parsed filter values, and pagination facts |
| `DslQueryDefinition` | Declares allowed search, filter, between, sort, relation paths, defaults, strict mode, and derived behavior |
| `DslInputMap` | Maps external parameter names to Query DSL sections without changing caller-facing API shape |
| `DslInputParser` | Full custom input parser contract |
| `DslFilterNormalizer` | Neutral bridge from application validation / normalization into filter facts |
| `DslPaginationPolicy` | Controls page / limit / export limit normalization and caps |
| `DslPaginationRequest` | Neutral pagination facts; it does not execute pagination |

## Capability Quick Reference

For full status, behavior, adapter boundaries, and rejection reasons, read [Query Capability Matrix](./docs/query-capability-matrix.md).

| Capability | Status | Short meaning |
| --- | --- | --- |
| Field search | Supported | `search[field => value]`; multiple fields are AND; empty strings are ignored |
| Keyword search | Supported | one input field OR-matches multiple main-entity fields |
| Filter | Supported | scalar becomes `where`; array becomes `whereIn`; empty values are ignored; `0` / `false` are meaningful |
| Filter rules | Supported | `allowFilter()` declares capabilities; `filterRules()` declares validation / normalization rules such as `field.*` |
| Filter normalizer | Supported | application validation / normalization through `DslFilterNormalizer` |
| Derived filter/search | Supported | explicit extension hooks for application-specific query behavior |
| Between | Supported | applies `whereBetween`; business time-boundary policy stays outside core |
| Sort | Supported | explicit sort, default sort, derived default sort, and direction policies |
| Relation search/filter | Supported | uses declared relation path mapping and relation scopes |
| Relation sort | Not supported | intentionally blocked to avoid implicit join/group semantics |
| Pagination facts | Supported | returns normalized facts only; application code executes pagination |
| HTTP response | Adapter | application responsibility |
| Authorization / tenancy | Adapter | application responsibility |

## Default Input Shape

The default input shape uses a `query` wrapper:

```php
$params = [
    'query' => [
        'search' => ['title' => 'alumni'],
        'filter' => ['status' => 'published'],
        'between' => ['created_at' => ['2026-01-01', '2026-12-31']],
        'sort' => [
            ['field' => 'published_at', 'order' => 'desc'],
        ],
    ],
    'page' => ['page' => 1, 'limit' => 20],
];
```

This is the default protocol, not a core limitation. Use `DslInputMap` or `DslInputParser` when your public API already has different parameter names.

## Installation

After Packagist release:

```bash
composer require hongxunpan/eloquent-query-dsl
```

Before a stable tag, use a VCS repository or path repository for evaluation. Production applications should pin an explicit commit or tag, not floating `dev-main`.

## Verification

Inside the package directory, run:

```bash
composer validate --strict
composer quality
```

`composer quality` runs:

- `composer test`: PHPUnit + legacy smoke tests;
- `composer analyse`: PHPStan level 8;
- `composer cs:check`: PHP-CS-Fixer dry-run + lightweight repository hygiene checks;
- `composer security:audit`: Composer dependency audit.

## CI

The repository provides GitHub Actions workflow at `.github/workflows/ci.yml`.

Current matrix:

- PHP 8.0 + illuminate/database 9;
- PHP 8.1 + illuminate/database 10;
- PHP 8.2 + illuminate/database 11;
- PHP 8.3 + illuminate/database 12;
- static analysis with PHPStan level 8.

Before tagging or publishing, follow the [Release Checklist](./docs/发布检查清单.zh-CN.md).
