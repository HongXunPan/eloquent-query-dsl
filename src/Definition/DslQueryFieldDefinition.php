<?php

namespace HongXunPan\EloquentQueryDsl\Definition;

use HongXunPan\EloquentQueryDsl\Derived\DslDerivedDefaultSortStrategy;
use HongXunPan\EloquentQueryDsl\Derived\DslDerivedFilterBehavior;
use HongXunPan\EloquentQueryDsl\Derived\DslDerivedFilterHandler;
use HongXunPan\EloquentQueryDsl\Derived\DslDerivedFilterRuleMap;
use HongXunPan\EloquentQueryDsl\Derived\DslDerivedSearchBehavior;
use HongXunPan\EloquentQueryDsl\Derived\DslDerivedSearchHandler;
use HongXunPan\EloquentQueryDsl\Field\DslFieldPath;

/**
 * Query DSL 字段能力定义。
 *
 * 该对象只描述某个 section 下允许使用的字段及其附加声明，不承接输入解析或 Builder 执行。
 */
class DslQueryFieldDefinition
{
    protected DslFieldPath $fieldPath;
    protected string $filterRule = '';
    protected string $searchMode = '';
    protected DslSortDirectionPolicy $sortDirectionPolicy;
    protected ?DslDerivedFilterBehavior $derivedFilterBehavior = null;
    protected ?DslDerivedSearchBehavior $derivedSearchBehavior = null;
    protected ?DslDerivedDefaultSortStrategy $derivedDefaultSortStrategy = null;

    private function __construct(DslFieldPath $fieldPath)
    {
        $this->fieldPath = $fieldPath;
        $this->sortDirectionPolicy = DslSortDirectionPolicy::any();
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

    public function filterRule(): string
    {
        return $this->filterRule;
    }

    public function withFilterRule(string $rule): self
    {
        $this->filterRule = trim($rule);
        return $this;
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

    public function sortDirectionPolicy(): DslSortDirectionPolicy
    {
        return $this->sortDirectionPolicy;
    }

    public function withSortDirectionPolicy(DslSortDirectionPolicy $policy): self
    {
        $this->sortDirectionPolicy = $policy;

        return $this;
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
