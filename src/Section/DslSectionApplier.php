<?php

namespace HongXunPan\EloquentQueryDsl\Section;

use HongXunPan\EloquentQueryDsl\Input\DslQueryRequestContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Query DSL section 应用器契约。
 *
 * Kernel 只依赖该契约按顺序编排 section；
 * 具体 section 的输入解析、条件生成与 Builder 应用由各自实现类承担。
 *
 * @internal
 */
interface DslSectionApplier
{
    /**
     * 将当前 section 能力应用到 Builder。
     *
     * @param Builder<Model> $query
     */
    public function apply(Builder $query, DslQueryRequestContext $context): void;
}
