<?php

declare(strict_types=1);

namespace HongXunPan\EloquentQueryDsl\Tests\Unit;

use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefinition;
use HongXunPan\EloquentQueryDsl\Filter\Contract\DslFilterNormalizer;
use HongXunPan\EloquentQueryDsl\Input\Contract\DslInputParser;
use HongXunPan\EloquentQueryDsl\Input\DslInputMap;
use HongXunPan\EloquentQueryDsl\Kernel\QueryDslKernel;
use HongXunPan\EloquentQueryDsl\Kernel\QueryDslV2Kernel;
use HongXunPan\EloquentQueryDsl\Page\DslPaginationPolicy;
use HongXunPan\EloquentQueryDsl\QueryDsl;
use HongXunPan\EloquentQueryDsl\QueryDslResult;
use PHPUnit\Framework\TestCase;

final class PublicApiCompatibilityTest extends TestCase
{
    public function testRecommendedPublicApiCanBeLoaded(): void
    {
        $this->assertTrue(class_exists(QueryDsl::class));
        $this->assertTrue(class_exists(QueryDslResult::class));
        $this->assertTrue(class_exists(DslQueryDefinition::class));
        $this->assertTrue(class_exists(DslInputMap::class));
        $this->assertTrue(interface_exists(DslInputParser::class));
        $this->assertTrue(interface_exists(DslFilterNormalizer::class));
        $this->assertTrue(class_exists(DslPaginationPolicy::class));
    }

    public function testLegacyKernelNameRemainsInternalCompatible(): void
    {
        $this->assertSame(QueryDslKernel::STAGE, (new QueryDslV2Kernel([]))->stage());
    }
}
