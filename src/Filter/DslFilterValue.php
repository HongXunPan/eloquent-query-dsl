<?php

namespace HongXunPan\EloquentQueryDsl\Filter;

use HongXunPan\EloquentQueryDsl\Definition\DslQueryFieldDefinition;
use HongXunPan\EloquentQueryDsl\Field\DslFieldPath;

/**
 * QueryDSL V2 已解析 filter 值。
 *
 * 该对象用于给业务层读取已解析 filter 值；
 * 不负责 Builder 条件应用，也不作为匿名数组在业务层继续传递。
 */
class DslFilterValue
{
    protected DslQueryFieldDefinition $definition;
    protected array $values;

    private function __construct(DslQueryFieldDefinition $definition, array $values)
    {
        $this->definition = $definition;
        $this->values = array_values($values);
    }

    public static function fromDefinition(DslQueryFieldDefinition $definition, array $values): self
    {
        return new self($definition, $values);
    }

    public function definition(): DslQueryFieldDefinition
    {
        return $this->definition;
    }

    public function fieldPath(): DslFieldPath
    {
        return $this->definition->fieldPath();
    }

    public function canonicalField(): string
    {
        return $this->definition->canonical();
    }

    public function field(): string
    {
        return $this->definition->fieldPath()->field();
    }

    public function values(): array
    {
        return $this->values;
    }

    public function hasMeaningfulValue(): bool
    {
        return $this->values !== [];
    }

    public function singleValue(): mixed
    {
        return count($this->values) === 1 ? $this->values[0] : null;
    }

    public function queryValue(): mixed
    {
        if ($this->values === []) {
            return null;
        }

        if (count($this->values) === 1) {
            return $this->values[0];
        }

        return $this->values;
    }

    public function filterRule(): string
    {
        return $this->definition->filterRule();
    }
}
