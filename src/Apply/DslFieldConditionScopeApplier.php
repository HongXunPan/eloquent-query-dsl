<?php

namespace HongXunPan\EloquentQueryDsl\Apply;

use HongXunPan\EloquentQueryDsl\Condition\DslCondition;
use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefinition;
use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslDefinitionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Query DSL 字段条件作用域应用器。
 *
 * 只负责把字段条件按主实体 / relation 分组并应用到 Builder；
 * 不理解 search / filter / between 的具体条件语义。
 *
 * @internal
 */
class DslFieldConditionScopeApplier
{
    /**
     * @template TModel of Model
     * @template TCondition of DslCondition
     * @param Builder<TModel> $query
     * @param array<int, TCondition> $conditions
     * @param callable(Builder<TModel>, TCondition): void $apply
     */
    public function apply(Builder $query, DslQueryDefinition $definition, array $conditions, callable $apply): void
    {
        $mainConditions = [];
        $relationConditions = [];

        foreach ($conditions as $condition) {
            if ($condition->fieldPath()->isMainEntity()) {
                $mainConditions[] = $condition;
                continue;
            }

            $relationConditions[$condition->fieldPath()->entity()][] = $condition;
        }

        foreach ($mainConditions as $condition) {
            $apply($query, $condition);
        }

        foreach ($relationConditions as $entity => $conditionsInRelation) {
            $relation = $definition->relationFor($entity);
            if ($relation === null) {
                throw DslQueryDslDefinitionException::fromMessage(
                    'query dsl relation 未配置映射：' . $entity,
                );
            }

            $query->whereHas(
                $relation->relation(),
                /**
                 * @param Builder<TModel> $query
                 */
                function (Builder $query) use ($conditionsInRelation, $apply): void {
                    foreach ($conditionsInRelation as $condition) {
                        $apply($query, $condition);
                    }
                },
            );
        }
    }
}
