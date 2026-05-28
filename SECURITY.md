# 安全政策

本文档说明 `hongxunpan/eloquent-query-dsl` 的安全问题反馈方式。

## 当前支持范围

当前项目处于 **pre-1.0** 阶段，尚未发布稳定 `1.x` 兼容承诺。

安全问题优先覆盖：

- 可能导致 SQL 注入的查询构建问题；
- 字段白名单绕过；
- strict 模式绕过；
- 输入解析导致的异常绕过或危险查询；
- filter normalizer 接缝导致的未知字段误放行。

以下问题通常不视为本包安全漏洞：

- 使用侧未声明合理字段白名单；
- 使用侧在 derived handler 中手写不安全 SQL；
- 使用侧绕过 `QueryDsl / DslQueryDefinition` 直接拼接 Builder；
- 业务项目 adapter、权限、租户隔离或 HTTP response 实现问题。

## 如何报告

请不要先公开披露疑似安全问题。

可以通过以下方式联系维护者：

- Email：`me@kangxuanpeng.com`
- GitHub Security Advisory：若仓库已启用，请优先使用 GitHub 的私密安全报告入口。

报告时建议包含：

1. 受影响版本或 commit；
2. 最小复现代码；
3. 输入参数；
4. 预期 SQL / 实际 SQL；
5. 影响范围判断；
6. 是否已有临时规避方案。

## 处理原则

- 确认问题后，会优先补 regression；
- 若问题影响公开版本，会在修复后发布新 tag；
- 若问题仅影响未发布的开发分支，会在 CHANGELOG 的 Unreleased 中记录；
- 业务项目 adapter 问题会建议在对应业务仓处理，不直接升格为 shared core 漏洞。

