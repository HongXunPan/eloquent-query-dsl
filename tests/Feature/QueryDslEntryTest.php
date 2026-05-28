<?php

declare(strict_types=1);

namespace HongXunPan\EloquentQueryDsl\Tests\Feature;

use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefinition;
use HongXunPan\EloquentQueryDsl\Input\DslInputMap;
use HongXunPan\EloquentQueryDsl\Page\DslPaginationPolicy;
use HongXunPan\EloquentQueryDsl\QueryDsl;
use HongXunPan\EloquentQueryDsl\Tests\Support\FakeDslFilterNormalizer;
use HongXunPan\EloquentQueryDsl\Tests\Support\TestDatabase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

final class QueryDslEntryTest extends TestCase
{
    protected function setUp(): void
    {
        TestDatabase::boot();
    }

    public function testMainEntryAppliesQueryAndReturnsNeutralFacts(): void
    {
        $definition = DslQueryDefinition::make('article')
            ->strict(true)
            ->allowSearch(['title'])
            ->allowFilter(['status' => 'trim'])
            ->allowSort(['sort_order']);

        $result = QueryDsl::for(QueryDslEntryTestArticle::query(), $definition)
            ->from([
                'title' => 'Hello',
                'where_status' => ' published ',
                'sort' => '-sort_order',
                'page' => '2',
                'per_page' => '500',
            ], DslInputMap::make()
                ->flatInput()
                ->search('title')
                ->filterPrefix('where_')
                ->sort('sort')
                ->page('page')
                ->limit('per_page'))
            ->paginationPolicy(DslPaginationPolicy::default()->withMaxLimit(50))
            ->filterNormalizer(new FakeDslFilterNormalizer())
            ->apply();

        $this->assertStringContainsString('title" like ?', $this->normalizeSql($result->builder()));
        $this->assertStringContainsString('status" = ?', $this->normalizeSql($result->builder()));
        $this->assertStringContainsString('order by "sort_order" desc', $this->normalizeSql($result->builder()));
        $this->assertSame(['%Hello%', 'published'], $result->builder()->getBindings());
        $this->assertSame([1], $this->resultIds($result->builder()));
        $this->assertSame('published', $result->filterValues()->singleValue('status'));
        $this->assertSame(2, $result->pagination()->page());
        $this->assertSame(50, $result->pagination()->limit());
    }

    /**
     * @template TModel of Model
     * @param Builder<TModel> $query
     */
    private function normalizeSql(Builder $query): string
    {
        return (string)preg_replace('/\s+/', ' ', strtolower($query->toSql()));
    }

    /**
     * @template TModel of Model
     * @param Builder<TModel> $query
     * @return int[]
     */
    private function resultIds(Builder $query): array
    {
        return array_map('intval', $query->pluck('id')->all());
    }
}

final class QueryDslEntryTestArticle extends Model
{
    protected $table = 'dsl_core_regression_articles';
    public $timestamps = false;
    protected $guarded = [];
}
