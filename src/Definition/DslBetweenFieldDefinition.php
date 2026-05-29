<?php

namespace HongXunPan\EloquentQueryDsl\Definition;

use HongXunPan\EloquentQueryDsl\Field\DslFieldPath;

/**
 * between section 字段定义。
 *
 * 当前 between 暂无额外配置；单独成类是为了保持 section 边界一致。
 */
class DslBetweenFieldDefinition extends DslQueryFieldDefinition
{
    public static function make(DslFieldPath $fieldPath): self
    {
        return new self($fieldPath);
    }
}
