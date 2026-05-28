<?php

namespace HongXunPan\EloquentQueryDsl\Derived;

use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslDefinitionException;
use HongXunPan\EloquentQueryDsl\Filter\DslFilterValue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Query DSL 派生 filter 规则映射。
 *
 * 用“协议值 => 规则对象”的方式承接旧版 derived filter rule map，
 * 但内部不再暴露 type / field / relation 这类匿名数组约定。
 */
class DslDerivedFilterRuleMap implements DslDerivedFilterBehavior
{
    /**
     * @var array<string, DslDerivedFilterRule>
     */
    protected array $rules = [];

    public static function make(): self
    {
        return new self();
    }

    public function when(mixed $value, DslDerivedFilterRule $rule): self
    {
        $this->rules[$this->normalizeCaseKey($value)] = $rule;

        return $this;
    }

    /**
     * @param Builder<Model> $query
     */
    public function apply(Builder $query, DslFilterValue $filterValue): void
    {
        $value = $filterValue->singleValue();
        if ($value === null) {
            return;
        }

        $rule = $this->rules[$this->normalizeCaseKey($value)] ?? null;
        if ($rule === null) {
            return;
        }

        $rule->apply($query);
    }

    protected function normalizeCaseKey(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            throw DslQueryDslDefinitionException::fromMessage('query dsl 派生筛选协议值必须是标量');
        }

        return (string)$value;
    }
}
