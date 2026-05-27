<?php

namespace HongXunPan\EloquentQueryDsl\Derived;

use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslDefinitionException;
use Illuminate\Database\Eloquent\Builder;

/**
 * QueryDSL V2 派生 filter：relation 存在规则。
 */
class DslHasRelationDerivedFilterRule implements DslDerivedFilterRule
{
    protected string $relation;

    private function __construct(string $relation)
    {
        $this->relation = $relation;
    }

    public static function forRelation(string $relation): self
    {
        $relation = trim($relation);
        if ($relation === '') {
            throw DslQueryDslDefinitionException::fromMessage('query dsl 派生筛选 relation 不能为空');
        }

        return new self($relation);
    }

    public function apply(Builder $query): void
    {
        $query->whereHas($this->relation);
    }
}
