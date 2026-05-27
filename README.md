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

当前包仍处于 **skeleton 阶段**：已建立 Composer 包骨架、命名空间、边界说明、最小测试入口、filter normalize 的中性契约与 DTO，并已平移主要 QueryDSL V2 core 对象和包级 core regression；后续仍需业务仓反接。

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
- backend 旧 DSL compat 翻译层；
- 业务资源字段开放清单、权限语义或页面返回结构；
- 与 `0759-club` 特定 validator 子类绑定的异常翻译。

## 当前边界

当前已完成：

- `composer.json`
- `src/`
- `tests/TestRunner.php`
- 本 README
- filter normalize 中性契约与 DTO
- Exception / Field / Condition 第一组低耦合 core 对象
- Definition / Derived / Input / Page
- Filter value facts（不包含 backend validator bridge）
- Reader / Apply / Section / Kernel
- 包级 core regression

其中 filter section 已改接包内中性的 `DslFilterNormalizer`，不会直接引用 backend 的 validator bridge。

后续批次进入 core 平移时，必须继续沿用包内中性的 filter normalize contract / DTO，避免把 backend 的 `QueryDslFilterValidator / QueryDslNormalizedFilterSet` 反向带入共享包。

## 命名空间

```php
HongXunPan\EloquentQueryDsl\
```

## 安装

正式发布后预计使用：

```bash
composer require hongxunpan/eloquent-query-dsl
```

当前阶段尚未发布 tag，业务仓不得直接把本 skeleton 当作已稳定能力接入。

## 最小验证

在共享 Composer 包容器路径内执行：

```bash
composer validate
composer test
```

当前 `composer test` 会执行：

- `tests/TestRunner.php`：包骨架、基础对象与中性 filter DTO 最小断言；
- `tests/CoreRegression.php`：脱离业务仓的 QueryDSL core regression，覆盖 query/page 输入、search/filter/between/sort、relation、strict、derived filter/default sort 等核心语义。
