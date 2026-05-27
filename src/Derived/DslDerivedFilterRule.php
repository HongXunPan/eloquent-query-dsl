<?php

namespace HongXunPan\EloquentQueryDsl\Derived;

use Illuminate\Database\Eloquent\Builder;

/**
 * QueryDSL V2 派生 filter 单条规则。
 *
 * 该对象只负责把一个已选中的派生规则作用到 Builder。
 */
interface DslDerivedFilterRule
{
    public function apply(Builder $query): void;
}
