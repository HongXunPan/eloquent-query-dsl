<?php

namespace HongXunPan\EloquentQueryDsl\Condition;

use HongXunPan\EloquentQueryDsl\Field\DslFieldPath;

/**
 * Query DSL 通用字段条件。
 *
 * 用于承接“某个 section 下某个字段对应一个值”的条件事实。
 */
class DslFieldCondition extends DslCondition
{
    protected mixed $value;

    protected function __construct(string $sectionName, DslFieldPath $fieldPath, mixed $value)
    {
        parent::__construct($sectionName, $fieldPath);
        $this->value = $value;
    }

    public static function make(string $sectionName, DslFieldPath $fieldPath, mixed $value): self
    {
        return new self($sectionName, $fieldPath, $value);
    }

    /**
     * 返回条件值。
     */
    public function value(): mixed
    {
        return $this->value;
    }
}
