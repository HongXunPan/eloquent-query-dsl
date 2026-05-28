# Eloquent Query DSL

`hongxunpan/eloquent-query-dsl` 是一个面向 Eloquent / Builder 的列表查询 DSL 核心包，用于统一承接列表接口中的 `search / filter / between / sort / page` 查询协议解析与执行。

## 这里的 DSL 是什么？

DSL 是 Domain-Specific Language（领域特定语言）的缩写。

在本包中，DSL 不是一门独立编程语言，而是一套面向 Eloquent 列表查询的约定式查询协议与执行内核。它把常见列表查询能力抽象为稳定结构，例如：

- `search`：关键词搜索；
- `filter`：精确筛选；
- `between`：区间筛选；
- `sort`：排序；
- `page`：分页参数。

业务项目只需要声明允许哪些字段、哪些查询能力，本包负责把请求中的查询协议解析并应用到 Eloquent Builder。

## 解决什么问题？

本包主要解决列表接口中反复出现的查询样板代码：

- 每个列表接口都重复写关键词搜索、字段筛选、区间筛选和排序；
- 查询参数协议在不同接口之间不一致；
- 字段白名单、排序规则、必填筛选等能力散落在 Service 中；
- 新增列表接口时，容易把查询解析、业务过滤和返回组织混在一起。

使用本包后，业务仓可以把“允许怎么查”声明出来，把“如何解析并应用到 Eloquent Builder”交给统一内核处理。

当前包处于 **pre-1.0 开源预备发布阶段**：已建立 Composer 包、命名空间、边界说明、filter normalize 的中性契约与 DTO，并已提供 Query DSL core 对象、输入协议解析能力、开源友好主入口和包级 regression。正式 tag / Packagist 发布前仍会继续补齐发布说明、协作文件与质量门禁。

相关项目文件：

- [CHANGELOG](./CHANGELOG.md)
- [贡献说明](./CONTRIBUTING.md)
- [安全政策](./SECURITY.md)
- [MIT License](./LICENSE)

## 推荐接入方式

默认场景下，使用方不应手动理解和组装 section applier / kernel / context，而是通过一个直观主入口完成查询应用：

```php
use HongXunPan\EloquentQueryDsl\QueryDsl;
use HongXunPan\EloquentQueryDsl\Page\DslPaginationPolicy;

$result = QueryDsl::for($builder, $definition)
    ->from($params)
    ->paginationPolicy(DslPaginationPolicy::default()->withMaxLimit(100))
    ->filterNormalizer($normalizer)
    ->apply();

$query = $result->builder();
$filterValues = $result->filterValues();
$pagination = $result->pagination();
```

其中：

- `$builder` 是 Eloquent `Builder`；
- `$definition` 是 `DslQueryDefinition`，只声明允许哪些字段和查询能力；
- `$params` 是外部输入，可以来自 HTTP request、CLI 参数或业务自定义数组；
- `$normalizer` 是 `DslFilterNormalizer`，用于接入项目自己的 filter 值归一化或校验能力；
- `DslPaginationPolicy` 用于限制输出给使用侧的分页事实，例如 `limit / export_limit` 上限；
- `$result` 是 `QueryDslResult`，只返回中性的查询事实，不负责 HTTP response、分页响应结构或业务异常翻译。

### 默认输入协议

默认输入可以沿用常见的 `query` 包裹结构：

```php
$params = [
    'query' => [
        'search' => ['title' => '校友会'],
        'filter' => ['status' => 'published'],
        'between' => ['created_at' => ['2026-01-01', '2026-12-31']],
        'sort' => ['published_at' => 'desc'],
    ],
    'page' => ['page' => 1, 'limit' => 20],
];
```

这只是默认协议，不是 core 限制。`query.filter` 不会被固化成唯一入口。

### 自定义参数名

如果你的项目参数不叫 `filter`，或者分页参数不是 `page.limit`，可以通过已落地的 `DslInputMap` 描述外部参数名：

```php
use HongXunPan\EloquentQueryDsl\Input\DslInputMap;

$inputMap = DslInputMap::make()
    ->filter('where')
    ->sort('order_by')
    ->page('page')
    ->limit('per_page')
    ->arrayInput();

$result = QueryDsl::for($builder, $definition)
    ->from($params)
    ->inputMap($inputMap)
    ->filterNormalizer($normalizer)
    ->apply();
```

对应输入可以是：

```php
$params = [
    'where' => ['status' => 'published'],
    'order_by' => ['published_at' => 'desc'],
    'page' => 1,
    'per_page' => 20,
];
```

### flat 输入

对于更扁平的接口，也可以把筛选前缀映射为 filter：

```php
$inputMap = DslInputMap::make()
    ->flatInput()
    ->search('keyword')
    ->filterPrefix('where_')
    ->sort('sort')
    ->page('page')
    ->limit('per_page');
```

例如：

```php
$params = [
    'keyword' => '校友会',
    'where_status' => 'published',
    'sort' => '-published_at',
    'page' => 1,
    'per_page' => 20,
];
```

### 完全自定义解析

如果项目已有自己的请求协议，可以实现 `DslInputParser`，把任意外部输入转换为包内标准输入：

```php
use HongXunPan\EloquentQueryDsl\Input\Contract\DslInputParser;

$result = QueryDsl::for($builder, $definition)
    ->from($params)
    ->inputParser($parser)
    ->filterNormalizer($normalizer)
    ->apply();
```

`DslInputParser` 只负责输入解析，不负责字段白名单、filter 归一化、查询执行或响应结构。

### search 语义

`search` 默认是字段级搜索：每个字段各自生成一个 `LIKE` 条件，多个字段之间使用 **AND** 语义。

```php
$definition = DslQueryDefinition::make('article')
    ->allowSearch(['title', 'code'])
    ->searchRightLike(['code']);

$params = [
    'query' => [
        'search' => [
            'title' => 'Hello',
            'code' => 'AB001',
        ],
    ],
];
```

上面的输入会表达为：`title LIKE '%Hello%' AND code LIKE 'AB001%'`。

如果需要一个关键词同时命中多个主实体字段，可以使用 `allowKeywordSearch()`，它会在目标字段之间使用 **OR** 分组：

```php
$definition = DslQueryDefinition::make('article')
    ->allowKeywordSearch('keyword', ['title', 'summary', 'code']);

$params = [
    'query' => [
        'search' => ['keyword' => '校友会'],
    ],
];
```

`allowKeywordSearch()` 当前只支持主实体字段；关联字段搜索仍应通过字段级 relation search 或使用侧 derived handler 显式承接。

### filter 语义

`filter` 默认按字段生成精确匹配：

- 单值生成 `where(field, value)`；
- 多值数组生成 `whereIn(field, values)`；
- 空字符串、空数组、`null`、`false` 会被视为空值并忽略；
- 字符串 `'0'` 与数值 `0` 会被视为有效值；
- 带规则的 filter 只通过 `DslFilterNormalizer` 归一化，本包不内置业务 validator。

派生 filter 命中后只执行派生行为，不再额外生成普通 `where`。

### sort 语义

`sort` 当前语义：

- 显式 sort 优先于 default sort；
- derived default sort 优先于普通 default sort；
- 多字段 sort 保持输入顺序；
- 可用 `sortAscOnly()` / `sortDescOnly()` 限制排序方向；
- 关联字段 sort 当前不支持，建议使用侧显式 join / projection 后再决定是否开放；
- `strict(false)` 下非法 sort item 会被忽略，`strict(true)` 下会抛出输入异常。

### 分页事实策略

本包不会执行 `count / paginate / forPage`，但会把分页输入解析为中性的 `DslPaginationRequest`。为避免使用侧误用超大分页参数，默认分页策略会限制：

- `limit` 最大值：`100`；
- `export_limit` 最大值：`1000`；
- `export_limit` 生效时 page 固定回到默认第一页；
- bool、float、非整数字符串不会作为有效分页值。

如需按业务项目调整上限，可以通过 `DslPaginationPolicy` 显式声明：

```php
use HongXunPan\EloquentQueryDsl\Page\DslPaginationPolicy;

$paginationPolicy = DslPaginationPolicy::default()
    ->withMaxLimit(50)
    ->withMaxExportLimit(500);

$result = QueryDsl::for($builder, $definition)
    ->from($params)
    ->paginationPolicy($paginationPolicy)
    ->apply();
```

使用侧仍负责真正的分页执行与 `{ page, list }` 响应包装。

### 字段安全边界

普通字段、实体别名与 relation 名默认只接受安全 identifier：`[A-Za-z_][A-Za-z0-9_]*`。字段路径只接受 `field` 或 `entity.field`。

以下输入会被拒绝：

- `id desc`
- `id, name`
- `count(*)`
- `` `id` ``
- `users.name->json`
- `a.b.c`

如果业务项目确实需要 raw SQL 或表达式查询，应通过 derived handler 在使用侧显式承接风险，不要把表达式伪装成普通字段声明。

### shared 包与使用侧分工

shared 包负责：

- 输入协议解析为标准 `DslQueryInput / DslPageInput`；
- search / filter / between / sort 的主流程编排；
- `DslFilterValues` 与 `DslPaginationRequest` 等中性事实；
- `DslInputParser`、`DslInputMap`、`DslFilterNormalizer`、`DslPaginationPolicy` 等扩展契约。

使用侧负责：

- HTTP request 读取；
- 项目 validator 或 filter normalizer 适配；
- 项目异常翻译；
- `{ page, list }` 等响应结构；
- 权限、租户、业务默认筛选等项目规则。

## 定位

本包适合承接：

- 查询协议输入对象；
- 查询能力声明对象；
- search / filter / between / sort 的读取与应用；
- 派生搜索、派生筛选与默认排序策略；
- 包内中性的 filter normalize contract / DTO；
- 可脱离业务项目执行的 core regression。

本包不承接：

- `App\` 命名空间下的项目适配代码；
- `simple-framework`、`ApiException`、`QueryPaginationTrait` 等业务仓桥接能力；
- 业务仓旧 DSL compat 翻译层；
- 业务资源字段开放清单、权限语义或页面返回结构；
- 与特定业务项目 validator 子类绑定的异常翻译。

## 当前边界

当前已完成：

- `composer.json`
- `src/`
- `tests/TestRunner.php`
- 本 README
- filter normalize 中性契约与 DTO
- Exception / Field / Condition 第一组低耦合 core 对象
- Definition / Derived / Input / Page
- Filter value facts（不包含业务项目 validator bridge）
- Reader / Apply / Section / Kernel
- `DslInputMap / DslInputParser / DefaultDslInputParser`
- `QueryDsl / QueryDslResult / QueryDslKernel`
- 包级 core regression

### Public API 承诺

pre-1.0 阶段推荐使用方优先依赖以下入口：

- `QueryDsl`
- `QueryDslResult`
- `Definition\DslQueryDefinition`
- `Input\DslInputMap`
- `Input\Contract\DslInputParser`
- `Filter\Contract\DslFilterNormalizer`
- `Filter\DslFilterValues`
- `Page\DslPaginationPolicy`
- `Page\DslPaginationRequest`

`Reader / Apply / Section / Kernel / Input\Internal` 下的对象主要承接包内协作，当前不作为稳定 public API 承诺；如需自定义深层行为，优先通过 `QueryDsl`、`DslInputParser`、`DslInputMap` 与 `DslFilterNormalizer` 扩展。

其中 filter section 已改接包内中性的 `DslFilterNormalizer`，不会直接引用业务项目的 validator bridge。

输入解析层已拆成较小的内部职责对象：

- `DslInputParams`：外部参数读取；
- `DslJsonDecoder`：JSON 解码与 map 类型判断；
- `DslQueryPayloadMapper`：query section 映射；
- `DslPagePayloadMapper`：page / limit / export_limit 映射；
- `DslSortInputNormalizer`：sort 多写法归一化。

这些对象属于包内 internal 协作层，README 推荐使用方仍优先依赖 `DslInputMap / DslInputParser / DefaultDslInputParser`。

使用侧接入时，必须继续沿用包内中性的 filter normalize contract / DTO，避免把业务项目的 validator bridge、异常翻译、分页响应结构或历史 compat bridge 反向带入共享包。

## 命名空间

```php
HongXunPan\EloquentQueryDsl\
```

## 安装

正式发布到 Packagist 后使用：

```bash
composer require hongxunpan/eloquent-query-dsl
```

正式 tag 发布前，可通过 Git 仓库或 path repository 做开发期试用；生产项目建议锁定明确 commit / tag，不直接依赖浮动 `dev-main`。

## 最小验证

在共享 Composer 包容器路径内执行：

```bash
composer validate
composer test
composer analyse
composer cs:check
```

当前 `composer test` 会执行：

- `composer test:skeleton` / `tests/TestRunner.php`：包骨架、基础对象与中性 filter DTO 最小断言；
- `composer test:input-parser` / `tests/InputParserRegression.php`：默认输入协议、自定义参数名、JSON / array / flat 输入与异常边界；
- `composer test:query-dsl` / `tests/QueryDslRegression.php`：`QueryDsl::for(...)->from(...)->apply()` 主入口、`QueryDslResult`、filter values 与 pagination；
- `composer test:core` / `tests/CoreRegression.php`：脱离业务仓的 QueryDSL core regression。

包级 core regression 的标准断言口径是：

1. 输入条件：明确给出 `query / page / export_limit` payload；
2. 生效 SQL：断言 `toSql()` 中关键 `where / whereIn / exists / order by` 片段；
3. 绑定值：断言 `getBindings()` 与输入归一化结果一致；
4. 实际结果：在 SQLite in-memory fixture 中断言最终命中的模型 ID 顺序。

共享测试支撑位于 `tests/Support/`：

- `Assert.php`：无 PHPUnit 依赖的最小断言工具；
- `TestDatabase.php`：SQLite in-memory schema 与 fixture；
- `FakeDslFilterNormalizer.php`：包级测试用的中性 filter normalizer，不依赖业务仓 validator。

## CI

本仓提供 GitHub Actions 工作流：`.github/workflows/ci.yml`。

触发条件：

- `push`
- `pull_request`

默认矩阵：

- PHP 8.0
- PHP 8.2
- PHP 8.3

CI 会执行：

```bash
composer validate --strict
composer install --no-interaction --prefer-dist --no-progress
composer test
composer cs:check
composer analyse
```
