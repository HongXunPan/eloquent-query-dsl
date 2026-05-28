<?php

namespace HongXunPan\EloquentQueryDsl;

use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefinition;
use HongXunPan\EloquentQueryDsl\Filter\Contract\DslFilterNormalizer;
use HongXunPan\EloquentQueryDsl\Input\Contract\DslInputParser;
use HongXunPan\EloquentQueryDsl\Input\DefaultDslInputParser;
use HongXunPan\EloquentQueryDsl\Input\DslInputMap;
use HongXunPan\EloquentQueryDsl\Input\DslQueryRequestContext;
use HongXunPan\EloquentQueryDsl\Kernel\QueryDslKernel;
use HongXunPan\EloquentQueryDsl\Page\DslPaginationPolicy;
use HongXunPan\EloquentQueryDsl\Section\DslBetweenSectionApplier;
use HongXunPan\EloquentQueryDsl\Section\DslFilterSectionApplier;
use HongXunPan\EloquentQueryDsl\Section\DslSearchSectionApplier;
use HongXunPan\EloquentQueryDsl\Section\DslSortSectionApplier;
use Illuminate\Database\Eloquent\Builder;

/**
 * QueryDSL 推荐主入口。
 *
 * 该入口负责中性的输入解析、section wiring 与请求上下文编排；
 * 使用侧仍负责 HTTP request、项目 validator adapter、异常翻译与响应结构。
 */
class QueryDsl
{
    private array $params = [];
    private ?DslInputMap $inputMap = null;
    private ?DslInputParser $inputParser = null;
    private ?DslFilterNormalizer $filterNormalizer = null;
    private ?DslPaginationPolicy $paginationPolicy = null;

    private function __construct(
        private Builder $builder,
        private DslQueryDefinition $definition
    ) {
    }

    public static function for(Builder $builder, DslQueryDefinition $definition): self
    {
        return new self($builder, $definition);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function from(array $params, ?DslInputMap $inputMap = null): self
    {
        $this->params = $params;
        if ($inputMap !== null) {
            $this->inputMap = $inputMap;
        }

        return $this;
    }

    public function inputMap(DslInputMap $inputMap): self
    {
        $this->inputMap = $inputMap;

        return $this;
    }

    public function inputParser(DslInputParser $inputParser): self
    {
        $this->inputParser = $inputParser;

        return $this;
    }

    public function filterNormalizer(DslFilterNormalizer $filterNormalizer): self
    {
        $this->filterNormalizer = $filterNormalizer;

        return $this;
    }

    public function paginationPolicy(DslPaginationPolicy $paginationPolicy): self
    {
        $this->paginationPolicy = $paginationPolicy;

        return $this;
    }

    public function apply(): QueryDslResult
    {
        $inputMap = $this->inputMap ?? DslInputMap::make();
        $inputParser = $this->inputParser ?? new DefaultDslInputParser();
        $context = DslQueryRequestContext::fromQueryInput(
            $this->definition,
            $inputParser->queryInput($this->params, $inputMap),
            $inputParser->pageInput($this->params, $inputMap),
            $this->paginationPolicy
        );

        $this->kernel()->applyContext($this->builder, $context);

        return new QueryDslResult($this->builder, $context);
    }

    protected function kernel(): QueryDslKernel
    {
        $filterSectionApplier = new DslFilterSectionApplier(filterNormalizer: $this->filterNormalizer);

        return new QueryDslKernel([
            new DslSearchSectionApplier(),
            $filterSectionApplier,
            new DslBetweenSectionApplier(),
            new DslSortSectionApplier(filterSectionApplier: $filterSectionApplier),
        ]);
    }
}
