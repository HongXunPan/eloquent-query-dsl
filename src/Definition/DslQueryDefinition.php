<?php

namespace HongXunPan\EloquentQueryDsl\Definition;

use HongXunPan\EloquentQueryDsl\Derived\DslDerivedDefaultSortStrategy;
use HongXunPan\EloquentQueryDsl\Derived\DslDerivedFilterRuleMap;
use HongXunPan\EloquentQueryDsl\Derived\DslKeywordSearchHandler;
use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslDefinitionException;
use HongXunPan\EloquentQueryDsl\Field\DslFieldPath;
use HongXunPan\EloquentQueryDsl\Field\DslIdentifier;

/**
 * Query DSL 查询能力定义。
 *
 * Definition 承接服务端声明的主实体、relation、section 字段能力、默认排序与 strict 模式。
 * 它只描述“允许什么”，不解析请求 section，不生成条件，也不修改 Builder。
 */
class DslQueryDefinition
{
    protected string $mainEntity;

    /**
     * @var array<string, DslRelationDefinition>
     */
    protected array $relations = [];

    /**
     * @var array<string, DslQuerySectionDefinition>
     */
    protected array $sections = [];

    /**
     * @var DslQueryDefaultSort[]
     */
    protected array $defaultSorts = [];

    protected bool $strict = false;

    public function __construct(string $mainEntity)
    {
        $this->mainEntity = DslIdentifier::forDefinition($mainEntity, '主实体');
    }

    public static function make(string $mainEntity): self
    {
        return new self($mainEntity);
    }

    public function mainEntity(): string
    {
        return $this->mainEntity;
    }

    public function relation(string $entity, string $relation): self
    {
        $definition = DslRelationDefinition::make($entity, $relation);
        $this->relations[$definition->entity()] = $definition;

        return $this;
    }

    public function hasRelation(string $entity): bool
    {
        return array_key_exists(trim($entity), $this->relations);
    }

    public function relationFor(string $entity): ?DslRelationDefinition
    {
        return $this->relations[trim($entity)] ?? null;
    }

    /**
     * @return array<string, DslRelationDefinition>
     */
    public function relations(): array
    {
        return $this->relations;
    }

    public function section(string $name): DslQuerySectionDefinition
    {
        $name = $this->normalizeSectionName($name);
        if (!array_key_exists($name, $this->sections)) {
            $this->sections[$name] = DslQuerySectionDefinition::make($name);
        }

        return $this->sections[$name];
    }

    public function hasSection(string $name): bool
    {
        return array_key_exists($this->normalizeSectionName($name), $this->sections);
    }

    public function getSection(string $name): ?DslQuerySectionDefinition
    {
        return $this->sections[$this->normalizeSectionName($name)] ?? null;
    }

    /**
     * @return array<string, DslQuerySectionDefinition>
     */
    public function sections(): array
    {
        return $this->sections;
    }

    public function allowField(string $sectionName, string $field): self
    {
        $this->field($sectionName, $field);

        return $this;
    }

    /**
     * @param array<int, string> $fields
     */
    public function allowFields(string $sectionName, array $fields): self
    {
        foreach ($this->normalizeFieldList($fields, 'allowFields') as $field) {
            $this->allowField($sectionName, $field);
        }

        return $this;
    }

    /**
     * @param array<int, string> $fields
     */
    public function allowSearch(array $fields): self
    {
        return $this->allowFields('search', $fields);
    }

    /**
     * @param array<int, string> $fields
     */
    public function searchRightLike(array $fields): self
    {
        foreach ($this->normalizeFieldList($fields, 'searchRightLike') as $field) {
            $this->declaredField('search', $field)
                ->withSearchMode('right_like');
        }

        return $this;
    }

    public function allowDerivedSearch(string $field, callable $handler): self
    {
        $this->declaredField('search', $field)
            ->withDerivedSearchHandler($handler);

        return $this;
    }

    /**
     * @param array<int, string> $targetFields
     */
    public function allowKeywordSearch(string $field, array $targetFields, string $mode = ''): self
    {
        $fieldDefinition = $this->field('search', $field);
        $fieldDefinition->withDerivedSearchBehavior(
            DslKeywordSearchHandler::forFields(
                $this->keywordSearchTargetFieldPaths($targetFields),
            ),
        );

        if (trim($mode) !== '') {
            $fieldDefinition->withSearchMode($mode);
        }

        return $this;
    }

    /**
     * @param array<int|string, string> $fields
     */
    public function allowFilter(array $fields): self
    {
        foreach ($fields as $key => $value) {
            $field = is_int($key) ? (string)$value : (string)$key;
            $rule = is_int($key) ? '' : (string)$value;

            $this->field('filter', $field)
                ->withFilterRule($rule);
        }

        return $this;
    }

    public function allowDerivedFilter(string $field, DslDerivedFilterRuleMap $ruleMap): self
    {
        $this->declaredField('filter', $field)
            ->withDerivedFilterRuleMap($ruleMap);

        return $this;
    }

    public function allowDerivedFilterHandler(string $field, callable $handler): self
    {
        $this->declaredField('filter', $field)
            ->withDerivedFilterHandler($handler);

        return $this;
    }

    /**
     * @param array<int, string> $fields
     */
    public function allowBetween(array $fields): self
    {
        return $this->allowFields('between', $fields);
    }

    /**
     * @param array<int, string> $fields
     */
    public function allowSort(array $fields): self
    {
        foreach ($this->normalizeFieldList($fields, 'allowSort') as $field) {
            $this->field('sort', $field);
        }

        return $this;
    }

    /**
     * @param array<int, string> $fields
     */
    public function sortAscOnly(array $fields): self
    {
        foreach ($this->normalizeFieldList($fields, 'sortAscOnly') as $field) {
            $this->declaredField('sort', $field)
                ->withSortDirectionPolicy(DslSortDirectionPolicy::ascOnly());
        }

        return $this;
    }

    /**
     * @param array<int, string> $fields
     */
    public function sortDescOnly(array $fields): self
    {
        foreach ($this->normalizeFieldList($fields, 'sortDescOnly') as $field) {
            $this->declaredField('sort', $field)
                ->withSortDirectionPolicy(DslSortDirectionPolicy::descOnly());
        }

        return $this;
    }

    public function allowDerivedDefaultSortByFilter(string $field, DslDerivedDefaultSortStrategy $strategy): self
    {
        $this->declaredField('filter', $field)
            ->withDerivedDefaultSortStrategy($strategy);

        return $this;
    }

    public function field(string $sectionName, string $field): DslQueryFieldDefinition
    {
        return $this->section($sectionName)->allowField(
            DslFieldPath::fromDefinition($field, $this->mainEntity),
        );
    }

    public function defaultSort(string $field, string $order = DslQueryDefaultSort::ORDER_ASC): self
    {
        $this->appendDefaultSort(
            DslQueryDefaultSort::forField($field, $this->mainEntity, $order),
        );

        return $this;
    }

    public function defaultSortMany(DslQueryDefaultSort ...$sorts): self
    {
        foreach ($sorts as $sort) {
            $this->appendDefaultSort($sort);
        }

        return $this;
    }

    /**
     * @return DslQueryDefaultSort[]
     */
    public function defaultSorts(): array
    {
        return $this->defaultSorts;
    }

    public function strict(bool $strict = true): self
    {
        $this->strict = $strict;

        return $this;
    }

    public function isStrict(): bool
    {
        return $this->strict;
    }

    protected function normalizeSectionName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            throw DslQueryDslDefinitionException::fromMessage('query dsl section名称不能为空');
        }

        return $name;
    }

    protected function declaredField(string $sectionName, string $field): DslQueryFieldDefinition
    {
        $fieldPath = DslFieldPath::fromDefinition($field, $this->mainEntity);
        $fieldDefinition = $this->getSection($sectionName)?->field($fieldPath->canonical());
        if ($fieldDefinition === null) {
            throw DslQueryDslDefinitionException::fromMessage(sprintf(
                '%s 字段未在 allow%s 中声明：%s',
                $sectionName,
                ucfirst($sectionName),
                $fieldPath->canonical(),
            ));
        }

        return $fieldDefinition;
    }

    protected function appendDefaultSort(DslQueryDefaultSort $sort): void
    {
        if ($sort->fieldPath()->isRelation()) {
            throw DslQueryDslDefinitionException::fromMessage('defaultSort 当前仅支持主实体字段排序');
        }

        $this->defaultSorts[] = $sort;
    }

    /**
     * @param array<int|string, mixed> $fields
     * @return array<int, string>
     */
    protected function normalizeFieldList(array $fields, string $helperName): array
    {
        $normalized = [];
        foreach ($fields as $key => $value) {
            if (!is_int($key)) {
                throw DslQueryDslDefinitionException::fromMessage($helperName . ' 仅支持字段列表，请勿传字段 => 值映射');
            }

            $field = trim((string)$value);
            if ($field === '') {
                throw DslQueryDslDefinitionException::fromMessage($helperName . ' 字段不能为空');
            }

            $normalized[] = $field;
        }

        return $normalized;
    }

    /**
     * @param array<int, string> $fields
     * @return array<int, DslFieldPath>
     */
    protected function keywordSearchTargetFieldPaths(array $fields): array
    {
        $fieldPaths = [];
        foreach ($this->normalizeFieldList($fields, 'allowKeywordSearch') as $field) {
            $fieldPaths[] = DslFieldPath::fromDefinition($field, $this->mainEntity);
        }

        return $fieldPaths;
    }
}
