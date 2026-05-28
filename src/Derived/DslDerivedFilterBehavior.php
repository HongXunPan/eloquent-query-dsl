<?php

namespace HongXunPan\EloquentQueryDsl\Derived;

use HongXunPan\EloquentQueryDsl\Filter\DslFilterValue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Query DSL 派生 filter 行为。
 *
 * 该对象负责把某个已解析 filter 值翻译成真实 Builder 条件；
 * 可由规则映射或自定义 handler 承接。
 */
interface DslDerivedFilterBehavior
{
    /**
     * @template TModel of Model
     * @param Builder<TModel> $query
     */
    public function apply(Builder $query, DslFilterValue $filterValue): void;
}
