<?php

namespace HongXunPan\EloquentQueryDsl\Kernel;

/**
 * 旧命名内核兼容入口。
 *
 * pre-1.0 阶段保留该类只为降低已接入方迁移成本；新代码应通过 QueryDsl 主入口，
 * 或在包内协作层使用 QueryDslKernel。
 *
 * @internal
 */
class QueryDslV2Kernel extends QueryDslKernel
{
}
