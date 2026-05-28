# Query Capability Matrix

[简体中文](./查询能力矩阵.zh-CN.md)

This matrix is the capability source of truth for `hongxunpan/eloquent-query-dsl`. It describes what is supported by core, what belongs to adapters, and what is intentionally out of scope.

README stays as the entry page. Capability status and semantics should be maintained here and in versioned release notes.

---

## 1. Status labels

| Label | Status | Meaning |
| --- | --- | --- |
| ✅ | Supported | Implemented in core and expected to have tests |
| 🟡 | Planned | Accepted direction for a future core release |
| 🔵 | Adapter | Valid need, but belongs to framework / application adapter |
| ⛔ | Rejected | Conflicts with package boundaries or security model |
| ⚪ | Under review | No commitment yet; needs real-world issue or use case |

---

## 2. Supported capabilities

| Status | Capability | Area | Input shape | Mutates Builder | Notes |
| --- | --- | --- | --- | --- | --- |
| ✅ | `DslQueryDefinition::make()` | definition | main entity name | No | Main entity uses safe identifier validation |
| ✅ | `relation(entity, relation)` | definition | relation mapping | No | Used by relation search / filter through relation scopes |
| ✅ | `allowSearch()` | search | `search[field => value]` | Yes | Field search; multiple fields are AND; empty strings ignored |
| ✅ | `searchRightLike()` | search | field list | Yes | Uses right-like semantics for selected search fields |
| ✅ | `allowKeywordSearch()` | search | one input field + target fields | Yes | OR group across main-entity target fields |
| ✅ | `allowDerivedSearch()` | search | callable handler | Yes | Application-owned complex search logic |
| ✅ | `allowFilter()` | filter | `filter[field => value]` | Yes | Scalar becomes `where`; array becomes `whereIn`; empty values ignored; `0` / `false` meaningful |
| ✅ | `DslFilterNormalizer` | filter | payload + rules | Indirect | Connects application validation / normalization through neutral DTOs |
| ✅ | `allowDerivedFilter()` | filter | rule map | Yes | Suitable for null / not-null / has relation / doesn't have relation style rules |
| ✅ | `allowDerivedFilterHandler()` | filter | callable handler | Yes | Application-owned custom filter behavior |
| ✅ | `allowBetween()` | between | `[start, end]` | Yes | Applies `whereBetween`; business time-boundary policy stays outside core |
| ✅ | `allowSort()` | sort | sort list / map / string | Yes | Explicit multi-field sorting with stable order |
| ✅ | `sortAscOnly()` / `sortDescOnly()` | sort | field list | Yes | Restricts direction for selected sort fields |
| ✅ | `defaultSort()` / `defaultSortMany()` | sort | field + direction | Yes | Applies when explicit sort is absent |
| ✅ | Derived default sort | sort | filter-based strategy | Yes | Chooses default sorts from parsed filter values |
| ✅ | Strict mode | behavior | `strict(true)` | Indirect | Blocks undeclared sections, fields, or invalid input |
| ✅ | Loose mode | behavior | `strict(false)` | Indirect | Ignores unsupported fields where safe |
| ✅ | Identifier security | security | field / entity / relation | No | Ordinary identifiers are limited to safe names |
| ✅ | `DslInputMap` | input | custom parameter names | No | Keeps public API parameter shape stable |
| ✅ | `DslInputParser` | input | custom parser | No | Full parser replacement |
| ✅ | Flat input | input | flat parameters | No | Useful for legacy or lightweight APIs |
| ✅ | `DslPaginationPolicy` | page | policy | No | Normalizes and caps page / limit / export_limit facts |
| ✅ | `QueryDslResult` | output | builder + facts | No | Returns Builder, filter values, and pagination facts |

---

## 3. Adapter-owned or unsupported capabilities

| Status | Capability | Area | Reason |
| --- | --- | --- | --- |
| ⛔ | Relation sort | sort | Core does not create implicit join / group / select semantics |
| 🔵 | HTTP request reading | adapter | Framework-specific |
| 🔵 | HTTP response / response envelope | adapter | Core returns neutral query facts only |
| 🔵 | Application pagination trait | adapter | Pagination response structure belongs to application code |
| 🔵 | Business exception translation | adapter | Applications translate Query DSL exceptions into their own error format |
| 🔵 | Authorization / tenancy | adapter | Depends on user, permission, and data-domain rules |
| 🔵 | Resource field policy | adapter | Resource-specific allowlists belong to application definitions |
| 🔵 | Projection / DTO presentation | adapter | Use a projection layer or presenter |
| ⛔ | Raw SQL field strings | security | Ordinary fields never accept expressions; future raw support would need explicit risky API |
| ⛔ | Multi-level path disguised as field | security | `a.b.c`, functions, quotes, backticks, and similar payloads are rejected |

---

## 4. Maintenance rules

1. New core query capabilities must update this file, README links, and regression tests.
2. Planned capabilities becoming supported must add SQL / bindings / result-set assertions.
3. Adapter-owned or rejected items must keep a reason to prevent repeated boundary drift.
4. README should link to the matrix instead of duplicating it.
5. If documentation and code diverge, fix the document or implementation immediately before release.
