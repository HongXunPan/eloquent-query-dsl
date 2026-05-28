<?php

namespace HongXunPan\EloquentQueryDsl\Section;

use HongXunPan\EloquentQueryDsl\Apply\DslFieldConditionScopeApplier;
use HongXunPan\EloquentQueryDsl\Condition\DslSearchCondition;
use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefinition;
use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslDefinitionException;
use HongXunPan\EloquentQueryDsl\Field\DslFieldPath;
use HongXunPan\EloquentQueryDsl\Input\DslQueryInput;
use HongXunPan\EloquentQueryDsl\Input\DslQueryRequestContext;
use HongXunPan\EloquentQueryDsl\Reader\DslFieldMapReader;
use HongXunPan\EloquentQueryDsl\Reader\DslSectionReader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Query DSL search section 应用器。
 *
 * @internal
 */
class DslSearchSectionApplier implements DslSectionApplier
{
    private const SECTION = DslSearchCondition::SECTION;
    private const SEARCH_MODE_FULL_LIKE = 'full_like';
    private const SEARCH_MODE_RIGHT_LIKE = 'right_like';

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

    /**
     * @template TModel of Model
     * @param Builder<TModel> $query
     */
    public function apply(Builder $query, DslQueryRequestContext $context): void
    {
        $definition = $context->definition();
        $input = $context->queryInput();
        $conditions = $this->conditions($definition, $input);
        $searchSection = $definition->getSection(self::SECTION);

        $this->scopeApplier->apply(
            $query,
            $definition,
            $conditions,
            /**
             * @param Builder<TModel> $query
             */
            function (Builder $query, DslSearchCondition $condition) use ($searchSection): void {
                $derivedBehavior = $searchSection?->field($condition->canonicalField())?->derivedSearchBehavior();
                if ($derivedBehavior !== null) {
                    $derivedBehavior->apply($query, $condition);
                    return;
                }

                $column = $query->qualifyColumn($condition->fieldPath()->field());
                if ($condition->mode() === self::SEARCH_MODE_RIGHT_LIKE) {
                    $query->where($column, 'like', $condition->value() . '%');
                    return;
                }

                $query->where($column, 'like', '%' . $condition->value() . '%');
            },
        );
    }

    /**
     * @return DslSearchCondition[]
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

            $value = $this->normalizeSearchValue($value);
            if ($value === null) {
                return;
            }

            $conditions[] = DslSearchCondition::fromField(
                $fieldPath,
                $value,
                $this->normalizeSearchMode($fieldDefinition->searchMode()),
            );
        });

        return $conditions;
    }

    protected function normalizeSearchValue(mixed $value): ?string
    {
        if (!$this->sectionReader->hasMeaningfulValue($value) || is_array($value)) {
            return null;
        }

        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }

    protected function normalizeSearchMode(string $mode): string
    {
        $mode = trim($mode);
        if ($mode === '') {
            return self::SEARCH_MODE_FULL_LIKE;
        }

        if (!in_array($mode, [self::SEARCH_MODE_FULL_LIKE, self::SEARCH_MODE_RIGHT_LIKE], true)) {
            throw DslQueryDslDefinitionException::fromMessage('query dsl 搜索模式错误');
        }

        return $mode;
    }
}
