<?php

namespace HongXunPan\EloquentQueryDsl\Derived;

use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefaultSort;
use HongXunPan\EloquentQueryDsl\Filter\DslFilterValue;

/**
 * Query DSL 派生默认排序策略。
 *
 * 该对象只根据某个已解析 filter 值决定默认排序列表；
 * 不读取 query 输入，也不直接修改 Builder。
 */
interface DslDerivedDefaultSortStrategy
{
    /**
     * @return DslQueryDefaultSort[]
     */
    public function resolve(DslFilterValue $filterValue): array;
}
