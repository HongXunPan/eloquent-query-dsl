# Eloquent Query DSL

[English README](./README.md)

`hongxunpan/eloquent-query-dsl` 是一个面向 Eloquent / Builder 的列表查询 DSL core，用于把列表接口中反复出现的 `search / filter / between / sort / page` 查询协议收口为可声明、可测试、可复用的查询内核。

它不负责 HTTP request、response envelope、业务异常、分页响应结构、授权、租户规则或资源字段策略；这些能力应留在应用 adapter 或 service 层。

## 当前状态

当前仓库处于 **pre-1.0** 开发阶段。

已完成：

- Composer 包与命名空间 `HongXunPan\EloquentQueryDsl\`；
- 推荐入口：`QueryDsl` / `QueryDslResult`；
- 查询能力声明模型：`DslQueryDefinition`；
- 默认输入解析、自定义参数名、flat 输入与完全自定义 parser 契约；
- `search / filter / between / sort` 读取与 Builder 应用；
- 中性的 filter normalizer 契约与 filter values facts；
- 字段、实体别名与 relation 名的安全 identifier 校验；
- 分页事实与分页策略上限；
- PHPUnit、legacy smoke、PHPStan level 8、PHP-CS-Fixer、Composer audit 与 GitHub Actions 矩阵；
- 发布检查清单与 `0.1.0` 待发布记录。

公开发布前仍需完成：

- 最终提交与远端 CI 观察；
- `0.1.0` tag 与 GitHub Release；
- Packagist 同步；
- 应用侧基于明确 tag 接入并执行项目 smoke。

## 文档入口

- [CHANGELOG](./CHANGELOG.md)
- [贡献说明](./CONTRIBUTING.md)
- [安全政策](./SECURITY.md)
- [公开契约与稳定性承诺](./docs/公开契约与稳定性承诺.zh-CN.md)
- [API 能力与扩展点](./docs/API能力与扩展点.zh-CN.md)
- [接入指南](./docs/接入指南.zh-CN.md)
- [查询能力矩阵](./docs/查询能力矩阵.zh-CN.md)
- [高价值 canonical 示例](./docs/高价值%20canonical%20示例.zh-CN.md)
- [发布检查清单](./docs/发布检查清单.zh-CN.md)
- [MIT License](./LICENSE)

如果你正在评估“是否应该把列表查询抽成 shared Composer 包”，先读下面的选型摘要和边界说明；如果你已经准备接入，请阅读接入指南和 canonical 示例。

## 选型摘要

这个包不是 API 框架、repository 层或响应格式库，而是一个低业务耦合的 Eloquent 列表查询协议内核。

优先选择它的典型理由：

- 你在多个列表接口中反复实现关键词搜索、字段筛选、区间筛选、排序和分页输入；
- 你希望用声明式 definition 管住字段白名单、排序方向、required filter、默认排序和 strict 模式；
- 你希望 shared core 只返回中性查询事实，让应用代码继续负责 response、异常、权限和分页执行；
- 你需要在多个 Eloquent 项目间复用同一套查询协议和 regression；
- 你希望 relation search/filter、derived filter、keyword search、默认排序等行为明确且可测试。

不建议选择它的典型场景：

- 你只有一个临时列表接口，不存在复用或长期维护压力；
- 你希望它直接返回 `{ page, list }`、HTTP response 或业务错误码；
- 你希望把权限、租户隔离、资源字段策略或业务默认范围放进 shared core；
- 你需要 DTO / projection / 展示层重组能力；这应由 projection 层或应用 presenter 承接。

## Core 与应用侧分工

shared core 负责：

- 外部输入解析为 `DslQueryInput / DslPageInput`；
- 查询能力声明；
- 把 `search / filter / between / sort` 应用到 Eloquent Builder；
- 中性 filter values 与 pagination facts；
- `DslInputParser / DslInputMap / DslFilterNormalizer / DslPaginationPolicy` 等扩展契约。

应用代码负责：

- HTTP request 读取；
- 把应用 validator / normalize 能力适配为 `DslFilterNormalizer`；
- 异常翻译；
- 执行 `count`、`paginate`、cursor pagination、导出限制或响应包装；
- 授权、租户隔离、业务默认 scope；
- 资源级字段开放清单。

不要把以下内容推入 shared core：

- `App\` 命名空间代码；
- 框架专属 adapter；
- `ApiException`、HTTP status、response envelope；
- 应用分页 trait；
- legacy 兼容 runtime；
- 资源权限或字段策略。

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
            'search' => ['title' => '校友'],
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

这个示例展示最小默认路径：

- 通过 `DslQueryDefinition` 声明允许的查询行为；
- 通过 `QueryDsl::for(...)->from(...)->apply()` 应用输入；
- 从 `QueryDslResult` 读取 Builder 和中性 facts；
- 应用代码自行执行分页和返回响应。

更多场景请见：[高价值 canonical 示例](./docs/高价值%20canonical%20示例.zh-CN.md)。

## Public API 速查

本表只保留入口判断；职责、签名、扩展点和示例请见 [API 能力与扩展点](./docs/API能力与扩展点.zh-CN.md)。

| API | 作用 |
| --- | --- |
| `QueryDsl` | 推荐入口，编排 Builder、definition、input、扩展点并返回 `QueryDslResult` |
| `QueryDslResult` | 持有已应用查询的 Builder、filter values 与 pagination facts |
| `DslQueryDefinition` | 声明 search、filter、between、sort、relation、默认排序、strict 与 derived 行为 |
| `DslInputMap` | 映射外部参数名，不要求调用方 API 形态跟随默认 Query DSL 协议 |
| `DslInputParser` | 完全自定义输入解析契约 |
| `DslFilterNormalizer` | 应用 validator / normalize 能力接入 filter facts 的中性桥 |
| `DslPaginationPolicy` | 控制 page / limit / export_limit 归一化与上限 |
| `DslPaginationRequest` | 中性分页事实；不执行分页 |

## 能力速查

完整状态、行为、adapter 边界与不受理原因请见 [查询能力矩阵](./docs/查询能力矩阵.zh-CN.md)。

| 能力 | 状态 | 简短说明 |
| --- | --- | --- |
| 字段 search | 已支持 | `search[field => value]`；多个字段 AND；空字符串忽略 |
| keyword search | 已支持 | 一个输入字段 OR 命中多个主实体字段 |
| filter | 已支持 | 标量转 `where`；数组转 `whereIn`；空值忽略；`0` / `false` 有意义 |
| filter rules | 已支持 | `allowFilter()` 声明查询能力；`filterRules()` 声明 `field.*` 等校验 / 归一化规则 |
| filter normalizer | 已支持 | 通过 `DslFilterNormalizer` 接入应用校验 / 归一化 |
| derived filter/search | 已支持 | 为应用特定查询行为提供显式扩展点 |
| between | 已支持 | 应用 `whereBetween`；业务时间边界策略留在 core 外 |
| sort | 已支持 | 显式排序、默认排序、派生默认排序和方向策略 |
| relation search/filter | 已支持 | 基于声明的 relation 映射和 relation scope |
| relation sort | 不支持 | 明确阻断，避免隐式 join / group 语义 |
| pagination facts | 已支持 | 只返回归一化事实；应用代码执行分页 |
| HTTP response | Adapter | 应用侧职责 |
| 授权 / 租户 | Adapter | 应用侧职责 |

## 默认输入形态

默认输入形态使用 `query` 包裹结构：

```php
$params = [
    'query' => [
        'search' => ['title' => '校友'],
        'filter' => ['status' => 'published'],
        'between' => ['created_at' => ['2026-01-01', '2026-12-31']],
        'sort' => [
            ['field' => 'published_at', 'order' => 'desc'],
        ],
    ],
    'page' => ['page' => 1, 'limit' => 20],
];
```

这只是默认协议，不是 core 限制。若你的公开 API 已有不同参数名，请使用 `DslInputMap` 或 `DslInputParser`。

## 安装

发布到 Packagist 后使用：

```bash
composer require hongxunpan/eloquent-query-dsl
```

稳定 tag 前可通过 VCS repository 或 path repository 评估。生产应用应锁定明确 commit 或 tag，不直接依赖浮动 `dev-main`。

## 验证

在包目录内执行：

```bash
composer validate --strict
composer quality
```

`composer quality` 会执行：

- `composer test`：PHPUnit + legacy smoke；
- `composer analyse`：PHPStan level 8；
- `composer cs:check`：PHP-CS-Fixer dry-run + 轻量仓库卫生检查；
- `composer security:audit`：Composer 依赖安全审计。

## CI

仓库提供 GitHub Actions 工作流：`.github/workflows/ci.yml`。

当前矩阵：

- PHP 8.0 + illuminate/database 9；
- PHP 8.1 + illuminate/database 10；
- PHP 8.2 + illuminate/database 11；
- PHP 8.3 + illuminate/database 12；
- PHPStan level 8 静态分析。

打 tag 或发布前，必须先执行 [发布检查清单](./docs/发布检查清单.zh-CN.md)。
