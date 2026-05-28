# 更新日志

本文档记录 `hongxunpan/eloquent-query-dsl` 的重要变更。

当前项目处于 **pre-1.0** 阶段，尚未承诺 `1.x` 级别的长期兼容性；正式 tag / Packagist 发布前，请以明确 commit 或 tag 锁定依赖。

格式参考 Keep a Changelog，但在当前阶段保持轻量。

## [Unreleased]

### Added

- 初始化 Composer 包 `hongxunpan/eloquent-query-dsl`。
- 建立命名空间 `HongXunPan\EloquentQueryDsl\` 与包标识类 `Package`。
- 增加 QueryDSL V2 core 对象：
  - Exception / Field / Condition；
  - Definition / Derived；
  - Input / Page；
  - Filter value facts；
  - Reader / Apply / Section / Kernel。
- 增加中性的 filter normalize 契约与 DTO：
  - `Filter\Contract\DslFilterNormalizer`；
  - `Filter\Value\DslNormalizedFilterSet`；
  - `Filter\Value\DslNormalizedFilterItem`。
- 增加开源友好的主入口：
  - `QueryDsl`；
  - `QueryDslResult`。
- 增加输入协议扩展能力：
  - `DslInputMap`；
  - `DslInputParser`；
  - `DefaultDslInputParser`。
- 支持默认 `query` 包裹协议、自定义参数名、flat 输入与完全自定义 parser。
- 增加分页输入事实对象：
  - `DslPageInput`；
  - `DslPaginationRequest`。
- 增加包级 regression：
  - `tests/TestRunner.php`；
  - `tests/InputParserRegression.php`；
  - `tests/QueryDslRegression.php`；
  - `tests/CoreRegression.php`。
- 增加 GitHub Actions CI，覆盖 PHP 8.0 / 8.2 / 8.3。
- 补齐 MIT `LICENSE`。
- 增加开源协作文件：
  - `CHANGELOG.md`；
  - `CONTRIBUTING.md`；
  - `SECURITY.md`；
  - `.gitattributes`；
  - `.editorconfig`。
- 增加 PHPStan 静态分析配置与 `composer analyse`。
- 增加轻量代码风格检查脚本与 `composer cs:check`。
- 增加 `composer quality`，串联 test / analyse / cs:check。

### Changed

- 将 backend QueryDSL V2 shared stable core 从业务仓抽离到共享 Composer 包。
- 将 backend validator bridge 保留在业务仓，只通过中性的 `DslFilterNormalizer` 与共享包对接。
- 将 README 从 skeleton / 草案口径调整为 pre-1.0 开源预备发布口径。
- 明确 shared 包只返回中性查询事实，不承接 HTTP response、异常翻译、分页执行或 `{ page, list }` 响应结构。
- CI 已接入 `composer validate --strict`、`composer test`、`composer cs:check` 与静态分析 job。

### Not included

- 不包含 `App\` 命名空间下的业务项目适配代码。
- 不包含 `simple-framework` 专属 adapter。
- 不包含 backend `QueryPaginationTrait`。
- 不包含旧 DSL compat runtime。
- 不包含业务资源字段开放清单、权限语义或页面返回结构。
