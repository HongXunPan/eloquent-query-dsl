<?php

namespace HongXunPan\EloquentQueryDsl\Derived;

use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefaultSort;
use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslDefinitionException;
use HongXunPan\EloquentQueryDsl\Filter\DslFilterValue;

/**
 * Query DSL 基于 filter 值的派生默认排序策略。
 *
 * 该对象只根据当前 filter 的单值结果返回一组默认排序定义。
 */
class DslFilterValueDerivedDefaultSortStrategy implements DslDerivedDefaultSortStrategy
{
    /**
     * @var array<string, DslQueryDefaultSort[]>
     */
    protected array $sortsByValue = [];

    public static function make(): self
    {
        return new self();
    }

    public function when(mixed $value, DslQueryDefaultSort ...$sorts): self
    {
        if ($sorts === []) {
            throw DslQueryDslDefinitionException::fromMessage('query dsl 派生默认排序至少需要一个排序项');
        }

        $this->sortsByValue[$this->normalizeCaseKey($value)] = array_values($sorts);

        return $this;
    }

    public function resolve(DslFilterValue $filterValue): array
    {
        $value = $filterValue->singleValue();
        if ($value === null) {
            return [];
        }

        return $this->sortsByValue[$this->normalizeCaseKey($value)] ?? [];
    }

    protected function normalizeCaseKey(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            throw DslQueryDslDefinitionException::fromMessage('query dsl 派生默认排序协议值必须是标量');
        }

        return (string)$value;
    }
}
