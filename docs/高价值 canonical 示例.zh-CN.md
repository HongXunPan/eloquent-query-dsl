# 高价值 canonical 示例

本文用于沉淀 `hongxunpan/eloquent-query-dsl` 的高价值使用场景。README 只保留最小示例；完整示例以本文为主，避免首页持续膨胀。

---

## 1. 标准列表查询

```php
use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefinition;
use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefaultSort;
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

适用场景：

- 标准后台列表；
- 字段白名单和排序规则需要稳定声明；
- 响应结构仍由业务仓统一包装。

---

## 2. 单 keyword 搜多个字段

```php
$definition = DslQueryDefinition::make('article')
    ->allowKeywordSearch('keyword', ['title', 'summary'])
    ->strict();

$result = QueryDsl::for(Article::query(), $definition)
    ->from([
        'query' => [
            'search' => ['keyword' => '校友会'],
        ],
    ])
    ->apply();
```

语义：

- `keyword` 是输入字段；
- `title / summary` 是目标字段；
- 目标字段之间是 OR 分组；
- 当前只支持主实体字段；relation OR 搜索建议由使用侧 derived search handler 显式承接。

---

## 3. 接入项目 filter normalizer

```php
use HongXunPan\EloquentQueryDsl\Filter\Contract\DslFilterNormalizer;
use HongXunPan\EloquentQueryDsl\Filter\Value\DslNormalizedFilterSet;

final class ProjectFilterNormalizer implements DslFilterNormalizer
{
    public function normalize(array $payload, array $rules, array $options = []): DslNormalizedFilterSet
    {
        // 在这里接入项目 validator / normalize 能力。
        // 返回 DslNormalizedFilterSet，避免 shared core 依赖业务 validator 返回结构。
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

边界：

- shared core 只认 `DslFilterNormalizer` 契约；
- 业务项目的 validator bridge、中文错误 envelope、异常类型都应留在 adapter；
- normalizer 返回空值时，普通 filter 不生成 where。

---

## 4. 自定义外部参数名

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

适用场景：

- 项目已有历史参数名；
- 不希望为了接入 shared core 改 API 外部协议；
- 只需要映射参数名，不需要重写 parser。

---

## 5. Flat 输入

```php
$inputMap = DslInputMap::make()
    ->flatInput()
    ->search('keyword')
    ->filterPrefix('where_')
    ->sort('sort')
    ->page('page')
    ->limit('per_page');

$params = [
    'keyword' => '校友会',
    'where_status' => 'published',
    'sort' => '-published_at',
    'page' => 1,
    'per_page' => 20,
];

$result = QueryDsl::for($builder, $definition)
    ->from($params, $inputMap)
    ->apply();
```

适用场景：

- 轻量 API 或旧接口采用扁平参数；
- 希望逐步接入 Query DSL，但不立刻改调用方协议。

---

## 6. 分页事实策略

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

语义：

- 本包只输出分页事实；
- `limit / export_limit` 会被 policy 限制；
- 业务仓自行决定执行 `paginate()`、`limit()`、导出或响应包装。

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

语义：

- relation 字段需要先声明 relation 映射；
- filter 会通过 relation scope 应用；
- relation sort 当前不支持，避免隐式 join / group 语义不稳定。

---

## 8. 推荐接入步骤

1. 在业务资源 service 中先定义 `DslQueryDefinition`；
2. 只开放真实需要的 search / filter / sort 字段；
3. 若 filter 需要 required / trim / default 等规则，通过项目 adapter 实现 `DslFilterNormalizer`；
4. 用 `QueryDsl::for(...)->from(...)->apply()` 得到 Builder 与中性 facts；
5. 在业务仓执行分页、异常翻译与响应包装；
6. 为核心列表补输入条件、SQL、bindings、结果集断言。
