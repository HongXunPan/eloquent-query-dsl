<?php

namespace HongXunPan\EloquentQueryDsl\Derived;

use HongXunPan\EloquentQueryDsl\Condition\DslSearchCondition;
use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslDefinitionException;
use HongXunPan\EloquentQueryDsl\Field\DslFieldPath;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Query DSL 关键词搜索处理器。
 *
 * 用于把一个 search 输入字段映射到多个主实体字段，并以 OR 语义分组查询。
 */
class DslKeywordSearchHandler implements DslDerivedSearchBehavior
{
    private const SEARCH_MODE_RIGHT_LIKE = 'right_like';

    /**
     * @param DslFieldPath[] $targetFields
     */
    private function __construct(private array $targetFields)
    {
    }

    /**
     * @param DslFieldPath[] $targetFields
     */
    public static function forFields(array $targetFields): self
    {
        if ($targetFields === []) {
            throw DslQueryDslDefinitionException::fromMessage('query dsl 关键词搜索目标字段不能为空');
        }

        foreach ($targetFields as $targetField) {
            if (!$targetField instanceof DslFieldPath) {
                throw DslQueryDslDefinitionException::fromMessage('query dsl 关键词搜索目标字段类型错误');
            }

            if ($targetField->isRelation()) {
                throw DslQueryDslDefinitionException::fromMessage('query dsl 关键词搜索当前仅支持主实体字段');
            }
        }

        return new self(array_values($targetFields));
    }

    /**
     * @template TModel of Model
     * @param Builder<TModel> $query
     */
    public function apply(Builder $query, DslSearchCondition $condition): void
    {
        $value = $condition->mode() === self::SEARCH_MODE_RIGHT_LIKE
            ? $condition->value() . '%'
            : '%' . $condition->value() . '%';

        $query->where(
            /**
             * @param Builder<TModel> $query
             */
            function (Builder $query) use ($value): void {
                foreach ($this->targetFields as $index => $targetField) {
                    $column = $query->qualifyColumn($targetField->field());
                    if ($index === 0) {
                        $query->where($column, 'like', $value);
                        continue;
                    }

                    $query->orWhere($column, 'like', $value);
                }
            },
        );
    }
}
