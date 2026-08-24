<?php

namespace HongXunPan\EloquentQueryDsl\Input;

use HongXunPan\EloquentQueryDsl\Cursor\DslCursorRequest;
use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefinition;
use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslException;
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
    protected bool $pageProvided;
    protected bool $cursorProvided;

    /** @var array<array-key, mixed> */
    protected array $cursorInput;

    /**
     * @param array<string, mixed> $requestParams
     * @param array<array-key, mixed> $cursorInput
     */
    private function __construct(
        DslQueryDefinition $definition,
        array $requestParams = [],
        ?DslQueryInput $queryInput = null,
        ?DslPageInput $pageInput = null,
        ?DslPaginationPolicy $paginationPolicy = null,
        bool $pageProvided = false,
        bool $cursorProvided = false,
        array $cursorInput = [],
    ) {
        $this->definition = $definition;
        $this->requestParams = $requestParams;
        $this->queryInput = $queryInput;
        $this->pageInput = $pageInput;
        $this->paginationPolicy = $paginationPolicy;
        $this->pageProvided = $pageProvided;
        $this->cursorProvided = $cursorProvided;
        $this->cursorInput = $cursorInput;
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

    /** @param array<array-key, mixed> $cursorInput */
    public static function fromQueryInput(
        DslQueryDefinition $definition,
        DslQueryInput $queryInput,
        ?DslPageInput $pageInput = null,
        ?DslPaginationPolicy $paginationPolicy = null,
        bool $pageProvided = false,
        bool $cursorProvided = false,
        array $cursorInput = [],
    ): self {
        return new self(
            $definition,
            [],
            $queryInput,
            $pageInput,
            $paginationPolicy,
            $pageProvided,
            $cursorProvided,
            $cursorInput,
        );
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
        if ($this->cursorProvided) {
            throw DslQueryDslException::unexpectedPaginationMode('page', 'cursor');
        }

        if ($this->paginationRequest === null) {
            $this->paginationRequest = DslPaginationRequest::fromPageInput(
                $this->pageInput(),
                $this->paginationPolicy,
            );
        }

        return $this->paginationRequest;
    }

    public function cursorRequest(): DslCursorRequest
    {
        if ($this->pageProvided) {
            throw DslQueryDslException::unexpectedPaginationMode('cursor', 'page');
        }

        return DslCursorRequest::fromArray($this->cursorInput, $this->paginationPolicy);
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
