<?php

namespace HongXunPan\EloquentQueryDsl\Filter;

use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslException;

/**
 * QueryDSL V2 已解析 filter 值集合。
 *
 * 业务层通过该集合读取 filter 值，不再从匿名数组中取 required / rule / value。
 */
class DslFilterValues
{
    /**
     * @var array<string, DslFilterValue>
     */
    protected array $values = [];

    public function put(DslFilterValue $value): void
    {
        $this->values[$value->canonicalField()] = $value;
    }

    public function get(string $field): ?DslFilterValue
    {
        $field = trim($field);
        if ($field === '') {
            return null;
        }

        if (isset($this->values[$field])) {
            return $this->values[$field];
        }

        if (!str_contains($field, '.')) {
            foreach ($this->values as $value) {
                if ($value->field() === $field) {
                    return $value;
                }
            }
        }

        return null;
    }

    public function require(string $field): DslFilterValue
    {
        $value = $this->get($field);
        if ($value === null) {
            throw DslQueryDslException::invalidQuery('query.filter 未解析字段：' . $field);
        }

        return $value;
    }

    public function has(string $field): bool
    {
        return $this->get($field) !== null;
    }

    public function singleValue(string $field): mixed
    {
        return $this->get($field)?->singleValue();
    }

    public function queryValue(string $field): mixed
    {
        return $this->get($field)?->queryValue();
    }

    public function values(string $field): array
    {
        return $this->get($field)?->values() ?? [];
    }

    /**
     * @return array<string, DslFilterValue>
     */
    public function all(): array
    {
        return $this->values;
    }
}
