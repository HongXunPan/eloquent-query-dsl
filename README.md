# Eloquent Query DSL

`hongxunpan/eloquent-query-dsl` 是一个面向 Eloquent / Builder 的列表查询 DSL core，用于把列表接口中反复出现的 `search / filter / between / sort / page` 查询协议收口为可声明、可测试、可复用的查询内核。

它不负责 HTTP request、response envelope、业务异常翻译、分页响应结构或业务字段开放清单；这些能力应留在项目 adapter / service 层。

## 当前状态

当前仓库处于 **pre-1.0 开源预备发布阶段**。

已完成：

- Composer 子包骨架与命名空间 `HongXunPan\EloquentQueryDsl\`；
- `QueryDsl` / `QueryDslResult` 推荐主入口；
- `DslQueryDefinition` 查询能力声明模型；
- 默认输入解析、自定义参数名、flat 输入与完全自定义 parser；
- `search / filter / between / sort` section 读取与应用；
- filter normalizer 中性契约与 filter values facts；
- 字段、实体别名与 relation 名的安全 identifier 校验；
- 分页事实与分页策略上限；
- PHPUnit、legacy smoke、PHPStan level 8、PHP-CS-Fixer、Composer audit 与 GitHub Actions 矩阵；
- 发布检查清单与 `0.1.0` 待发布记录。

仍需发布前完成：

- 最终提交与远端 CI 观察；
- `0.1.0` tag / GitHub Release；
- Packagist 同步；
- 业务仓按明确 tag 接入并执行项目侧 smoke。

## 文档入口

- [CHANGELOG](./CHANGELOG.md)
- [贡献说明](./CONTRIBUTING.md)
- [安全政策](./SECURITY.md)
- [公开契约与稳定性承诺](./docs/公开契约与稳定性承诺.zh-CN.md)
- [查询能力矩阵](./docs/查询能力矩阵.zh-CN.md)
- [高价值 canonical 示例](./docs/高价值%20canonical%20示例.zh-CN.md)
- [发布检查清单](./docs/发布检查清单.zh-CN.md)
- [MIT License](./LICENSE)

如果你正在评估“是否应该把业务项目里的列表查询能力抽成 shared 包”，请优先阅读本文的选型摘要与边界说明；如果你已经准备接入，请继续阅读 canonical 示例。

## 选型摘要

这个包不是完整的 API 列表框架，也不是后端响应协议库，而是一个低业务耦合的 Eloquent 查询协议内核。

优先选择它的典型理由：

- 你有多个列表接口反复实现关键词搜索、筛选、区间筛选、排序和分页参数解析；
- 你希望用声明式 definition 管住字段白名单、排序方向、required filter、默认排序等查询能力；
- 你希望 shared core 只输出中性查询事实，让业务仓继续负责 response、异常、权限和分页执行；
- 你需要在多个 simple-framework / Eloquent 项目之间复用同一套查询协议和 regression；
- 你希望 relation 查询、派生筛选、keyword search 等行为被明确测试，而不是散落在 service 里。

不建议选择它的典型场景：

- 你的项目只需要单个临时列表，不存在复用或长期维护成本；
- 你希望它直接返回 `{ page, list }`、HTTP response 或业务错误码；
- 你希望把权限、租户隔离、业务默认范围、资源字段清单写进 shared core；
- 你需要的是 DTO / projection / 展示层重组能力；这应由投影层或业务 presenter 承接。

## shared 包与使用侧分工

shared 包负责：

- 外部输入解析为 `DslQueryInput / DslPageInput`；
- 查询能力声明对象；
- `search / filter / between / sort` 的读取与应用；
- filter values 与 pagination facts 等中性事实；
- `DslInputParser / DslInputMap / DslFilterNormalizer / DslPaginationPolicy` 等扩展契约。

使用侧负责：

- HTTP request 读取；
- 项目 validator / filter normalizer adapter；
- 项目异常翻译；
- `count / paginate / { page, list }` 等响应包装；
- 权限、租户、业务默认筛选等项目规则；
- 业务资源字段开放清单。

不要把以下内容直接推入 shared core：

- `App\` 命名空间下的业务项目代码；
- `simple-framework` 专属 adapter；
- `ApiException`、HTTP status、response envelope；
- 业务仓 `QueryPaginationTrait`；
- 旧 DSL compat runtime；
- 业务资源字段开放清单或权限语义。

## 30 秒最小示例

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
            'search' => ['title' => '校友会'],
            'filter' => ['status' => 'published'],
            'sort' => ['published_at' => 'desc'],
        ],
        'page' => ['page' => 1, 'limit' => 20],
    ])
    ->apply();

$query = $result->builder();
$pagination = $result->pagination();
$filterValues = $result->filterValues();
```

这个示例展示最小默认用法：

- 使用 `DslQueryDefinition` 声明允许的查询能力；
- 使用 `QueryDsl::for(...)->from(...)->apply()` 应用查询；
- 通过 `QueryDslResult` 读取 Builder、分页事实和 filter facts；
- 使用侧继续决定是否执行 `paginate()`、如何包装 response。

更多完整场景请见：[高价值 canonical 示例](./docs/高价值%20canonical%20示例.zh-CN.md)。

## 公开能力速查

本表只保留入口级判断；完整状态、语义、边界与规划说明请以 [查询能力矩阵](./docs/查询能力矩阵.zh-CN.md) 为准。

| 能力 | 当前状态 | 简短说明 |
| --- | --- | --- |
| `search[field => value]` | ✅ 已支持 | 字段级搜索；多个字段默认 AND；空字符串忽略 |
| `allowKeywordSearch()` | ✅ 已支持 | 一个 keyword 输入 OR 命中多个主实体字段；relation OR 搜索建议由 derived handler 承接 |
| `filter[field => value]` | ✅ 已支持 | 单值转 `where`，数组转 `whereIn`，空值忽略，`0` / `false` 视为有效值 |
| filter normalizer | ✅ 已支持 | 通过 `DslFilterNormalizer` 接入项目 validator / normalize 能力 |
| derived filter | ✅ 已支持 | 由字段定义绑定派生行为，命中后不再额外生成普通 where |
| `between[field => [start,end]]` | ✅ 已支持 | 区间筛选；只负责条件应用，不解释业务时间边界 |
| `sort` | ✅ 已支持 | 支持显式排序、默认排序、派生默认排序与排序方向策略 |
| relation search/filter | ✅ 已支持 | 通过 relation definition 映射后使用 `whereHas` |
| relation sort | ⛔ 暂不支持 | 当前明确阻断，避免隐式 join / group 语义不稳定 |
| pagination facts | ✅ 已支持 | 只输出受策略约束的 page / limit / export_limit 事实，不执行分页 |
| HTTP response | 🔵 Adapter | 由业务仓承接，不进入 shared core |
| 业务权限 / 租户隔离 | 🔵 Adapter | 由业务仓在 QueryDsl 前后显式处理 |

## 默认输入协议

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

这只是默认协议，不是 core 限制。若项目已有外部参数名，可用 `DslInputMap` 或自定义 `DslInputParser` 映射，不必把项目 HTTP 协议反向写进 shared core。

## 安装

正式发布到 Packagist 后使用：

```bash
composer require hongxunpan/eloquent-query-dsl
```

正式 tag 发布前，可通过 Git 仓库或 path repository 做开发期试用；生产项目建议锁定明确 commit / tag，不直接依赖浮动 `dev-main`。

## 验证

在共享 Composer 包容器路径内执行：

```bash
composer validate --strict
composer quality
```

`composer quality` 会串联：

- `composer test`：PHPUnit + legacy smoke；
- `composer analyse`：PHPStan level 8；
- `composer cs:check`：PHP-CS-Fixer dry-run + 轻量仓库卫生检查；
- `composer security:audit`：Composer 依赖安全审计。

共享测试支撑位于 `tests/Support/`，核心断言口径为：

1. 输入条件；
2. 生效 SQL；
3. bindings；
4. SQLite in-memory 实际结果集。

## CI

本仓提供 GitHub Actions 工作流：`.github/workflows/ci.yml`。

当前矩阵：

- PHP 8.0 + illuminate/database 9；
- PHP 8.1 + illuminate/database 10；
- PHP 8.2 + illuminate/database 11；
- PHP 8.3 + illuminate/database 12；
- Static analysis / PHPStan level 8。

准备 tag / GitHub Release / Packagist 发布前，必须先按 [发布检查清单](./docs/发布检查清单.zh-CN.md) 执行。
