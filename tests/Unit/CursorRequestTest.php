<?php

declare(strict_types=1);

namespace HongXunPan\EloquentQueryDsl\Tests\Unit;

use HongXunPan\EloquentQueryDsl\Cursor\DslCursorRequest;
use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslException;
use HongXunPan\EloquentQueryDsl\Page\DslPaginationPolicy;
use Illuminate\Pagination\Cursor;
use PHPUnit\Framework\TestCase;

final class CursorRequestTest extends TestCase
{
    public function testStructuredCursorBuildsIlluminateCursor(): void
    {
        $request = DslCursorRequest::fromArray(
            [
                'limit' => '500',
                'position' => [
                    'event_at' => '2026-08-24 10:00:00',
                    'id' => 123,
                ],
                'direction' => 'previous',
            ],
            DslPaginationPolicy::default()->withMaxLimit(50),
        );

        $this->assertSame(50, $request->limit());
        $this->assertSame('previous', $request->direction());
        $this->assertSame(123, $request->position()['id'] ?? null);
        $this->assertTrue($request->cursor()?->pointsToPreviousItems());
        $this->assertSame('2026-08-24 10:00:00', $request->cursor()->parameter('event_at'));
    }

    public function testEmptyCursorUsesFirstPageFacts(): void
    {
        $request = DslCursorRequest::fromArray();

        $this->assertSame(20, $request->limit());
        $this->assertSame('next', $request->direction());
        $this->assertNull($request->position());
        $this->assertNull($request->cursor());
    }

    public function testCursorCanBeSerializedWithoutBase64(): void
    {
        $request = DslCursorRequest::fromArray();

        $this->assertSame(
            [
                'position' => [
                    'event_at' => '2026-08-24 10:00:00',
                    'id' => 123,
                ],
                'direction' => 'previous',
            ],
            $request->toPayload(new Cursor([
                'event_at' => '2026-08-24 10:00:00',
                'id' => 123,
            ], false)),
        );
        $this->assertNull($request->toPayload(null));
    }

    /**
     * @dataProvider invalidCursorProvider
     * @param array<array-key, mixed> $cursor
     */
    public function testInvalidCursorIsRejected(array $cursor): void
    {
        $this->expectException(DslQueryDslException::class);

        DslCursorRequest::fromArray($cursor);
    }

    /**
     * @return array<string, array{array<array-key, mixed>}>
     */
    public function invalidCursorProvider(): array
    {
        return [
            '列表不是对象' => [['first', 'second']],
            '未知字段' => [['unknown' => 1]],
            'limit无效' => [['limit' => 0]],
            '方向没有位置' => [['direction' => 'previous']],
            '方向无效' => [[
                'position' => ['event_at' => '2026-08-24 10:00:00', 'id' => 123],
                'direction' => 'backward',
            ]],
            '位置为空' => [['position' => []]],
            '位置字段为空' => [['position' => ['' => 123]]],
            '位置包含复杂值' => [[
                'position' => ['event_at' => [], 'id' => 123],
            ]],
        ];
    }
}
