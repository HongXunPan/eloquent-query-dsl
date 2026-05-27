<?php

namespace HongXunPan\EloquentQueryDsl\Condition;

use HongXunPan\EloquentQueryDsl\Field\DslFieldPath;

/**
 * QueryDSL V2 筛选条件。
 *
 * 只描述筛选字段和值，不执行规则校验或 Builder 条件拼装。
 */
class DslFilterCondition extends DslFieldCondition
{
    public const SECTION = 'filter';

    protected function __construct(DslFieldPath $fieldPath, mixed $value)
    {
        parent::__construct(self::SECTION, $fieldPath, $value);
    }

    public static function fromField(DslFieldPath $fieldPath, mixed $value): self
    {
        return new self($fieldPath, $value);
    }
}
