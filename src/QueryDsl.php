<?php

namespace HongXunPan\EloquentQueryDsl;

use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefinition;
use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslException;
use HongXunPan\EloquentQueryDsl\Filter\Contract\DslFilterNormalizer;
use HongXunPan\EloquentQueryDsl\Input\Contract\DslInputParser;
use HongXunPan\EloquentQueryDsl\Input\DefaultDslInputParser;
use HongXunPan\EloquentQueryDsl\Input\DslInputMap;
use HongXunPan\EloquentQueryDsl\Input\DslQueryRequestContext;
use HongXunPan\EloquentQueryDsl\Input\Internal\DslInputParams;
use HongXunPan\EloquentQueryDsl\Kernel\QueryDslKernel;
use HongXunPan\EloquentQueryDsl\Page\DslPaginationPolicy;
use HongXunPan\EloquentQueryDsl\Section\DslBetweenSectionApplier;
use HongXunPan\EloquentQueryDsl\Section\DslFilterSectionApplier;
use HongXunPan\EloquentQueryDsl\Section\DslSearchSectionApplier;
use HongXunPan\EloquentQueryDsl\Section\DslSortSectionApplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * QueryDSL 推荐主入口。
 *
 * 该入口负责中性的输入解析、section wiring 与请求上下文编排；
 * 使用侧仍负责 HTTP request、项目 validator adapter、异常翻译与响应结构。
 */
class QueryDsl
{
    /**
     * @var array<string, mixed>
     */
    private array $params = [];

    /**
     * @var Builder<TModel>
     */
    private Builder $builder;

    private DslQueryDefinition $definition;
    private ?DslInputMap $inputMap = null;
    private ?DslInputParser $inputParser = null;
    private ?DslFilterNormalizer $filterNormalizer = null;
    private ?DslPaginationPolicy $paginationPolicy = null;

    /**
     * @param Builder<TModel> $builder
     */
    private function __construct(Builder $builder, DslQueryDefinition $definition)
    {
        $this->builder = $builder;
        $this->definition = $definition;
    }

    /**
     * @template TBuilderModel of Model
     * @param Builder<TBuilderModel> $builder
     * @return self<TBuilderModel>
     */
    public static function for(Builder $builder, DslQueryDefinition $definition): self
    {
        /** @var self<TBuilderModel> $queryDsl */
        $queryDsl = new self($builder, $definition);

        return $queryDsl;
    }

    /**
     * @param array<string, mixed> $params
     * @return self<TModel>
     */
    public function from(array $params, ?DslInputMap $inputMap = null): self
    {
        $this->params = $params;
        if ($inputMap !== null) {
            $this->inputMap = $inputMap;
        }

        return $this;
    }

    /**
     * @return self<TModel>
     */
    public function inputMap(DslInputMap $inputMap): self
    {
        $this->inputMap = $inputMap;

        return $this;
    }

    /**
     * @return self<TModel>
     */
    public function inputParser(DslInputParser $inputParser): self
    {
        $this->inputParser = $inputParser;

        return $this;
    }

    /**
     * @return self<TModel>
     */
    public function filterNormalizer(DslFilterNormalizer $filterNormalizer): self
    {
        $this->filterNormalizer = $filterNormalizer;

        return $this;
    }

    /**
     * @return self<TModel>
     */
    public function paginationPolicy(DslPaginationPolicy $paginationPolicy): self
    {
        $this->paginationPolicy = $paginationPolicy;

        return $this;
    }

    /**
     * @return QueryDslResult<TModel>
     */
    public function apply(): QueryDslResult
    {
        $inputMap = $this->inputMap ?? DslInputMap::make();
        $inputMap->assertPaginationKeysValid();

        $params = DslInputParams::fromArray($this->params);
        $pageProvided = $params->hasAny(...$inputMap->pageKeys());
        $cursorProvided = $params->has($inputMap->cursorKey());
        if ($pageProvided && $cursorProvided) {
            throw DslQueryDslException::mixedPaginationModes();
        }

        $cursorInput = [];
        if ($cursorProvided) {
            $rawCursor = $params->get($inputMap->cursorKey());
            if (!is_array($rawCursor)) {
                throw DslQueryDslException::invalidCursorFormat();
            }

            $cursorInput = $rawCursor;
        }

        $inputParser = $this->inputParser ?? new DefaultDslInputParser();
        $pageInput = $inputParser->pageInput($this->params, $inputMap);
        $context = DslQueryRequestContext::fromQueryInput(
            $this->definition,
            $inputParser->queryInput($this->params, $inputMap),
            $pageInput,
            $this->paginationPolicy,
            $pageProvided,
            $cursorProvided,
            $cursorInput,
        );

        $this->kernel()->applyContext($this->builder, $context);

        /** @var QueryDslResult<TModel> $result */
        $result = new QueryDslResult($this->builder, $context);

        return $result;
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
