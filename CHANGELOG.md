# 更新日志

本文档记录 `hongxunpan/eloquent-query-dsl` 的重要变更。

当前项目处于 **pre-1.0** 阶段，尚未承诺 `1.x` 级别的长期兼容性；正式 tag / Packagist 发布前，请以明确 commit 或 tag 锁定依赖。

格式参考 Keep a Changelog，但在当前阶段保持轻量。

## [Unreleased]

### Documentation

- README 调整为英文主入口，并新增 `README.zh-CN.md` 中文入口。
- 新增双语核心文档：
  - `docs/api-reference.md` / `docs/API能力与扩展点.zh-CN.md`；
  - `docs/integration-guide.md` / `docs/接入指南.zh-CN.md`；
  - `docs/public-contracts.md` / `docs/公开契约与稳定性承诺.zh-CN.md`；
  - `docs/query-capability-matrix.md` / `docs/查询能力矩阵.zh-CN.md`；
  - `docs/high-value-canonical-examples.md` / `docs/高价值 canonical 示例.zh-CN.md`。
- 明确开源使用视角下的 API 能力、扩展点、接入步骤和 adapter 边界，避免依赖业务项目约定俗成。
- CONTRIBUTING 与发布检查清单增加双语文档同步要求。

## [0.1.0] - 待发布

### Added

- 初始化 Composer 包 `hongxunpan/eloquent-query-dsl`。
- 建立命名空间 `HongXunPan\EloquentQueryDsl\` 与包标识类 `Package`。
- 增加 Query DSL core 对象：
  - Exception / Field / Condition；
  - Definition / Derived；
  - Input / Page；
  - Filter value facts；
  - Reader / Apply / Section / Kernel。
- 增加中性的 `Kernel\QueryDslKernel`，旧 `QueryDslV2Kernel` 仅作为 internal 兼容入口保留。
- 增加中性的 filter normalize 契约与 DTO：
  - `Filter\Contract\DslFilterNormalizer`；
  - `Filter\Value\DslNormalizedFilterSet`；
  - `Filter\Value\DslNormalizedFilterItem`。
- 增加开源友好的主入口：
  - `QueryDsl`；
  - `QueryDslResult`。
- 增加关键词搜索声明 `allowKeywordSearch()`，支持一个 search 输入字段以 OR 分组命中多个主实体字段。
- 增加输入协议扩展能力：
  - `DslInputMap`；
  - `DslInputParser`；
  - `DefaultDslInputParser`。
- 支持默认 `query` 包裹协议、自定义参数名、flat 输入与完全自定义 parser。
- 增加分页输入事实对象：
  - `DslPageInput`；
  - `DslPaginationPolicy`；
  - `DslPaginationRequest`。
- 增加普通字段、实体别名与 relation 名的安全 identifier 校验，默认禁止 raw SQL、表达式和多级字段路径伪装成普通字段。
- 增加包级 regression：
  - `tests/TestRunner.php`；
  - `tests/InputParserRegression.php`；
  - `tests/QueryDslRegression.php`；
  - `tests/CoreRegression.php`。
- 增加 GitHub Actions CI，覆盖 PHP 8.0 / Illuminate 9、PHP 8.1 / Illuminate 10、PHP 8.2 / Illuminate 11、PHP 8.3 / Illuminate 12。
- 补齐 MIT `LICENSE`。
- 增加开源协作文件：
  - `CHANGELOG.md`；
  - `CONTRIBUTING.md`；
  - `SECURITY.md`；
  - `.gitattributes`；
  - `.editorconfig`。
- 增加 PHPStan 静态分析配置与 `composer analyse`。
- 增加 PHPUnit 9.6、`phpunit.xml.dist` 与 Unit / Feature 分层测试。
- 增加 PHP-CS-Fixer 配置、轻量代码风格检查脚本与 `composer cs:check`。
- 增加 `composer cs:fix`，用于本地自动修复 PHP 代码风格。
- 增加 `composer security:audit`，用于依赖安全审计。
- 增加 `composer quality`，串联 test / analyse / cs:check / security:audit。
- 增加专业化 docs 入口：
  - `docs/公开契约与稳定性承诺.zh-CN.md`；
  - `docs/查询能力矩阵.zh-CN.md`；
  - `docs/高价值 canonical 示例.zh-CN.md`；
  - `docs/发布检查清单.zh-CN.md`。

### Changed

- 将 Query DSL shared core 收口到共享 Composer 包。
- 将业务项目 validator bridge 保留在使用侧，只通过中性的 `DslFilterNormalizer` 与共享包对接。
- `QueryDsl` 主入口改用中性 `QueryDslKernel`，不再依赖带历史阶段语义的内核命名。
- README 明确 search / filter / sort 语义边界，包括字段级 search 的 AND 语义、keyword search 的 OR 语义、filter 空值行为与 sort 优先级。
- `composer test` 改为先执行 PHPUnit 分层测试，再执行 legacy smoke。
- PHPStan 从 level 3 提升到 level 8，并补齐 public API、内部数组与测试夹具类型标注。
- 将 README 从 skeleton / 草案口径调整为 pre-1.0 开源预备发布口径。
- 明确 shared 包只返回中性查询事实，不承接 HTTP response、异常翻译、分页执行或 `{ page, list }` 响应结构。
- `DslPaginationRequest` 现在通过 `DslPaginationPolicy` 输出受限分页事实，默认限制 `limit` 与 `export_limit` 上限。
- CI 已接入 `composer validate --strict`、`composer test`、`composer cs:check`、`composer security:audit`、PHPStan level 8 与 Illuminate 版本矩阵。
- README 改为入口级专业说明，详细契约、能力矩阵与 canonical 示例下沉到 `docs/`。

### Not included

- 不包含 `App\` 命名空间下的业务项目适配代码。
- 不包含 `simple-framework` 专属 adapter。
- 不包含业务仓 `QueryPaginationTrait`。
- 不包含旧 DSL compat runtime。
- 不包含业务资源字段开放清单、权限语义或页面返回结构。
