<?php

namespace HongXunPan\EloquentQueryDsl\Section;

use HongXunPan\EloquentQueryDsl\Apply\DslFieldConditionScopeApplier;
use HongXunPan\EloquentQueryDsl\Condition\DslFilterCondition;
use HongXunPan\EloquentQueryDsl\Definition\DslFilterFieldDefinition;
use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefinition;
use HongXunPan\EloquentQueryDsl\Definition\DslQueryFieldDefinition;
use HongXunPan\EloquentQueryDsl\Definition\DslQuerySectionDefinition;
use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslDefinitionException;
use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslException;
use HongXunPan\EloquentQueryDsl\Field\DslFieldPath;
use HongXunPan\EloquentQueryDsl\Filter\Contract\DslFilterNormalizer;
use HongXunPan\EloquentQueryDsl\Filter\DslFilterValue;
use HongXunPan\EloquentQueryDsl\Filter\DslFilterValues;
use HongXunPan\EloquentQueryDsl\Filter\Value\DslNormalizedFilterItem;
use HongXunPan\EloquentQueryDsl\Filter\Value\DslNormalizedFilterSet;
use HongXunPan\EloquentQueryDsl\Input\DslQueryInput;
use HongXunPan\EloquentQueryDsl\Input\DslQueryRequestContext;
use HongXunPan\EloquentQueryDsl\Reader\DslFieldMapReader;
use HongXunPan\EloquentQueryDsl\Reader\DslSectionReader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Query DSL filter section 应用器。
 *
 * @internal
 */
class DslFilterSectionApplier implements DslSectionApplier
{
    private const SECTION = DslFilterCondition::SECTION;

    protected DslSectionReader $sectionReader;
    protected DslFieldMapReader $fieldMapReader;
    protected DslFieldConditionScopeApplier $scopeApplier;
    protected ?DslFilterNormalizer $filterNormalizer;

    public function __construct(
        ?DslSectionReader $sectionReader = null,
        ?DslFieldMapReader $fieldMapReader = null,
        ?DslFieldConditionScopeApplier $scopeApplier = null,
        ?DslFilterNormalizer $filterNormalizer = null,
    ) {
        $this->sectionReader = $sectionReader ?? new DslSectionReader();
        $this->fieldMapReader = $fieldMapReader ?? new DslFieldMapReader($this->sectionReader);
        $this->scopeApplier = $scopeApplier ?? new DslFieldConditionScopeApplier();
        $this->filterNormalizer = $filterNormalizer;
    }

    /**
     * @template TModel of Model
     * @param Builder<TModel> $query
     */
    public function apply(Builder $query, DslQueryRequestContext $context): void
    {
        $definition = $context->definition();
        $filterValues = $this->filterValuesFromContext($context);
        $this->applyDerivedFilterValues($query, $filterValues);

        $conditions = $this->conditionsFromFilterValues($filterValues);
        $this->scopeApplier->apply(
            $query,
            $definition,
            $conditions,
            /**
             * @param Builder<Model> $query
             */
            function (Builder $query, DslFilterCondition $condition): void {
                $column = $query->qualifyColumn($condition->fieldPath()->field());
                $value = $condition->value();
                if (is_array($value)) {
                    if ($value === []) {
                        return;
                    }

                    $query->whereIn($column, $value);
                    return;
                }

                $query->where($column, $value);
            },
        );
    }

    /**
     * @return DslFilterCondition[]
     */
    public function conditions(DslQueryDefinition $definition, DslQueryInput $input): array
    {
        return $this->conditionsFromFilterValues(
            $this->filterValues($definition, $input),
        );
    }

    public function filterValuesFromContext(DslQueryRequestContext $context): DslFilterValues
    {
        return $context->filterValuesUsing(
            fn (DslQueryDefinition $definition, DslQueryInput $input): DslFilterValues => $this->filterValues($definition, $input),
        );
    }

    public function filterValues(DslQueryDefinition $definition, DslQueryInput $input): DslFilterValues
    {
        $filterValues = new DslFilterValues();
        $sectionDefinition = $definition->getSection(self::SECTION);
        $sectionValue = $this->fieldMapReader->read($input, self::SECTION);
        $normalizedPayload = [];
        if ($sectionValue !== null) {
            $this->fieldMapReader->each($definition, $sectionValue, function (DslFieldPath $fieldPath, mixed $value) use ($definition, &$normalizedPayload): void {
                $fieldDefinition = $this->sectionReader->fieldDefinition($definition, self::SECTION, $fieldPath, $value);
                if ($fieldDefinition === null) {
                    return;
                }

                $this->setArrayValueByPath(
                    $normalizedPayload,
                    $this->payloadField($fieldDefinition),
                    $value,
                );
            });
        }

        $normalizedFilterSet = $this->normalizeFilterSetBySection(
            $normalizedPayload,
            $sectionDefinition,
        );

        foreach ($sectionDefinition?->fields() ?? [] as $fieldDefinition) {
            $item = $this->itemFromNormalizedPayload($normalizedFilterSet, $fieldDefinition)
                ?? $normalizedFilterSet->item($this->payloadField($fieldDefinition));

            if ($item === null) {
                continue;
            }

            $filterValues->put(DslFilterValue::fromDefinition(
                $fieldDefinition,
                $item->normalizedValues(),
            ));
        }

        return $filterValues;
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function normalizeFilterSetBySection(array $payload, ?DslQuerySectionDefinition $sectionDefinition): DslNormalizedFilterSet
    {
        $fieldDefinitions = $sectionDefinition?->fields() ?? [];
        $validatorRules = $sectionDefinition?->validationRules() ?? [];

        if ($fieldDefinitions === [] && $validatorRules === []) {
            return DslNormalizedFilterSet::success($payload, []);
        }

        if ($validatorRules === []) {
            return $this->normalizeFilterSetWithoutRules($payload, $fieldDefinitions);
        }

        if ($this->filterNormalizer === null) {
            throw DslQueryDslDefinitionException::fromMessage('query dsl filter normalizer 未配置');
        }

        $result = $this->filterNormalizer->normalize($payload, $validatorRules, [
            'reject_unknown' => false,
        ]);
        if (!$result->isPassed()) {
            throw DslQueryDslException::invalidQuery(
                $result->errors()[0] ?? 'query.filter 参数错误',
            );
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, DslQueryFieldDefinition> $fieldDefinitions
     */
    protected function normalizeFilterSetWithoutRules(array $payload, array $fieldDefinitions): DslNormalizedFilterSet
    {
        $items = [];
        foreach ($fieldDefinitions as $fieldDefinition) {
            $payloadField = $this->payloadField($fieldDefinition);
            $normalizedValueInfo = $this->getArrayValueByPath($payload, $payloadField);
            if (!$normalizedValueInfo['exists']) {
                continue;
            }

            $items[$payloadField] = new DslNormalizedFilterItem(
                $payloadField,
                $this->normalizeQueryValues($normalizedValueInfo['value']),
            );
        }

        return DslNormalizedFilterSet::success($payload, $items);
    }

    /**
     * @template TModel of Model
     * @param Builder<TModel> $query
     */
    protected function applyDerivedFilterValues(Builder $query, DslFilterValues $filterValues): void
    {
        foreach ($filterValues->all() as $filterValue) {
            if (!$filterValue->hasMeaningfulValue()) {
                continue;
            }

            $fieldDefinition = $filterValue->definition();
            if (!$fieldDefinition instanceof DslFilterFieldDefinition) {
                continue;
            }

            $behavior = $fieldDefinition->derivedFilterBehavior();
            if ($behavior === null) {
                continue;
            }

            $behavior->apply($query, $filterValue);
        }
    }

    /**
     * @return DslFilterCondition[]
     */
    protected function conditionsFromFilterValues(DslFilterValues $filterValues): array
    {
        $conditions = [];
        foreach ($filterValues->all() as $filterValue) {
            if (!$filterValue->hasMeaningfulValue()) {
                continue;
            }

            $fieldDefinition = $filterValue->definition();
            if ($fieldDefinition instanceof DslFilterFieldDefinition && $fieldDefinition->hasDerivedFilterBehavior()) {
                continue;
            }

            $conditions[] = DslFilterCondition::fromField(
                $filterValue->fieldPath(),
                $filterValue->queryValue(),
            );
        }

        return $conditions;
    }

    protected function payloadField(mixed $fieldDefinition): string
    {
        return $fieldDefinition->fieldPath()->isMainEntity()
            ? $fieldDefinition->fieldPath()->field()
            : $fieldDefinition->canonical();
    }

    protected function itemFromNormalizedPayload(
        DslNormalizedFilterSet $filterSet,
        mixed $fieldDefinition,
    ): ?DslNormalizedFilterItem {
        $payloadField = $this->payloadField($fieldDefinition);
        $normalizedValueInfo = $this->getArrayValueByPath($filterSet->normalizedPayload(), $payloadField);
        if (!$normalizedValueInfo['exists']) {
            return null;
        }

        return new DslNormalizedFilterItem(
            $payloadField,
            $this->normalizeQueryValues($normalizedValueInfo['value']),
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function setArrayValueByPath(array &$data, string $path, mixed $value): void
    {
        if ($path === '') {
            return;
        }

        $segments = explode('.', $path);
        $current = &$data;

        foreach ($segments as $index => $segment) {
            if ($index === count($segments) - 1) {
                if (array_key_exists($segment, $current)) {
                    throw DslQueryDslException::invalidQuery('query.filter 参数冲突：' . $path);
                }

                $current[$segment] = $value;
                return;
            }

            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }

            $current = &$current[$segment];
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array{exists: bool, value: mixed}
     */
    protected function getArrayValueByPath(array $data, string $path): array
    {
        if ($path === '') {
            return ['exists' => true, 'value' => $data];
        }

        $current = $data;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return ['exists' => false, 'value' => null];
            }

            $current = $current[$segment];
        }

        return ['exists' => true, 'value' => $current];
    }

    /**
     * @return array<int, mixed>
     */
    protected function normalizeQueryValues(mixed $value): array
    {
        if (is_array($value)) {
            return $this->normalizeArrayValues($value);
        }

        return $this->hasMeaningfulValue($value) ? [$value] : [];
    }

    /**
     * @param array<int, mixed> $values
     * @return array<int, mixed>
     */
    protected function normalizeArrayValues(array $values): array
    {
        $normalized = [];
        foreach ($values as $value) {
            if ($this->hasMeaningfulValue($value)) {
                $normalized[] = $value;
            }
        }

        return array_values($normalized);
    }

    protected function hasMeaningfulValue(mixed $value): bool
    {
        if (is_array($value)) {
            return $value !== [];
        }

        return $value || $value === '0' || $value === 0;
    }
}
