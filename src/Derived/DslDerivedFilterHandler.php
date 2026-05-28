<?php

namespace HongXunPan\EloquentQueryDsl\Derived;

use Closure;
use HongXunPan\EloquentQueryDsl\Filter\DslFilterValue;
use Illuminate\Database\Eloquent\Builder;

/**
 * Query DSL 派生 filter 自定义处理器。
 *
 * 适用于需要结合业务上下文翻译查询的复杂筛选。
 */
class DslDerivedFilterHandler implements DslDerivedFilterBehavior
{
    protected Closure $handler;

    private function __construct(callable $handler)
    {
        $this->handler = Closure::fromCallable($handler);
    }

    public static function make(callable $handler): self
    {
        return new self($handler);
    }

    public function apply(Builder $query, DslFilterValue $filterValue): void
    {
        ($this->handler)(
            $query,
            $filterValue->queryValue(),
            $filterValue->canonicalField()
        );
    }
}
