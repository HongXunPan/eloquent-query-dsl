<?php

namespace HongXunPan\EloquentQueryDsl\Derived;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Query DSL 派生 filter 单条规则。
 *
 * 该对象只负责把一个已选中的派生规则作用到 Builder。
 */
interface DslDerivedFilterRule
{
    /**
     * @template TModel of Model
     * @param Builder<TModel> $query
     */
    public function apply(Builder $query): void;
}
