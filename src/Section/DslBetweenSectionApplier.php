<?php

namespace HongXunPan\EloquentQueryDsl\Section;

use HongXunPan\EloquentQueryDsl\Apply\DslFieldConditionScopeApplier;
use HongXunPan\EloquentQueryDsl\Condition\DslBetweenCondition;
use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefinition;
use HongXunPan\EloquentQueryDsl\Field\DslFieldPath;
use HongXunPan\EloquentQueryDsl\Input\DslQueryInput;
use HongXunPan\EloquentQueryDsl\Input\DslQueryRequestContext;
use HongXunPan\EloquentQueryDsl\Reader\DslFieldMapReader;
use HongXunPan\EloquentQueryDsl\Reader\DslSectionReader;
use Illuminate\Database\Eloquent\Builder;

/**
 * Query DSL between section 应用器。
 *
 * @internal
 */
class DslBetweenSectionApplier implements DslSectionApplier
{
    private const SECTION = DslBetweenCondition::SECTION;

    protected DslSectionReader $sectionReader;
    protected DslFieldMapReader $fieldMapReader;
    protected DslFieldConditionScopeApplier $scopeApplier;

    public function __construct(
        ?DslSectionReader $sectionReader = null,
        ?DslFieldMapReader $fieldMapReader = null,
        ?DslFieldConditionScopeApplier $scopeApplier = null,
    ) {
        $this->sectionReader = $sectionReader ?? new DslSectionReader();
        $this->fieldMapReader = $fieldMapReader ?? new DslFieldMapReader($this->sectionReader);
        $this->scopeApplier = $scopeApplier ?? new DslFieldConditionScopeApplier();
    }

    public function apply(Builder $query, DslQueryRequestContext $context): void
    {
        $definition = $context->definition();
        $input = $context->queryInput();
        $conditions = $this->conditions($definition, $input);
        $this->scopeApplier->apply($query, $definition, $conditions, function (Builder $query, DslBetweenCondition $condition): void {
            $query->whereBetween(
                $query->qualifyColumn($condition->fieldPath()->field()),
                $condition->values(),
            );
        });
    }

    /**
     * @return DslBetweenCondition[]
     */
    public function conditions(DslQueryDefinition $definition, DslQueryInput $input): array
    {
        $sectionValue = $this->fieldMapReader->read($input, self::SECTION);
        if ($sectionValue === null) {
            return [];
        }

        $conditions = [];
        $this->fieldMapReader->each($definition, $sectionValue, function (DslFieldPath $fieldPath, mixed $value) use ($definition, &$conditions): void {
            $fieldDefinition = $this->sectionReader->fieldDefinition($definition, self::SECTION, $fieldPath, $value);
            if ($fieldDefinition === null) {
                return;
            }

            if (!is_array($value) || count($value) !== 2) {
                $this->sectionReader->rejectInvalidInput($definition, self::SECTION, $fieldPath->canonical(), '区间条件必须是长度为 2 的数组');
                return;
            }

            $values = array_values($value);
            $conditions[] = DslBetweenCondition::make($fieldPath, $values[0], $values[1]);
        });

        return $conditions;
    }
}
