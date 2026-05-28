# API Reference and Extension Points

[简体中文](./API能力与扩展点.zh-CN.md)

This document describes the public API surface of `hongxunpan/eloquent-query-dsl`, the supported extension points, and the internal classes that should not be depended on by applications.

The package is a query-building core. It mutates an Eloquent `Builder` and returns neutral facts. It does not read HTTP requests, execute pagination, build response envelopes, or translate business exceptions.

---

## 1. Public API groups

### 1.1 Recommended entrypoint

| API | Responsibility |
| --- | --- |
| `QueryDsl` | Wires Builder, definition, input parsing, optional extensions, and applies query sections |
| `QueryDslResult` | Returns the mutated Builder, request context, filter values, and pagination facts |

### 1.2 Definition API

| API | Responsibility |
| --- | --- |
| `DslQueryDefinition` | Declares allowed search, filter, between, sort, relations, defaults, strict mode, and derived behavior |
| `DslQueryDefaultSort` | Describes one default sort item |
| `DslSortDirectionPolicy` | Restricts accepted sort direction for declared sort fields |

### 1.3 Input API

| API | Responsibility |
| --- | --- |
| `DslInputMap` | Maps external parameter names to Query DSL sections |
| `DslInputParser` | Contract for a fully custom input parser |
| `DefaultDslInputParser` | Default parser for wrapped, array, JSON-object, and flat inputs |
| `DslQueryInput` / `DslPageInput` | Normalized query and page input objects |

### 1.4 Filter normalization API

| API | Responsibility |
| --- | --- |
| `DslFilterNormalizer` | Bridge from application validation / normalization to neutral filter facts |
| `DslNormalizedFilterSet` | Result returned by a normalizer |
| `DslNormalizedFilterItem` | Normalized values for one payload field |
| `DslFilterValues` / `DslFilterValue` | Parsed filter facts exposed to application code after applying DSL |

### 1.5 Pagination facts API

| API | Responsibility |
| --- | --- |
| `DslPaginationPolicy` | Controls default page / limit, maximum limit, export limit, numeric-string and float handling |
| `DslPaginationRequest` | Neutral pagination facts: `page`, `limit`, and optional `exportLimit` |

### 1.6 Derived behavior API

| API | Responsibility |
| --- | --- |
| `DslDerivedFilterRuleMap` | Maps a filter protocol value to a derived rule |
| `DslDerivedFilterRule` | Applies one derived filter rule to Builder |
| `DslFilterValueDerivedDefaultSortStrategy` | Chooses default sort items from an already parsed filter value |
| `DslKeywordSearchHandler` | Built-in derived search handler behind `allowKeywordSearch()` |

---

## 2. `QueryDsl`

```php
$result = QueryDsl::for(Article::query(), $definition)
    ->from($params)
    ->inputMap($inputMap)
    ->inputParser($parser)
    ->filterNormalizer($normalizer)
    ->paginationPolicy($policy)
    ->apply();
```

### Stable methods

| Method | Meaning |
| --- | --- |
| `QueryDsl::for(Builder $builder, DslQueryDefinition $definition)` | Creates a DSL runner for one Builder and one definition |
| `from(array $params, ?DslInputMap $inputMap = null)` | Supplies raw external params and optionally an input map |
| `inputMap(DslInputMap $inputMap)` | Overrides external parameter mapping |
| `inputParser(DslInputParser $inputParser)` | Uses a fully custom parser |
| `filterNormalizer(DslFilterNormalizer $filterNormalizer)` | Connects application validation / normalization |
| `paginationPolicy(DslPaginationPolicy $paginationPolicy)` | Overrides pagination normalization limits |
| `apply()` | Applies query sections and returns `QueryDslResult` |

`QueryDsl` mutates the supplied Builder. If your application needs the original Builder unchanged, clone or recreate it before calling `apply()`.

---

## 3. `QueryDslResult`

```php
$builder = $result->builder();
$context = $result->context();
$filters = $result->filterValues();
$page = $result->pagination();
```

| Method | Meaning |
| --- | --- |
| `builder()` | The Builder after search / filter / between / sort have been applied |
| `context()` | Full neutral request context used by the runtime |
| `filterValues()` | Parsed filter facts for application-side follow-up logic |
| `pagination()` | Normalized page facts; pagination is still executed by application code |

The result does not contain `count`, `items`, HTTP status, response envelope, or business error format.

---

## 4. `DslQueryDefinition`

`DslQueryDefinition` is the main declaration object. It answers “what is allowed for this resource?”

```php
$definition = DslQueryDefinition::make('article')
    ->relation('author', 'author')
    ->allowSearch(['title', 'summary'])
    ->allowKeywordSearch('keyword', ['title', 'summary'])
    ->allowFilter(['status', 'author.name'])
    ->allowBetween(['published_at'])
    ->allowSort(['published_at', 'id'])
    ->sortDescOnly(['published_at'])
    ->defaultSort('id', 'desc')
    ->strict();
```

### Search methods

| Method | Meaning |
| --- | --- |
| `allowSearch(array $fields)` | Allows field-level search; multiple input fields are applied as AND |
| `searchRightLike(array $fields)` | Uses right-like behavior for selected search fields |
| `allowKeywordSearch(string $field, array $targetFields, string $mode = '')` | Allows one input field to OR-match multiple main-entity target fields |
| `allowDerivedSearch(string $field, callable $handler)` | Lets application code handle custom search behavior explicitly |

### Filter methods

| Method | Meaning |
| --- | --- |
| `allowFilter(array $fields)` | Allows scalar `where` and array `whereIn`; associative values become normalizer rules |
| `allowDerivedFilter(string $field, DslDerivedFilterRuleMap $ruleMap)` | Maps protocol values to derived query rules |
| `allowDerivedFilterHandler(string $field, callable $handler)` | Lets application code handle a custom filter field directly |

### Between and sort methods

| Method | Meaning |
| --- | --- |
| `allowBetween(array $fields)` | Allows `[start, end]` range input for selected fields |
| `allowSort(array $fields)` | Allows explicit sort fields |
| `sortAscOnly(array $fields)` / `sortDescOnly(array $fields)` | Restricts accepted direction per field |
| `defaultSort(string $field, string $order = 'asc')` | Adds one default sort when no explicit sort is supplied |
| `defaultSortMany(DslQueryDefaultSort ...$sorts)` | Adds multiple stable default sort items |
| `allowDerivedDefaultSortByFilter(string $field, DslDerivedDefaultSortStrategy $strategy)` | Chooses default sort items based on a parsed filter value |

### Relation methods

| Method | Meaning |
| --- | --- |
| `relation(string $entity, string $relation)` | Maps a DSL relation alias to an Eloquent relation method |
| `hasRelation(string $entity)` / `relationFor(string $entity)` | Introspection helpers |

Relation search / filter requires a declared relation mapping. Relation sort is intentionally not supported because implicit joins, grouping, selected columns, and duplicate rows are application-specific.

### Strict mode

`strict()` blocks undeclared or invalid query input. Loose mode ignores unsupported fields and invalid sort directions where safe. Public APIs should normally use `strict()` unless backward compatibility requires a softer migration path.

---

## 5. Input mapping

### Default wrapped input

```php
$params = [
    'query' => [
        'search' => ['keyword' => 'alumni'],
        'filter' => ['status' => 'published'],
        'between' => ['published_at' => ['2026-01-01', '2026-12-31']],
        'sort' => [
            ['field' => 'published_at', 'order' => 'desc'],
        ],
    ],
    'page' => ['page' => 1, 'limit' => 20],
];
```

### Rename external keys

```php
$inputMap = DslInputMap::make()
    ->filter('where')
    ->sort('order_by')
    ->page('page')
    ->limit('per_page')
    ->arrayInput();
```

### Flat input

```php
$inputMap = DslInputMap::make()
    ->flatInput()
    ->search('keyword')
    ->filterPrefix('where_')
    ->sort('sort')
    ->page('page')
    ->limit('per_page');
```

Use `DslInputParser` only when key mapping is not enough and your application must transform a different public protocol into `DslQueryInput` and `DslPageInput`.

---

## 6. Filter normalizer extension

Use `DslFilterNormalizer` when your application already has validation, trimming, type coercion, enum parsing, or required-field rules.

```php
use HongXunPan\EloquentQueryDsl\Filter\Contract\DslFilterNormalizer;
use HongXunPan\EloquentQueryDsl\Filter\Value\DslNormalizedFilterItem;
use HongXunPan\EloquentQueryDsl\Filter\Value\DslNormalizedFilterSet;

final class ProjectFilterNormalizer implements DslFilterNormalizer
{
    public function normalize(array $payload, array $rules, array $options = []): DslNormalizedFilterSet
    {
        $items = [];

        if (isset($payload['status'])) {
            $items['status'] = new DslNormalizedFilterItem('status', [(string) $payload['status']]);
        }

        return DslNormalizedFilterSet::success($payload, $items);
    }
}
```

The normalizer should return neutral facts. It should not return HTTP responses, application exception objects, or localized response envelopes.

---

## 7. Pagination policy extension

```php
$policy = DslPaginationPolicy::default()
    ->withDefaultLimit(20)
    ->withMaxLimit(50)
    ->withMaxExportLimit(500)
    ->withNumericStringEnabled(true)
    ->withFloatEnabled(false);
```

`DslPaginationRequest` gives you facts only:

```php
$pagination = $result->pagination();
$items = $result->builder()
    ->forPage($pagination->page(), $pagination->limit())
    ->get();
```

Application code decides whether to call `paginate()`, `simplePaginate()`, cursor pagination, export limits, or a custom response wrapper.

---

## 8. Internal namespaces

Do not depend on these namespaces as public API:

- `Reader\*`
- `Apply\*`
- `Section\*`
- `Kernel\*`
- `Input\Internal\*`

They may change without a compatibility promise before `1.0`.
