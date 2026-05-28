<?php

namespace HongXunPan\EloquentQueryDsl\Page;

/**
 * Query DSL 对象化分页事实。
 *
 * 该对象承接 page / export_limit 解析后的稳定分页事实，
 * 供后续试点 Service 读取，不再在业务层长期传递分页匿名数组。
 */
class DslPaginationRequest
{
    public const DEFAULT_PAGE = 1;
    public const DEFAULT_LIMIT = 20;

    protected int $page;
    protected int $limit;
    protected ?int $exportLimit;

    private function __construct(int $page, int $limit, ?int $exportLimit)
    {
        $this->page = $page;
        $this->limit = $limit;
        $this->exportLimit = $exportLimit;
    }

    public static function fromPageInput(DslPageInput $input, ?DslPaginationPolicy $policy = null): self
    {
        $policy ??= DslPaginationPolicy::default();
        $page = $policy->normalizePage($input->pageValue()) ?? $policy->defaultPage();
        $limit = $policy->normalizeLimit($input->limitValue()) ?? $policy->defaultLimit();
        $exportLimit = $policy->normalizeExportLimit($input->rawExportLimit());

        if ($exportLimit !== null) {
            $page = $policy->defaultPage();
            $limit = $exportLimit;
        }

        return new self($page, $limit, $exportLimit);
    }

    public static function default(): self
    {
        return new self(self::DEFAULT_PAGE, self::DEFAULT_LIMIT, null);
    }

    public function page(): int
    {
        return $this->page;
    }

    public function limit(): int
    {
        return $this->limit;
    }

    public function exportLimit(): ?int
    {
        return $this->exportLimit;
    }

    public function hasExportLimit(): bool
    {
        return $this->exportLimit !== null;
    }

    public function isFirstPage(): bool
    {
        return $this->page === self::DEFAULT_PAGE;
    }

}
