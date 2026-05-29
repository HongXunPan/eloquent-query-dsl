<?php

namespace HongXunPan\EloquentQueryDsl\Definition;

use HongXunPan\EloquentQueryDsl\Field\DslFieldPath;

/**
 * Query DSL 字段能力基础定义。
 *
 * 该对象只承接字段路径；search / filter / between / sort 的专属配置
 * 必须下沉到各自 section 的 FieldDefinition，避免不同 section 的职责混杂。
 */
class DslQueryFieldDefinition
{
    protected DslFieldPath $fieldPath;

    protected function __construct(DslFieldPath $fieldPath)
    {
        $this->fieldPath = $fieldPath;
    }

    public static function make(DslFieldPath $fieldPath): self
    {
        return new self($fieldPath);
    }

    public function fieldPath(): DslFieldPath
    {
        return $this->fieldPath;
    }

    public function canonical(): string
    {
        return $this->fieldPath->canonical();
    }
}
