# Public Contracts and Stability

[简体中文](./公开契约与稳定性承诺.zh-CN.md)

This document defines the public contracts of `hongxunpan/eloquent-query-dsl` and the internal implementation areas that applications should not depend on.

The repository is still in **pre-1.0** development. Documented public APIs are intended to remain stable where possible; if a breaking change becomes necessary, it must be described in README, CHANGELOG, and migration notes.

---

## 1. Principles

### 1.1 Input and output boundary

The stable output boundary is:

- `QueryDsl::for(...)->from(...)->apply()`;
- `QueryDslResult`;
- `DslFilterValues`;
- `DslPaginationRequest`.

The package will not become an HTTP response builder, a `{ page, list }` envelope provider, or an application pagination trait. Those structures belong in application adapters or services.

### 1.2 Internal implementation is not stable

The following namespaces are internal by default:

- `HongXunPan\EloquentQueryDsl\Reader\*`
- `HongXunPan\EloquentQueryDsl\Apply\*`
- `HongXunPan\EloquentQueryDsl\Section\*`
- `HongXunPan\EloquentQueryDsl\Kernel\*`
- `HongXunPan\EloquentQueryDsl\Input\Internal\*`

They may be renamed, moved, split, merged, or have their constructors changed before `1.0`.

### 1.3 Documented public surface wins

Not every PHP `public` method is automatically a stable public contract. A class or method is treated as stable only when it is documented in README, API Reference, this file, or versioned release notes.

---

## 2. Stable public contracts

### 2.1 Recommended public entrypoints

- `HongXunPan\EloquentQueryDsl\QueryDsl`
- `HongXunPan\EloquentQueryDsl\QueryDslResult`
- `HongXunPan\EloquentQueryDsl\Definition\DslQueryDefinition`
- `HongXunPan\EloquentQueryDsl\Input\DslInputMap`
- `HongXunPan\EloquentQueryDsl\Input\Contract\DslInputParser`
- `HongXunPan\EloquentQueryDsl\Filter\Contract\DslFilterNormalizer`
- `HongXunPan\EloquentQueryDsl\Filter\DslFilterValues`
- `HongXunPan\EloquentQueryDsl\Page\DslPaginationPolicy`
- `HongXunPan\EloquentQueryDsl\Page\DslPaginationRequest`

### 2.2 `QueryDsl`

Stable responsibilities:

- bind an Eloquent `Builder` and a `DslQueryDefinition`;
- accept raw external parameter arrays;
- optionally use `DslInputMap`;
- optionally use a custom `DslInputParser`;
- optionally use `DslFilterNormalizer`;
- optionally use `DslPaginationPolicy`;
- apply query sections to Builder;
- return `QueryDslResult`.

### 2.3 `DslQueryDefinition`

Stable responsibilities:

- main entity name;
- relation mapping;
- search / filter / between / sort allowlists;
- keyword search;
- derived search and filter;
- default sort and derived default sort;
- strict / loose mode.

Entity aliases, relation path segments, and ordinary fields go through identifier validation. Ordinary field strings do not accept raw SQL, function calls, JSON paths, quoted identifiers, or multi-level paths disguised as fields. Relation mappings may use Eloquent dot paths such as `comments.author`, but every segment is still validated as a safe identifier.

### 2.4 Input extension contracts

Stable input extension APIs:

- `DslInputMap`: external parameter name mapping;
- `DslInputParser`: fully custom input parsing;
- `DefaultDslInputParser`: default parser for wrapped, array / JSON-object, and flat input.

Input mapping only transforms external protocol shape into Query DSL sections. It does not validate business rules, check authorization, or format responses.

### 2.5 Filter normalizer contract

`DslFilterNormalizer` is the neutral bridge between application validation / normalization and shared core.

Stable semantics:

- input: filter payload, field rules, options;
- output: `DslNormalizedFilterSet`;
- failure: error list converted by shared core into Query DSL exception;
- success: normalized filter items and values.

The package does not depend on an application validator, localized error envelope, or framework exception type.

### 2.6 Pagination facts contract

`DslPaginationPolicy` and `DslPaginationRequest` expose only pagination facts:

- `page`;
- `limit`;
- `export_limit`.

Stable defaults:

- `defaultPage = 1`;
- `defaultLimit = 20`;
- `maxLimit = 100`;
- `maxExportLimit = 1000`;
- booleans, disabled floats, and non-integer strings are ignored as pagination values.

The package does not execute pagination and does not return `{ page, list }`.

---

## 3. Not part of the stability promise

- `QueryDslV2Kernel`, retained only as an internal compatibility entrypoint;
- section applier wiring details;
- reader / apply / kernel collaboration details;
- legacy smoke runner internals;
- concrete application filter normalizer implementations;
- Eloquent `paginate`, `count`, or cursor pagination execution;
- HTTP response, business exception translation, or error codes;
- authorization, tenancy, default business scopes;
- resource-level field policies.

---

## 4. Change rules

1. Changes to recommended public entrypoints must update README, CHANGELOG, and this document.
2. Changes to search / filter / sort / pagination semantics must update the capability matrix and regression tests.
3. Adding an internal class does not make it public API.
4. Promoting an internal capability to public API requires documentation and tests before release.
5. During pre-1.0, API changes are allowed but migration impact must be recorded in CHANGELOG.
