<?php

namespace HongXunPan\EloquentQueryDsl\Definition;

use HongXunPan\EloquentQueryDsl\Derived\DslDerivedSearchBehavior;
use HongXunPan\EloquentQueryDsl\Derived\DslDerivedSearchHandler;
use HongXunPan\EloquentQueryDsl\Field\DslFieldPath;

/**
 * search section 字段定义。
 */
class DslSearchFieldDefinition extends DslQueryFieldDefinition
{
    protected string $searchMode = '';
    protected ?DslDerivedSearchBehavior $derivedSearchBehavior = null;

    public static function make(DslFieldPath $fieldPath): self
    {
        return new self($fieldPath);
    }

    public function searchMode(): string
    {
        return $this->searchMode;
    }

    public function withSearchMode(string $mode): self
    {
        $this->searchMode = trim($mode);

        return $this;
    }

    public function hasDerivedSearchBehavior(): bool
    {
        return $this->derivedSearchBehavior !== null;
    }

    public function derivedSearchBehavior(): ?DslDerivedSearchBehavior
    {
        return $this->derivedSearchBehavior;
    }

    public function withDerivedSearchBehavior(DslDerivedSearchBehavior $behavior): self
    {
        $this->derivedSearchBehavior = $behavior;

        return $this;
    }

    public function withDerivedSearchHandler(callable $handler): self
    {
        return $this->withDerivedSearchBehavior(
            DslDerivedSearchHandler::make($handler),
        );
    }
}
