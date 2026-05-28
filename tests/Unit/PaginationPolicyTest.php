<?php

declare(strict_types=1);

namespace HongXunPan\EloquentQueryDsl\Tests\Unit;

use HongXunPan\EloquentQueryDsl\Page\DslPageInput;
use HongXunPan\EloquentQueryDsl\Page\DslPaginationPolicy;
use HongXunPan\EloquentQueryDsl\Page\DslPaginationRequest;
use PHPUnit\Framework\TestCase;

final class PaginationPolicyTest extends TestCase
{
    public function testDefaultPolicyLimitsPageFacts(): void
    {
        $request = DslPaginationRequest::fromPageInput(
            DslPageInput::fromRaw(['page' => '2', 'limit' => '500'], '5000'),
        );

        $this->assertSame(1, $request->page());
        $this->assertSame(1000, $request->limit());
        $this->assertSame(1000, $request->exportLimit());
    }

    public function testCustomPolicyCanDisableExportLimit(): void
    {
        $request = DslPaginationRequest::fromPageInput(
            DslPageInput::fromRaw(['page' => '2', 'limit' => '80'], '150'),
            DslPaginationPolicy::default()
                ->withMaxLimit(50)
                ->withoutExportLimit(),
        );

        $this->assertSame(2, $request->page());
        $this->assertSame(50, $request->limit());
        $this->assertNull($request->exportLimit());
    }

    public function testFloatAndBoolAreInvalidByDefault(): void
    {
        $request = DslPaginationRequest::fromPageInput(
            DslPageInput::fromRaw(['page' => '1.5', 'limit' => true], 99.9),
        );

        $this->assertSame(1, $request->page());
        $this->assertSame(20, $request->limit());
        $this->assertNull($request->exportLimit());
    }
}
