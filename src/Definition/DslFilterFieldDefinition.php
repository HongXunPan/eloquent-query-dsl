<?php

namespace HongXunPan\EloquentQueryDsl\Definition;

use HongXunPan\EloquentQueryDsl\Derived\DslDerivedDefaultSortStrategy;
use HongXunPan\EloquentQueryDsl\Derived\DslDerivedFilterBehavior;
use HongXunPan\EloquentQueryDsl\Derived\DslDerivedFilterHandler;
use HongXunPan\EloquentQueryDsl\Derived\DslDerivedFilterRuleMap;
use HongXunPan\EloquentQueryDsl\Field\DslFieldPath;

/**
 * filter section 字段定义。
 */
class DslFilterFieldDefinition extends DslQueryFieldDefinition
{
    protected ?DslDerivedFilterBehavior $derivedFilterBehavior = null;
    protected ?DslDerivedDefaultSortStrategy $derivedDefaultSortStrategy = null;

    public static function make(DslFieldPath $fieldPath): self
    {
        return new self($fieldPath);
    }

    public function hasDerivedFilterBehavior(): bool
    {
        return $this->derivedFilterBehavior !== null;
    }

    public function derivedFilterBehavior(): ?DslDerivedFilterBehavior
    {
        return $this->derivedFilterBehavior;
    }

    public function withDerivedFilterBehavior(DslDerivedFilterBehavior $behavior): self
    {
        $this->derivedFilterBehavior = $behavior;

        return $this;
    }

    public function withDerivedFilterHandler(callable $handler): self
    {
        return $this->withDerivedFilterBehavior(
            DslDerivedFilterHandler::make($handler),
        );
    }

    public function withDerivedFilterRuleMap(DslDerivedFilterRuleMap $ruleMap): self
    {
        return $this->withDerivedFilterBehavior($ruleMap);
    }

    public function hasDerivedDefaultSortStrategy(): bool
    {
        return $this->derivedDefaultSortStrategy !== null;
    }

    public function derivedDefaultSortStrategy(): ?DslDerivedDefaultSortStrategy
    {
        return $this->derivedDefaultSortStrategy;
    }

    public function withDerivedDefaultSortStrategy(DslDerivedDefaultSortStrategy $strategy): self
    {
        $this->derivedDefaultSortStrategy = $strategy;

        return $this;
    }
}
