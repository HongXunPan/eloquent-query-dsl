<?php

namespace HongXunPan\EloquentQueryDsl\Definition;

use HongXunPan\EloquentQueryDsl\Field\DslFieldPath;

/**
 * sort section 字段定义。
 */
class DslSortFieldDefinition extends DslQueryFieldDefinition
{
    protected DslSortDirectionPolicy $sortDirectionPolicy;

    protected function __construct(DslFieldPath $fieldPath)
    {
        parent::__construct($fieldPath);
        $this->sortDirectionPolicy = DslSortDirectionPolicy::any();
    }

    public static function make(DslFieldPath $fieldPath): self
    {
        return new self($fieldPath);
    }

    public function sortDirectionPolicy(): DslSortDirectionPolicy
    {
        return $this->sortDirectionPolicy;
    }

    public function withSortDirectionPolicy(DslSortDirectionPolicy $policy): self
    {
        $this->sortDirectionPolicy = $policy;

        return $this;
    }
}
