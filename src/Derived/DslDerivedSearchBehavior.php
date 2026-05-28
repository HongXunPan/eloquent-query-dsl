<?php

namespace HongXunPan\EloquentQueryDsl\Derived;

use HongXunPan\EloquentQueryDsl\Condition\DslSearchCondition;
use Illuminate\Database\Eloquent\Builder;

/**
 * Query DSL 派生 search 行为。
 *
 * 该对象负责把某个已解析 search 条件翻译成真实 Builder 条件。
 */
interface DslDerivedSearchBehavior
{
    public function apply(Builder $query, DslSearchCondition $condition): void;
}
