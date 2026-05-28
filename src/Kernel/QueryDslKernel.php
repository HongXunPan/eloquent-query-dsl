<?php

namespace HongXunPan\EloquentQueryDsl\Kernel;

use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefinition;
use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslRuntimeException;
use HongXunPan\EloquentQueryDsl\Input\DslQueryInput;
use HongXunPan\EloquentQueryDsl\Input\DslQueryRequestContext;
use HongXunPan\EloquentQueryDsl\Section\DslBetweenSectionApplier;
use HongXunPan\EloquentQueryDsl\Section\DslFilterSectionApplier;
use HongXunPan\EloquentQueryDsl\Section\DslSearchSectionApplier;
use HongXunPan\EloquentQueryDsl\Section\DslSectionApplier;
use HongXunPan\EloquentQueryDsl\Section\DslSortSectionApplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Query DSL 内核入口。
 *
 * 内核只负责 section applier 执行顺序；
 * 具体 section 的解析、条件生成与 Builder 应用由各自 applier 承担。
 * 使用侧默认通过 QueryDsl 主入口间接使用，不直接依赖具体 section 细节。
 *
 * @internal
 */
class QueryDslKernel
{
    public const STAGE = 'section_applier';

    /**
     * @var DslSectionApplier[]|null
     */
    protected ?array $sectionAppliers = null;

    /**
     * @param DslSectionApplier[]|null $sectionAppliers
     */
    public function __construct(?array $sectionAppliers = null)
    {
        if ($sectionAppliers !== null) {
            $this->sectionAppliers = $this->normalizeSectionAppliers($sectionAppliers);
        }
    }

    /**
     * 返回当前内核阶段标识，便于后续最小探针或调试时确认入口已接入。
     */
    public function stage(): string
    {
        return self::STAGE;
    }

    /**
     * 将查询输入按 Definition 应用到 Builder。
     *
     * @template TModel of Model
     * @param Builder<TModel> $query
     * @return Builder<TModel>
     */
    public function apply(Builder $query, DslQueryDefinition $definition, DslQueryInput $input): Builder
    {
        return $this->applyContext(
            $query,
            DslQueryRequestContext::fromQueryInput($definition, $input),
        );
    }

    /**
     * 将请求上下文应用到 Builder。
     *
     * @template TModel of Model
     * @param Builder<TModel> $query
     * @return Builder<TModel>
     */
    public function applyContext(Builder $query, DslQueryRequestContext $context): Builder
    {
        foreach ($this->sectionAppliers() as $applier) {
            $applier->apply($query, $context);
        }

        return $query;
    }

    /**
     * @return DslSectionApplier[]
     */
    protected function sectionAppliers(): array
    {
        if ($this->sectionAppliers === null) {
            $this->sectionAppliers = $this->defaultSectionAppliers();
        }

        return $this->sectionAppliers;
    }

    /**
     * @return DslSectionApplier[]
     */
    protected function defaultSectionAppliers(): array
    {
        return [
            new DslSearchSectionApplier(),
            new DslFilterSectionApplier(),
            new DslBetweenSectionApplier(),
            new DslSortSectionApplier(),
        ];
    }

    /**
     * @param array<int, mixed> $sectionAppliers
     * @return DslSectionApplier[]
     */
    protected function normalizeSectionAppliers(array $sectionAppliers): array
    {
        foreach ($sectionAppliers as $applier) {
            if (!$applier instanceof DslSectionApplier) {
                throw DslQueryDslRuntimeException::fromMessage('query dsl section applier 类型错误');
            }
        }

        return array_values($sectionAppliers);
    }
}
