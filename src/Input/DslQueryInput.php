<?php

namespace HongXunPan\EloquentQueryDsl\Input;

use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslException;

/**
 * QueryDSL V2 查询输入对象。
 *
 * QueryInput 只负责把外部 query 解析成顶层 section 集合，不关心 section 的业务语义和内部结构。
 */
class DslQueryInput
{
    /**
     * @var array<string, DslQuerySection>
     */
    protected array $sections;

    /**
     * @param array<string, DslQuerySection> $sections
     */
    private function __construct(array $sections)
    {
        $this->sections = $sections;
    }

    /**
     * 从请求参数数组中读取 query 并解析。
     */
    public static function fromRequestParams(array $params): self
    {
        return self::fromRaw($params['query'] ?? null);
    }

    /**
     * 从原始 query 值解析顶层 section。
     *
     * 只接受空值、数组或 JSON object 字符串；section 名称和 section 值结构由后续 Definition 继续约束。
     */
    public static function fromRaw(mixed $rawQuery): self
    {
        $decoded = self::decodeRawQuery($rawQuery);

        return new self(self::makeSections($decoded));
    }

    /**
     * 返回空查询输入。
     */
    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * 判断是否不包含任何 section。
     */
    public function isEmpty(): bool
    {
        return $this->sections === [];
    }

    /**
     * 判断是否存在指定 section。
     */
    public function has(string $name): bool
    {
        return array_key_exists(self::normalizeSectionName($name), $this->sections);
    }

    /**
     * 获取指定 section；不存在时返回 null。
     */
    public function get(string $name): ?DslQuerySection
    {
        $name = self::normalizeSectionName($name);

        return $this->sections[$name] ?? null;
    }

    /**
     * 返回所有 section，以 section 名称为 key。
     *
     * @return array<string, DslQuerySection>
     */
    public function all(): array
    {
        return $this->sections;
    }

    /**
     * 返回所有 section 名称。
     */
    public function names(): array
    {
        return array_keys($this->sections);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function decodeRawQuery(mixed $rawQuery): array
    {
        if ($rawQuery === null || $rawQuery === '') {
            return [];
        }

        if (is_array($rawQuery)) {
            return self::assertTopLevelMap($rawQuery);
        }

        if (!is_string($rawQuery)) {
            throw DslQueryDslException::invalidQueryFormat();
        }

        $rawQuery = trim($rawQuery);
        if ($rawQuery === '') {
            return [];
        }

        if (!str_starts_with($rawQuery, '{')) {
            throw DslQueryDslException::invalidQueryFormat();
        }

        $decoded = json_decode($rawQuery, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw DslQueryDslException::invalidQueryFormat();
        }

        return self::assertTopLevelMap($decoded);
    }

    /**
     * @param array<string, mixed> $queryData
     * @return array<string, DslQuerySection>
     */
    protected static function makeSections(array $queryData): array
    {
        $sections = [];
        foreach ($queryData as $name => $value) {
            if (!is_string($name)) {
                throw DslQueryDslException::invalidQueryFormat();
            }

            $section = DslQuerySection::make($name, $value);
            if (array_key_exists($section->name(), $sections)) {
                throw DslQueryDslException::invalidQueryFormat();
            }

            $sections[$section->name()] = $section;
        }

        return $sections;
    }

    /**
     * @param array<string, mixed> $queryData
     * @return array<string, mixed>
     */
    protected static function assertTopLevelMap(array $queryData): array
    {
        foreach ($queryData as $name => $_) {
            if (!is_string($name) || trim($name) === '') {
                throw DslQueryDslException::invalidQueryFormat();
            }
        }

        return $queryData;
    }

    protected static function normalizeSectionName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            throw DslQueryDslException::emptyQuerySectionName();
        }

        return $name;
    }
}
