<?php

namespace HongXunPan\EloquentQueryDsl;

use HongXunPan\EloquentQueryDsl\Filter\DslFilterValues;
use HongXunPan\EloquentQueryDsl\Input\DslQueryRequestContext;
use HongXunPan\EloquentQueryDsl\Page\DslPaginationRequest;
use Illuminate\Database\Eloquent\Builder;

/**
 * QueryDSL 一次应用后的中性结果。
 *
 * 该对象只暴露 builder、context、filter values 与 pagination 等事实，
 * 不负责 count、HTTP response、分页响应结构或业务异常翻译。
 */
class QueryDslResult
{
    public function __construct(
        private Builder $builder,
        private DslQueryRequestContext $context,
    ) {
    }

    public function builder(): Builder
    {
        return $this->builder;
    }

    public function context(): DslQueryRequestContext
    {
        return $this->context;
    }

    public function filterValues(): DslFilterValues
    {
        return $this->context->filterValues() ?? new DslFilterValues();
    }

    public function pagination(): DslPaginationRequest
    {
        return $this->context->paginationRequest();
    }
}
