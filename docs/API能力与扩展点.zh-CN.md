# API 能力与扩展点

[English](./api-reference.md)

本文说明 `hongxunpan/eloquent-query-dsl` 当前公开 API、扩展点与不建议外部依赖的内部实现面。

本包是查询构建 core：它修改 Eloquent `Builder` 并返回中性事实；它不读取 HTTP request、不执行分页、不构造 response envelope，也不翻译业务异常。

---

## 1. Public API 分组

### 1.1 推荐入口

| API | 职责 |
| --- | --- |
| `QueryDsl` | 编排 Builder、definition、输入解析、可选扩展点，并应用查询 section |
| `QueryDslResult` | 返回已修改的 Builder、请求上下文、filter values 与 pagination facts |

### 1.2 Definition API

| API | 职责 |
| --- | --- |
| `DslQueryDefinition` | 声明 search、filter、between、sort、relation、默认排序、strict 与 derived 行为 |
| `DslQueryDefaultSort` | 描述一个默认排序项 |
| `DslSortDirectionPolicy` | 限制已声明排序字段可接受的方向 |

### 1.3 Input API

| API | 职责 |
| --- | --- |
| `DslInputMap` | 把外部参数名映射到 Query DSL section |
| `DslInputParser` | 完全自定义输入解析契约 |
| `DefaultDslInputParser` | 默认 parser，支持包裹结构、array、JSON object 与 flat 输入 |
| `DslQueryInput` / `DslPageInput` | 归一化后的查询输入与分页输入对象 |

### 1.4 Filter normalization API

| API | 职责 |
| --- | --- |
| `DslFilterNormalizer` | 应用 validator / normalize 能力与中性 filter facts 之间的桥 |
| `DslNormalizedFilterSet` | normalizer 返回结果 |
| `DslNormalizedFilterItem` | 单个 payload 字段的归一化值 |
| `DslFilterValues` / `DslFilterValue` | DSL 应用后暴露给应用代码的 filter facts |

### 1.5 Pagination facts API

| API | 职责 |
| --- | --- |
| `DslPaginationPolicy` | 控制默认 page / limit、最大 limit、export limit、数字字符串与 float 处理 |
| `DslPaginationRequest` | 中性分页事实：`page`、`limit`、可选 `exportLimit` |

### 1.6 Derived behavior API

| API | 职责 |
| --- | --- |
| `DslDerivedFilterRuleMap` | 把 filter 协议值映射到派生规则 |
| `DslDerivedFilterRule` | 把一条派生 filter 规则应用到 Builder |
| `DslFilterValueDerivedDefaultSortStrategy` | 根据已解析 filter 值选择默认排序项 |
| `DslKeywordSearchHandler` | `allowKeywordSearch()` 背后的内置 derived search handler |

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

### 稳定方法

| 方法 | 含义 |
| --- | --- |
| `QueryDsl::for(Builder $builder, DslQueryDefinition $definition)` | 为一个 Builder 和一个 definition 创建 DSL runner |
| `from(array $params, ?DslInputMap $inputMap = null)` | 传入外部原始参数，可同时传入 input map |
| `inputMap(DslInputMap $inputMap)` | 覆盖外部参数映射 |
| `inputParser(DslInputParser $inputParser)` | 使用完全自定义 parser |
| `filterNormalizer(DslFilterNormalizer $filterNormalizer)` | 接入应用校验 / 归一化能力 |
| `paginationPolicy(DslPaginationPolicy $paginationPolicy)` | 覆盖分页事实归一化策略 |
| `apply()` | 应用查询 section 并返回 `QueryDslResult` |

`QueryDsl` 会修改传入的 Builder。若应用需要保留原始 Builder，请在调用 `apply()` 前 clone 或重新创建。

---

## 3. `QueryDslResult`

```php
$builder = $result->builder();
$context = $result->context();
$filters = $result->filterValues();
$page = $result->pagination();
```

| 方法 | 含义 |
| --- | --- |
| `builder()` | 已应用 search / filter / between / sort 的 Builder |
| `context()` | runtime 使用的完整中性请求上下文 |
| `filterValues()` | 应用侧后续逻辑可读取的 filter facts |
| `pagination()` | 已归一化的分页事实；分页仍由应用代码执行 |

结果对象不包含 `count`、`items`、HTTP status、响应 envelope 或业务错误格式。

---

## 4. `DslQueryDefinition`

`DslQueryDefinition` 是主要声明对象，用于回答“这个资源允许怎么查”。

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

### Search 方法

| 方法 | 含义 |
| --- | --- |
| `allowSearch(array $fields)` | 开放字段级 search；多个输入字段按 AND 生效 |
| `searchRightLike(array $fields)` | 将指定 search 字段改为 right-like 语义 |
| `allowKeywordSearch(string $field, array $targetFields, string $mode = '')` | 允许一个输入字段 OR 命中多个主实体目标字段 |
| `allowDerivedSearch(string $field, callable $handler)` | 由应用代码显式承接自定义 search 行为 |

### Filter 方法

| 方法 | 含义 |
| --- | --- |
| `allowFilter(array $fields)` | 开放标量 `where` 与数组 `whereIn`；关联数组 value 作为 normalizer rule |
| `allowDerivedFilter(string $field, DslDerivedFilterRuleMap $ruleMap)` | 把协议值映射到派生查询规则 |
| `allowDerivedFilterHandler(string $field, callable $handler)` | 由应用代码直接处理自定义 filter 字段 |

### Between 与 sort 方法

| 方法 | 含义 |
| --- | --- |
| `allowBetween(array $fields)` | 对指定字段开放 `[start, end]` 区间输入 |
| `allowSort(array $fields)` | 开放显式排序字段 |
| `sortAscOnly(array $fields)` / `sortDescOnly(array $fields)` | 限制字段排序方向 |
| `defaultSort(string $field, string $order = 'asc')` | 无显式 sort 时追加一个默认排序 |
| `defaultSortMany(DslQueryDefaultSort ...$sorts)` | 追加多个稳定默认排序项 |
| `allowDerivedDefaultSortByFilter(string $field, DslDerivedDefaultSortStrategy $strategy)` | 根据已解析 filter 值选择默认排序项 |

### Relation 方法

| 方法 | 含义 |
| --- | --- |
| `relation(string $entity, string $relation)` | 把 DSL relation alias 映射到 Eloquent relation 方法 |
| `hasRelation(string $entity)` / `relationFor(string $entity)` | relation introspection helper |

relation search / filter 必须先声明 relation 映射。relation sort 目前明确不支持，因为隐式 join、group、select 与重复行处理都属于应用场景差异。

### Strict 模式

`strict()` 会阻断未声明或非法查询输入。loose mode 会在安全前提下忽略未支持字段和非法排序方向。公开 API 一般建议使用 `strict()`，除非为了兼容旧调用方需要渐进迁移。

---

## 5. 输入映射

### 默认包裹输入

```php
$params = [
    'query' => [
        'search' => ['keyword' => '校友'],
        'filter' => ['status' => 'published'],
        'between' => ['published_at' => ['2026-01-01', '2026-12-31']],
        'sort' => [
            ['field' => 'published_at', 'order' => 'desc'],
        ],
    ],
    'page' => ['page' => 1, 'limit' => 20],
];
```

### 改外部参数名

```php
$inputMap = DslInputMap::make()
    ->filter('where')
    ->sort('order_by')
    ->page('page')
    ->limit('per_page')
    ->arrayInput();
```

### Flat 输入

```php
$inputMap = DslInputMap::make()
    ->flatInput()
    ->search('keyword')
    ->filterPrefix('where_')
    ->sort('sort')
    ->page('page')
    ->limit('per_page');
```

只有当 key 映射不足以承接现有公开协议时，才需要实现 `DslInputParser`，把外部协议转换为 `DslQueryInput` 与 `DslPageInput`。

---

## 6. Filter normalizer 扩展

当应用已有校验、trim、类型转换、枚举解析或 required 规则时，使用 `DslFilterNormalizer` 接入。

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

normalizer 应返回中性事实，不应返回 HTTP response、应用异常对象或本地化响应 envelope。

---

## 7. Pagination policy 扩展

```php
$policy = DslPaginationPolicy::default()
    ->withDefaultLimit(20)
    ->withMaxLimit(50)
    ->withMaxExportLimit(500)
    ->withNumericStringEnabled(true)
    ->withFloatEnabled(false);
```

`DslPaginationRequest` 只给出事实：

```php
$pagination = $result->pagination();
$items = $result->builder()
    ->forPage($pagination->page(), $pagination->limit())
    ->get();
```

应用代码自行决定使用 `paginate()`、`simplePaginate()`、cursor pagination、导出限制或自定义 response wrapper。

---

## 8. 内部命名空间

不要把以下命名空间当作 public API 依赖：

- `Reader\*`
- `Apply\*`
- `Section\*`
- `Kernel\*`
- `Input\Internal\*`

这些内容在 `1.0` 前可能不提供兼容性承诺。
