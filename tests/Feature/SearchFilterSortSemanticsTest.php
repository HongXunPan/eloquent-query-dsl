<?php

declare(strict_types=1);

namespace HongXunPan\EloquentQueryDsl\Tests\Feature;

use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefaultSort;
use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefinition;
use HongXunPan\EloquentQueryDsl\Derived\DslFilterValueDerivedDefaultSortStrategy;
use HongXunPan\EloquentQueryDsl\Input\DslQueryInput;
use HongXunPan\EloquentQueryDsl\Kernel\QueryDslKernel;
use HongXunPan\EloquentQueryDsl\Section\DslBetweenSectionApplier;
use HongXunPan\EloquentQueryDsl\Section\DslFilterSectionApplier;
use HongXunPan\EloquentQueryDsl\Section\DslSearchSectionApplier;
use HongXunPan\EloquentQueryDsl\Section\DslSortSectionApplier;
use HongXunPan\EloquentQueryDsl\Tests\Support\FakeDslFilterNormalizer;
use HongXunPan\EloquentQueryDsl\Tests\Support\TestDatabase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

final class SearchFilterSortSemanticsTest extends TestCase
{
    private QueryDslKernel $kernel;
    private DslFilterSectionApplier $filterSectionApplier;

    protected function setUp(): void
    {
        TestDatabase::boot();

        $this->filterSectionApplier = new DslFilterSectionApplier(filterNormalizer: new FakeDslFilterNormalizer());
        $this->kernel = new QueryDslKernel([
            new DslSearchSectionApplier(),
            $this->filterSectionApplier,
            new DslBetweenSectionApplier(),
            new DslSortSectionApplier(filterSectionApplier: $this->filterSectionApplier),
        ]);
    }

    public function testFieldSearchUsesAndAndKeywordSearchUsesOr(): void
    {
        $fieldDefinition = DslQueryDefinition::make('article')
            ->strict(true)
            ->allowSearch(['title', 'code'])
            ->searchRightLike(['code']);

        $andQuery = $this->kernel->apply(PhpUnitArticle::query(), $fieldDefinition, DslQueryInput::fromRaw([
            'search' => ['title' => 'Hello', 'code' => 'AB001'],
        ]));

        $this->assertStringContainsString('title" like ?', $this->normalizeSql($andQuery));
        $this->assertStringContainsString('code" like ?', $this->normalizeSql($andQuery));
        $this->assertSame(['%Hello%', 'AB001%'], $andQuery->getBindings());
        $this->assertSame([1], $this->resultIds($andQuery));

        $keywordDefinition = DslQueryDefinition::make('article')
            ->strict(true)
            ->allowKeywordSearch('keyword', ['title', 'code']);

        $keywordQuery = $this->kernel->apply(PhpUnitArticle::query(), $keywordDefinition, DslQueryInput::fromRaw([
            'search' => ['keyword' => 'AB001'],
        ]));

        $this->assertStringContainsString(' or ', $this->normalizeSql($keywordQuery));
        $this->assertSame(['%AB001%', '%AB001%'], $keywordQuery->getBindings());
        $this->assertSame([1], $this->resultIds($keywordQuery));
    }

    public function testFilterKeepsZeroAndIgnoresEmptyValues(): void
    {
        $definition = DslQueryDefinition::make('article')->strict(true)->allowFilter(['id']);

        $filterValues = $this->filterSectionApplier->filterValues($definition, DslQueryInput::fromRaw([
            'filter' => ['id' => 0],
        ]));
        $this->assertSame(0, $filterValues->singleValue('id'));

        $query = $this->kernel->apply(PhpUnitArticle::query(), $definition, DslQueryInput::fromRaw([
            'filter' => ['id' => [1, '', null, 0]],
        ]));
        $this->assertSame([1, 0], $query->getBindings());

        $emptyQuery = $this->kernel->apply(PhpUnitArticle::query(), $definition, DslQueryInput::fromRaw([
            'filter' => ['id' => false],
        ]));
        $this->assertStringNotContainsString('where', $this->normalizeSql($emptyQuery));
    }

    public function testSortPriorityAndDirectionPolicy(): void
    {
        $definition = DslQueryDefinition::make('article')
            ->strict(true)
            ->allowFilter(['tab'])
            ->allowSort(['id'])
            ->allowDerivedDefaultSortByFilter('tab', DslFilterValueDerivedDefaultSortStrategy::make()
                ->when('pending', DslQueryDefaultSort::asc('id', 'article')))
            ->defaultSort('sort_order', 'desc');

        $derivedQuery = $this->kernel->apply(PhpUnitArticle::query(), $definition, DslQueryInput::fromRaw([
            'filter' => ['tab' => 'pending'],
        ]));
        $this->assertStringContainsString('order by "id" asc', $this->normalizeSql($derivedQuery));
        $this->assertStringNotContainsString('sort_order', $this->normalizeSql($derivedQuery));

        $explicitQuery = $this->kernel->apply(PhpUnitArticle::query(), $definition, DslQueryInput::fromRaw([
            'sort' => [['field' => 'id', 'order' => 'desc']],
        ]));
        $this->assertStringContainsString('order by "id" desc', $this->normalizeSql($explicitQuery));
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

final class PhpUnitArticle extends Model
{
    protected $table = 'dsl_core_regression_articles';
    public $timestamps = false;
    protected $guarded = [];
}
