<?php

namespace HongXunPan\EloquentQueryDsl\Derived;

use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslDefinitionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Query DSL 派生 filter：字段为空规则。
 */
class DslNullColumnDerivedFilterRule implements DslDerivedFilterRule
{
    protected string $field;

    private function __construct(string $field)
    {
        $this->field = $field;
    }

    public static function forField(string $field): self
    {
        $field = trim($field);
        if ($field === '') {
            throw DslQueryDslDefinitionException::fromMessage('query dsl 派生筛选字段不能为空');
        }

        return new self($field);
    }

    /**
     * @template TModel of Model
     * @param Builder<TModel> $query
     */
    public function apply(Builder $query): void
    {
        $query->whereNull($query->qualifyColumn($this->field));
    }
}
