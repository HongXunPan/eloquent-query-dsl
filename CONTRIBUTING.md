# 贡献说明

本文档说明 `hongxunpan/eloquent-query-dsl` 当前阶段的最小贡献方式与提交前约束。

当前仓库处于 **pre-1.0** 阶段，欢迎补文档、补测试、补开源工程化设施与通用 Query DSL 能力；但请先理解本包的 shared core 边界，不要把某个业务项目的 adapter、HTTP 响应、异常翻译或分页响应结构反向灌入 core。

---

## 1. 开始之前先看什么

建议按下面顺序建立上下文：

1. `README.md` / `README.zh-CN.md`
2. `docs/api-reference.md` / `docs/API能力与扩展点.zh-CN.md`
3. `docs/integration-guide.md` / `docs/接入指南.zh-CN.md`
4. `docs/public-contracts.md` / `docs/公开契约与稳定性承诺.zh-CN.md`
5. `docs/query-capability-matrix.md` / `docs/查询能力矩阵.zh-CN.md`
6. `docs/high-value-canonical-examples.md` / `docs/高价值 canonical 示例.zh-CN.md`
7. `CHANGELOG.md`
8. `docs/发布检查清单.zh-CN.md`
9. `composer.json`
10. `src/QueryDsl.php`
11. `src/QueryDslResult.php`
12. `src/Definition/DslQueryDefinition.php`
13. `src/Input/DslInputMap.php`
14. `src/Input/Contract/DslInputParser.php`
15. `src/Filter/Contract/DslFilterNormalizer.php`
16. `tests/`

如果改动涉及 public / internal 边界，请先确认 README、API Reference 与 Public Contracts 是否需要同步更新。公开文档默认需要维护中英文版本，避免英文 README 与中文说明出现能力差异。

---

## 2. 当前公开边界

### 2.1 推荐公开入口

pre-1.0 阶段推荐使用方优先依赖：

- `QueryDsl`
- `QueryDslResult`
- `Definition\DslQueryDefinition`
- `Input\DslInputMap`
- `Input\Contract\DslInputParser`
- `Filter\Contract\DslFilterNormalizer`
- `Filter\DslFilterValues`
- `Page\DslPaginationPolicy`
- `Page\DslPaginationRequest`

### 2.2 非稳定内部面

以下内容主要承接包内协作，当前不作为稳定 public API 承诺：

- `Reader/*`
- `Apply/*`
- `Section/*`
- `Kernel/*`
- `Input\Internal/*`

因此：

- 若你的改动影响推荐公开入口，必须同步 README 与 CHANGELOG；
- 若你的改动只发生在 internal 协作层，仍需保证包级 regression 通过；
- 若你希望把某个 internal 能力升级为 public API，请先补 README 中的边界说明。

---

## 3. shared 包与使用侧分工

shared 包负责：

- 外部输入解析为 `DslQueryInput / DslPageInput`；
- 查询能力声明对象；
- search / filter / between / sort 的读取与应用；
- filter values 与 pagination facts 等中性事实；
- `DslInputParser / DslInputMap / DslFilterNormalizer / DslPaginationPolicy` 等扩展契约。

使用侧负责：

- HTTP request 读取；
- 项目 validator / filter normalizer adapter；
- 项目异常翻译；
- count / paginate / `{ page, list }` 等响应包装；
- 权限、租户、业务默认筛选等项目规则。

不要把以下内容直接推入 shared core：

- `App\` 命名空间下的业务项目代码；
- `simple-framework` 专属 adapter；
- `ApiException`、HTTP status、response envelope；
- 业务仓 `QueryPaginationTrait`；
- 旧 DSL compat runtime；
- 业务资源字段开放清单或权限语义。

---

## 4. 如何跑验证

### 4.1 Composer 元数据

```bash
composer validate --strict
```

### 4.2 包级测试

```bash
composer test
```

当前 `composer test` 会串联：

- `composer test:phpunit`
- `composer test:legacy`

其中：

- `composer test:phpunit` 是正式 PHPUnit 分层测试；
- `composer test:legacy` 保留历史轻量 runner，作为兼容 smoke；
- legacy smoke 会继续串联 `test:skeleton / test:input-parser / test:query-dsl / test:core`。

### 4.3 CI 行为

当前 GitHub Actions 默认覆盖：

- PHP 8.0 + illuminate/database 9
- PHP 8.1 + illuminate/database 10
- PHP 8.2 + illuminate/database 11
- PHP 8.3 + illuminate/database 12

并执行：

```bash
composer validate --strict
composer require illuminate/database:<matrix-version> --no-update --no-interaction
composer update --no-interaction --prefer-dist --no-progress
composer test
composer cs:check
composer security:audit
composer analyse
```

---

## 5. 改动同步要求

### 5.1 必须同步 README / docs 的场景

- 新增推荐公开入口：同步 `README.md`、`README.zh-CN.md`、API Reference 与 Public Contracts；
- 修改推荐接入方式：同步 Integration Guide 与 canonical examples；
- 修改 shared 包与使用侧分工：同步 README、Public Contracts 与 Release Checklist；
- 改变输入协议或扩展契约：同步 API Reference、Integration Guide、Capability Matrix 与测试；
- 新增查询能力或调整能力状态：同步 Capability Matrix 的中英文版本。

### 5.2 必须同步 CHANGELOG 的场景

- 新增公开能力；
- 修改公开行为；
- 修复关键 bug；
- 新增重要工程化设施。

### 5.3 必须补测试的场景

- 新增 section 行为；
- 修改 input parser 行为；
- 修改 filter normalizer 接缝；
- 修改 sort / search / between / relation / strict 行为；
- 修改异常边界。

测试建议继续围绕：

1. 输入条件；
2. 生效 SQL；
3. bindings；
4. SQLite in-memory 实际结果集。

---

## 6. 发布前检查

准备 tag / GitHub Release / Packagist 发布时，必须按源码仓库中的发布检查清单执行：

- `docs/发布检查清单.zh-CN.md`

该清单是发布前固定入口，不用聊天记录替代。

## 7. 提交前检查清单

提交前建议至少检查：

- [ ] `composer validate --strict` 通过
- [ ] `composer test` 通过
- [ ] `composer analyse` 通过
- [ ] `composer cs:check` 通过
- [ ] `composer security:audit` 通过
- [ ] 没有把 `vendor/`、`.idea/`、本地缓存带进仓库
- [ ] 若改了公开能力，README 与核心 docs 中英文版本已同步
- [ ] 若改了对外可见行为，CHANGELOG 已同步
- [ ] 没有把业务项目 adapter / helper / compat runtime 推回 shared core
