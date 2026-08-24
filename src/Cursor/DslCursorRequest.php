<?php

namespace HongXunPan\EloquentQueryDsl\Cursor;

use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslException;
use HongXunPan\EloquentQueryDsl\Page\DslPaginationPolicy;
use Illuminate\Pagination\Cursor;

/**
 * 结构化游标分页事实；分页查询和响应外壳仍由应用负责。
 */
class DslCursorRequest
{
    public const DIRECTION_NEXT = 'next';
    public const DIRECTION_PREVIOUS = 'previous';

    private int $limit;
    private ?Cursor $cursor;

    private function __construct(int $limit, ?Cursor $cursor)
    {
        $this->limit = $limit;
        $this->cursor = $cursor;
    }

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload = [], ?DslPaginationPolicy $policy = null): self
    {
        foreach (array_keys($payload) as $key) {
            if (!is_string($key)) {
                throw DslQueryDslException::invalidCursorFormat();
            }
        }

        $unknown = array_diff(array_keys($payload), ['limit', 'position', 'direction']);
        if ($unknown !== []) {
            throw DslQueryDslException::invalidCursor('未开放字段：' . implode(',', $unknown));
        }

        $policy ??= DslPaginationPolicy::default();
        $limit = array_key_exists('limit', $payload)
            ? $policy->normalizeLimit($payload['limit'])
            : $policy->defaultLimit();
        if ($limit === null) {
            throw DslQueryDslException::invalidCursor('limit必须为正整数');
        }

        if (!array_key_exists('position', $payload)) {
            if (array_key_exists('direction', $payload)) {
                throw DslQueryDslException::invalidCursor('direction必须与position同时使用');
            }

            return new self($limit, null);
        }

        $direction = $payload['direction'] ?? self::DIRECTION_NEXT;
        if (!is_string($direction)
            || !in_array($direction, [self::DIRECTION_NEXT, self::DIRECTION_PREVIOUS], true)) {
            throw DslQueryDslException::invalidCursor('direction只支持next或previous');
        }

        return new self(
            $limit,
            new Cursor(
                self::normalizePosition($payload['position']),
                $direction === self::DIRECTION_NEXT,
            ),
        );
    }

    public function limit(): int
    {
        return $this->limit;
    }

    /** @return array<string, mixed>|null */
    public function position(): ?array
    {
        return $this->cursor === null ? null : self::cursorPosition($this->cursor);
    }

    public function direction(): string
    {
        return $this->cursor?->pointsToPreviousItems()
            ? self::DIRECTION_PREVIOUS
            : self::DIRECTION_NEXT;
    }

    public function cursor(): ?Cursor
    {
        return $this->cursor;
    }

    /** @return array{position: array<string, mixed>, direction: string}|null */
    public function toPayload(?Cursor $cursor): ?array
    {
        if ($cursor === null) {
            return null;
        }

        return [
            'position' => self::cursorPosition($cursor),
            'direction' => $cursor->pointsToNextItems()
                ? self::DIRECTION_NEXT
                : self::DIRECTION_PREVIOUS,
        ];
    }

    /** @return array<string, mixed> */
    private static function cursorPosition(Cursor $cursor): array
    {
        /** @var array<string, mixed> $position */
        $position = $cursor->toArray();
        unset($position['_pointsToNextItems']);

        return $position;
    }

    /** @return array<string, bool|float|int|string> */
    private static function normalizePosition(mixed $rawPosition): array
    {
        if (!is_array($rawPosition) || $rawPosition === []) {
            throw DslQueryDslException::invalidCursor('position必须为非空对象');
        }

        $position = [];
        foreach ($rawPosition as $field => $value) {
            if (!is_string($field) || trim($field) === '' || !is_scalar($value)) {
                throw DslQueryDslException::invalidCursor('position必须为字段与标量值组成的对象');
            }

            $position[$field] = $value;
        }

        return $position;
    }
}
