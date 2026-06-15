<?php

declare(strict_types=1);

namespace HongXunPan\EloquentQueryDsl\Tests\Unit;

use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefinition;
use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslDefinitionException;
use PHPUnit\Framework\TestCase;

final class IdentifierSecurityTest extends TestCase
{
    public function testDefinitionRejectsUnsafeIdentifiers(): void
    {
        $this->expectException(DslQueryDslDefinitionException::class);
        $this->expectExceptionMessage('主实体格式错误');

        DslQueryDefinition::make('article;drop');
    }

    public function testFieldRejectsExpressionLikeValue(): void
    {
        $this->expectException(DslQueryDslDefinitionException::class);
        $this->expectExceptionMessage('字段格式错误');

        DslQueryDefinition::make('article')->allowFilter(['count(*)']);
    }

    public function testRelationAcceptsSafePath(): void
    {
        $definition = DslQueryDefinition::make('article')
            ->relation('comments', 'comments.author');

        self::assertSame('comments.author', $definition->relationFor('comments')?->relation());
    }

    public function testRelationRejectsUnsafePathSegment(): void
    {
        $this->expectException(DslQueryDslDefinitionException::class);
        $this->expectExceptionMessage('relation格式错误');

        DslQueryDefinition::make('article')->relation('comments', 'comments.author;drop');
    }
}
