<?php

namespace HongXunPan\EloquentQueryDsl\Section;

use HongXunPan\EloquentQueryDsl\Condition\DslSortCondition;
use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefaultSort;
use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefinition;
use HongXunPan\EloquentQueryDsl\Definition\DslQueryFieldDefinition;
use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslDefinitionException;
use HongXunPan\EloquentQueryDsl\Field\DslFieldPath;
use HongXunPan\EloquentQueryDsl\Filter\DslFilterValues;
use HongXunPan\EloquentQueryDsl\Input\DslQueryInput;
use HongXunPan\EloquentQueryDsl\Input\DslQueryRequestContext;
use HongXunPan\EloquentQueryDsl\Reader\DslItemListReader;
use HongXunPan\EloquentQueryDsl\Reader\DslSectionReader;
use Illuminate\Database\Eloquent\Builder;

/**
 * Query DSL sort section 应用器。
 *
 * sort 的显式排序与默认排序 fallback 都属于 sort section 职责，
 * Kernel 不再特殊判断 sort 返回值。
 *
 * @internal
 */
class DslSortSectionApplier implements DslSectionApplier
{
    private const SECTION = DslSortCondition::SECTION;
    private const SORT_ITEM_FIELD = 'field';
    private const SORT_ITEM_ENTITY = 'entity';
    private const SORT_ITEM_ORDER = 'order';

    protected DslSectionReader $sectionReader;
    protected DslItemListReader $itemListReader;
    protected DslFilterSectionApplier $filterSectionApplier;

    public function __construct(
        ?DslSectionReader $sectionReader = null,
        ?DslItemListReader $itemListReader = null,
        ?DslFilterSectionApplier $filterSectionApplier = null,
    ) {
        $this->sectionReader = $sectionReader ?? new DslSectionReader();
        $this->itemListReader = $itemListReader ?? new DslItemListReader($this->sectionReader);
        $this->filterSectionApplier = $filterSectionApplier ?? new DslFilterSectionApplier();
    }

    public function apply(Builder $query, DslQueryRequestContext $context): void
    {
        if (!$this->applyExplicitSortFromContext($query, $context)) {
            $this->applyDefaultSortFromContext($query, $context);
        }
    }

    protected function applyExplicitSort(Builder $query, DslQueryDefinition $definition, DslQueryInput $input): bool
    {
        return $this->applyExplicitSortFromContext(
            $query,
            DslQueryRequestContext::fromQueryInput($definition, $input),
        );
    }

    protected function applyExplicitSortFromContext(Builder $query, DslQueryRequestContext $context): bool
    {
        $definition = $context->definition();
        $conditions = $this->conditions($definition, $context->queryInput());
        $applied = false;
        foreach ($conditions as $condition) {
            if ($condition->fieldPath()->isRelation()) {
                $this->sectionReader->rejectInvalidInput($definition, self::SECTION, $condition->canonicalField(), '当前版本暂不支持关联排序');
                continue;
            }

            $query->orderBy($condition->fieldPath()->field(), $condition->order());
            $applied = true;
        }

        return $applied;
    }

    public function applyDefaultSort(Builder $query, DslQueryDefinition $definition, DslQueryInput $input): void
    {
        $this->applyDefaultSortFromContext(
            $query,
            DslQueryRequestContext::fromQueryInput($definition, $input),
        );
    }

    protected function applyDefaultSortFromContext(Builder $query, DslQueryRequestContext $context): void
    {
        $definition = $context->definition();
        $sorts = $this->resolveDerivedDefaultSortsFromContext($context);
        if ($sorts === []) {
            $sorts = $definition->defaultSorts();
        }

        $this->applySorts($query, $sorts);
    }

    /**
     * @return DslSortCondition[]
     */
    public function conditions(DslQueryDefinition $definition, DslQueryInput $input): array
    {
        $sectionValue = $this->itemListReader->read($input, self::SECTION);
        if ($sectionValue === null) {
            return [];
        }

        $sectionDefinition = $this->sectionReader->sectionDefinition($definition, self::SECTION, $sectionValue);
        if ($sectionDefinition === null) {
            return [];
        }

        $conditions = [];
        foreach ($sectionValue as $index => $item) {
            if (!is_array($item)) {
                $this->sectionReader->rejectInvalidInput($definition, self::SECTION, 'sort.' . $index, '排序项必须是对象');
                continue;
            }

            $field = trim((string)($item[self::SORT_ITEM_FIELD] ?? ''));
            $entity = trim((string)($item[self::SORT_ITEM_ENTITY] ?? $definition->mainEntity()));
            $order = strtolower(trim((string)($item[self::SORT_ITEM_ORDER] ?? DslSortCondition::ORDER_ASC)));

            if ($field === '') {
                $this->sectionReader->rejectInvalidInput($definition, self::SECTION, 'sort.' . $index, '排序字段不能为空');
                continue;
            }

            if (!in_array($order, [DslSortCondition::ORDER_ASC, DslSortCondition::ORDER_DESC], true)) {
                $this->sectionReader->rejectInvalidInput($definition, self::SECTION, 'sort.' . $index, '排序方向仅支持 asc / desc');
                continue;
            }

            $fieldPath = DslFieldPath::fromInput(
                $entity === $definition->mainEntity() ? $field : $entity . '.' . $field,
                $definition->mainEntity(),
            );

            $fieldDefinition = $sectionDefinition->field($fieldPath->canonical());
            if ($fieldDefinition === null) {
                $this->sectionReader->rejectUnknownField($definition, self::SECTION, $fieldPath->canonical());
                continue;
            }

            if (!$fieldDefinition->sortDirectionPolicy()->allows($order)) {
                $this->sectionReader->rejectInvalidInput($definition, self::SECTION, $fieldPath->canonical(), '该字段不支持当前排序方向');
                continue;
            }

            $conditions[] = DslSortCondition::make($fieldPath, $order);
        }

        return $conditions;
    }

    /**
     * @param DslQueryDefaultSort[] $sorts
     */
    protected function applySorts(Builder $query, array $sorts): void
    {
        foreach ($sorts as $sort) {
            if ($sort->fieldPath()->isRelation()) {
                throw DslQueryDslDefinitionException::fromMessage('query dsl 默认排序当前仅支持主实体字段');
            }

            $query->orderBy($sort->fieldPath()->field(), $sort->order());
        }
    }

    /**
     * @return DslQueryDefaultSort[]
     */
    protected function resolveDerivedDefaultSorts(DslQueryDefinition $definition, DslQueryInput $input): array
    {
        return $this->resolveDerivedDefaultSortsFromContext(
            DslQueryRequestContext::fromQueryInput($definition, $input),
        );
    }

    /**
     * @return DslQueryDefaultSort[]
     */
    protected function resolveDerivedDefaultSortsFromContext(DslQueryRequestContext $context): array
    {
        $definition = $context->definition();
        $filterSection = $definition->getSection('filter');
        if ($filterSection === null) {
            return [];
        }

        $filterValues = $this->filterSectionApplier->filterValuesFromContext($context);
        foreach ($filterSection->fields() as $fieldDefinition) {
            $sorts = $this->resolveFieldDerivedDefaultSorts($fieldDefinition, $filterValues);
            if ($sorts !== []) {
                return $sorts;
            }
        }

        return [];
    }

    /**
     * @return DslQueryDefaultSort[]
     */
    protected function resolveFieldDerivedDefaultSorts(
        DslQueryFieldDefinition $fieldDefinition,
        DslFilterValues $filterValues,
    ): array {
        if (!$fieldDefinition->hasDerivedDefaultSortStrategy()) {
            return [];
        }

        $filterValue = $filterValues->get($fieldDefinition->canonical());
        if ($filterValue === null) {
            return [];
        }

        return $fieldDefinition->derivedDefaultSortStrategy()?->resolve($filterValue) ?? [];
    }
}
