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

/**
 * QueryDSL V2 最小内核入口。
 *
 * 内核只负责固定 V2 入口与 section applier 执行顺序；
 * 具体 section 的解析、条件生成与 Builder 应用由各自 applier 承担。
 * 使用侧默认通过 QueryDsl 主入口间接使用，不直接依赖具体 section 细节。
 */
class QueryDslV2Kernel
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
     * 返回当前 V2 内核阶段标识，便于后续最小探针或调试时确认入口已接入。
     */
    public function stage(): string
    {
        return self::STAGE;
    }

    /**
     * 将 V2 查询输入按 Definition 应用到 Builder。
     */
    public function apply(Builder $query, DslQueryDefinition $definition, DslQueryInput $input): Builder
    {
        return $this->applyContext(
            $query,
            DslQueryRequestContext::fromQueryInput($definition, $input)
        );
    }

    /**
     * 将 V2 请求上下文应用到 Builder。
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
