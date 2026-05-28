<?php

namespace HongXunPan\EloquentQueryDsl\Page;

use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslException;

/**
 * Query DSL 分页输入对象。
 *
 * 该对象只负责解析外部 page 协议，不参与 query section 解析，
 * 也不直接承接分页查询执行。
 */
class DslPageInput
{
    /**
     * @var array<string, mixed>
     */
    protected array $pagePayload;

    protected mixed $rawExportLimit;

    /**
     * @param array<string, mixed> $pagePayload
     */
    private function __construct(array $pagePayload, mixed $rawExportLimit)
    {
        $this->pagePayload = $pagePayload;
        $this->rawExportLimit = $rawExportLimit;
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function fromRequestParams(array $params): self
    {
        return self::fromRaw(
            $params['page'] ?? null,
            $params['export_limit'] ?? null,
        );
    }

    public static function fromRaw(mixed $rawPage, mixed $rawExportLimit = null): self
    {
        return new self(
            self::decodeRawPage($rawPage),
            $rawExportLimit,
        );
    }

    public static function empty(): self
    {
        return new self([], null);
    }

    public function isEmpty(): bool
    {
        return $this->pagePayload === [] && !$this->hasExportLimit();
    }

    /**
     * @return array<string, mixed>
     */
    public function pagePayload(): array
    {
        return $this->pagePayload;
    }

    public function pageValue(): mixed
    {
        return $this->pagePayload['page'] ?? null;
    }

    public function limitValue(): mixed
    {
        return $this->pagePayload['limit'] ?? null;
    }

    public function hasExportLimit(): bool
    {
        return $this->rawExportLimit !== null && $this->rawExportLimit !== '';
    }

    public function rawExportLimit(): mixed
    {
        return $this->rawExportLimit;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function decodeRawPage(mixed $rawPage): array
    {
        if ($rawPage === null || $rawPage === '') {
            return [];
        }

        if (is_array($rawPage)) {
            return self::assertTopLevelMap($rawPage);
        }

        if (!is_string($rawPage)) {
            throw DslQueryDslException::invalidPageFormat();
        }

        $rawPage = trim($rawPage);
        if ($rawPage === '') {
            return [];
        }

        if (!str_starts_with($rawPage, '{')) {
            throw DslQueryDslException::invalidPageFormat();
        }

        $decoded = json_decode($rawPage, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw DslQueryDslException::invalidPageFormat();
        }

        return self::assertTopLevelMap($decoded);
    }

    /**
     * @param array<string, mixed> $pageData
     * @return array<string, mixed>
     */
    protected static function assertTopLevelMap(array $pageData): array
    {
        foreach ($pageData as $name => $_) {
            if (!is_string($name) || trim($name) === '') {
                throw DslQueryDslException::invalidPageFormat();
            }
        }

        return $pageData;
    }
}
