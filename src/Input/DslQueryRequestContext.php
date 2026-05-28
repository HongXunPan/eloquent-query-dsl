<?php

namespace HongXunPan\EloquentQueryDsl\Input;

use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefinition;
use HongXunPan\EloquentQueryDsl\Filter\DslFilterValues;
use HongXunPan\EloquentQueryDsl\Page\DslPageInput;
use HongXunPan\EloquentQueryDsl\Page\DslPaginationPolicy;
use HongXunPan\EloquentQueryDsl\Page\DslPaginationRequest;

/**
 * Query DSL 单次请求只读上下文。
 *
 * 该对象用于在一次 DSL 解析/执行过程中复用：
 * - definition
 * - queryInput
 * - pageInput
 * - paginationRequest
 * - filterValues
 *
 * 它不承接匿名 array 黑盒，只暴露具名只读事实。
 * 默认属于 内核 / 使用侧扩展使用的请求级扩展对象，不作为普通业务层直接 new 的入口。
 */
class DslQueryRequestContext
{
    protected DslQueryDefinition $definition;

    /**
     * @var array<string, mixed>
     */
    protected array $requestParams;

    protected ?DslQueryInput $queryInput;
    protected ?DslPageInput $pageInput;
    protected ?DslPaginationPolicy $paginationPolicy;
    protected ?DslPaginationRequest $paginationRequest = null;
    protected ?DslFilterValues $filterValues = null;

    /**
     * @param array<string, mixed> $requestParams
     */
    private function __construct(
        DslQueryDefinition $definition,
        array $requestParams = [],
        ?DslQueryInput $queryInput = null,
        ?DslPageInput $pageInput = null,
        ?DslPaginationPolicy $paginationPolicy = null,
    ) {
        $this->definition = $definition;
        $this->requestParams = $requestParams;
        $this->queryInput = $queryInput;
        $this->pageInput = $pageInput;
        $this->paginationPolicy = $paginationPolicy;
    }

    /**
     * @param array<string, mixed> $requestParams
     */
    public static function fromRequestParams(
        DslQueryDefinition $definition,
        array $requestParams,
        ?DslPaginationPolicy $paginationPolicy = null,
    ): self {
        return new self($definition, $requestParams, paginationPolicy: $paginationPolicy);
    }

    public static function fromQueryInput(
        DslQueryDefinition $definition,
        DslQueryInput $queryInput,
        ?DslPageInput $pageInput = null,
        ?DslPaginationPolicy $paginationPolicy = null,
    ): self {
        return new self($definition, [], $queryInput, $pageInput, $paginationPolicy);
    }

    public function definition(): DslQueryDefinition
    {
        return $this->definition;
    }

    public function queryInput(): DslQueryInput
    {
        if ($this->queryInput === null) {
            $this->queryInput = DslQueryInput::fromRequestParams($this->requestParams);
        }

        return $this->queryInput;
    }

    public function pageInput(): DslPageInput
    {
        if ($this->pageInput === null) {
            $this->pageInput = DslPageInput::fromRequestParams($this->requestParams);
        }

        return $this->pageInput;
    }

    public function paginationRequest(): DslPaginationRequest
    {
        if ($this->paginationRequest === null) {
            $this->paginationRequest = DslPaginationRequest::fromPageInput(
                $this->pageInput(),
                $this->paginationPolicy,
            );
        }

        return $this->paginationRequest;
    }

    public function rememberFilterValues(DslFilterValues $filterValues): DslFilterValues
    {
        $this->filterValues = $filterValues;

        return $this->filterValues;
    }

    public function filterValuesUsing(callable $resolver): DslFilterValues
    {
        if ($this->filterValues === null) {
            $this->filterValues = $resolver(
                $this->definition(),
                $this->queryInput(),
            );
        }

        return $this->filterValues;
    }

    public function filterValues(): ?DslFilterValues
    {
        return $this->filterValues;
    }
}
