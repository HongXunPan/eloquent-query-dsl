<?php

declare(strict_types=1);

namespace HongXunPan\EloquentQueryDsl\Tests\Feature;

use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefinition;
use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslDefinitionException;
use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslException;
use HongXunPan\EloquentQueryDsl\Input\Contract\DslInputParser;
use HongXunPan\EloquentQueryDsl\Input\DslInputMap;
use HongXunPan\EloquentQueryDsl\Input\DslQueryInput;
use HongXunPan\EloquentQueryDsl\Page\DslPageInput;
use HongXunPan\EloquentQueryDsl\Page\DslPaginationPolicy;
use HongXunPan\EloquentQueryDsl\QueryDsl;
use HongXunPan\EloquentQueryDsl\Tests\Support\FakeDslFilterNormalizer;
use HongXunPan\EloquentQueryDsl\Tests\Support\TestDatabase;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

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
            ->allowFilter(['status'])
            ->filterRules(['status' => 'trim'])
            ->allowSort(['sort_order']);

        $result = QueryDsl::for(QueryDslEntryTestArticle::query(), $definition)
            ->from([
                'title' => 'Hello',
                'where_status' => ' published ',
                'sort' => [
                    ['field' => 'sort_order', 'order' => 'desc'],
                ],
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

    public function testCursorFactsUseIndependentInputAndRejectPageMixing(): void
    {
        $definition = DslQueryDefinition::make('article')
            ->allowSort(['sort_order'])
            ->defaultSort('sort_order', 'desc')
            ->defaultSort('id', 'desc');

        $result = QueryDsl::for(QueryDslEntryTestArticle::query(), $definition)
            ->from([
                'cursor' => [
                    'limit' => 10,
                    'position' => ['sort_order' => 20, 'id' => 3],
                ],
            ])
            ->apply();

        $cursor = $result->cursor();
        $this->assertSame(10, $cursor->limit());
        $this->assertTrue($cursor->cursor()?->pointsToNextItems());

        $this->expectException(DslQueryDslException::class);
        QueryDsl::for(QueryDslEntryTestArticle::query(), $definition)
            ->from([
                'page' => ['page' => 1, 'limit' => 20],
                'cursor' => ['limit' => 20],
            ])
            ->apply();
    }

    public function testStructuredCursorDrivesNextAndPreviousQueries(): void
    {
        $definition = DslQueryDefinition::make('article')
            ->defaultSort('sort_order', 'desc')
            ->defaultSort('id', 'desc');

        $firstResult = QueryDsl::for(QueryDslEntryTestArticle::query(), $definition)
            ->from(['cursor' => ['limit' => 1]])
            ->apply();
        $firstCursor = $firstResult->cursor();
        $firstPage = $firstResult->builder()->cursorPaginate(
            $firstCursor->limit(),
            ['*'],
            'cursor',
            $firstCursor->cursor(),
        );

        $this->assertSame([3], $this->paginatorIds($firstPage));

        $secondResult = QueryDsl::for(QueryDslEntryTestArticle::query(), $definition)
            ->from(['cursor' => array_merge(
                ['limit' => 1],
                $firstCursor->toPayload($firstPage->nextCursor()) ?? [],
            )])
            ->apply();
        $secondCursor = $secondResult->cursor();
        $secondPage = $secondResult->builder()->cursorPaginate(
            $secondCursor->limit(),
            ['*'],
            'cursor',
            $secondCursor->cursor(),
        );

        $this->assertSame([1], $this->paginatorIds($secondPage));

        $previousResult = QueryDsl::for(QueryDslEntryTestArticle::query(), $definition)
            ->from(['cursor' => array_merge(
                ['limit' => 1],
                $secondCursor->toPayload($secondPage->previousCursor()) ?? [],
            )])
            ->apply();
        $previousCursor = $previousResult->cursor();
        $previousPage = $previousResult->builder()->cursorPaginate(
            $previousCursor->limit(),
            ['*'],
            'cursor',
            $previousCursor->cursor(),
        );

        $this->assertSame([3], $this->paginatorIds($previousPage));
    }

    public function testInputMapCanRenameCursorParameter(): void
    {
        $result = QueryDsl::for(
            QueryDslEntryTestArticle::query(),
            DslQueryDefinition::make('article')->defaultSort('id', 'desc'),
        )->from(
            ['after' => ['limit' => 10]],
            DslInputMap::make()->cursor('after'),
        )->apply();

        $this->assertSame(10, $result->cursor()->limit());
    }

    public function testApplyRejectsOverlappingPageAndCursorMappings(): void
    {
        $this->expectException(DslQueryDslDefinitionException::class);

        QueryDsl::for(
            QueryDslEntryTestArticle::query(),
            DslQueryDefinition::make('article')->defaultSort('id', 'desc'),
        )->from(
            ['pagination' => []],
            DslInputMap::make()
                ->page('pagination')
                ->cursor('pagination'),
        )->apply();
    }

    public function testParserPageDefaultsDoNotSelectPageMode(): void
    {
        $result = QueryDsl::for(
            QueryDslEntryTestArticle::query(),
            DslQueryDefinition::make('article')->defaultSort('id', 'desc'),
        )->from(['cursor' => ['limit' => 10]])
            ->inputParser(new QueryDslEntryDefaultPageInputParser())
            ->apply();

        $this->assertSame(10, $result->cursor()->limit());
    }

    public function testCursorRejectsStringTransportInput(): void
    {
        $this->expectException(DslQueryDslException::class);

        QueryDsl::for(
            QueryDslEntryTestArticle::query(),
            DslQueryDefinition::make('article')->defaultSort('id', 'desc'),
        )->from(['cursor' => '{"limit":10}'])->apply();
    }

    public function testPaginatorChecksPositionAgainstActualSortFields(): void
    {
        $result = QueryDsl::for(
            QueryDslEntryTestArticle::query(),
            DslQueryDefinition::make('article')
                ->defaultSort('sort_order', 'desc')
                ->defaultSort('id', 'desc'),
        )->from([
            'cursor' => [
                'limit' => 1,
                'position' => ['id' => 3],
            ],
        ])->apply();
        $cursor = $result->cursor();

        $this->expectException(UnexpectedValueException::class);
        $result->builder()->cursorPaginate(
            $cursor->limit(),
            ['*'],
            'cursor',
            $cursor->cursor(),
        );
    }

    public function testPageFactsRejectCursorInput(): void
    {
        $result = QueryDsl::for(
            QueryDslEntryTestArticle::query(),
            DslQueryDefinition::make('article')->defaultSort('id', 'desc'),
        )->from(['cursor' => ['limit' => 10]])->apply();

        $this->expectException(DslQueryDslException::class);
        $result->pagination();
    }

    public function testCursorFactsRejectPageInput(): void
    {
        $result = QueryDsl::for(
            QueryDslEntryTestArticle::query(),
            DslQueryDefinition::make('article')->defaultSort('id', 'desc'),
        )->from(['page' => ['page' => 1, 'limit' => 10]])->apply();

        $this->expectException(DslQueryDslException::class);
        $result->cursor();
    }

    public function testEmptyPageAndCursorInputsAreStillMutuallyExclusive(): void
    {
        $this->expectException(DslQueryDslException::class);

        QueryDsl::for(
            QueryDslEntryTestArticle::query(),
            DslQueryDefinition::make('article')->defaultSort('id', 'desc'),
        )->from(['page' => [], 'cursor' => []])->apply();
    }

    public function testNullPageAndCursorInputsAreStillMutuallyExclusive(): void
    {
        $this->expectException(DslQueryDslException::class);

        QueryDsl::for(
            QueryDslEntryTestArticle::query(),
            DslQueryDefinition::make('article')->defaultSort('id', 'desc'),
        )->from(['page' => null, 'cursor' => []])->apply();
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

    /** @return int[] */
    private function paginatorIds(CursorPaginator $paginator): array
    {
        return array_map(static function (mixed $item): int {
            assert($item instanceof Model);

            return (int)$item->getAttribute('id');
        }, $paginator->items());
    }
}

final class QueryDslEntryTestArticle extends Model
{
    protected $table = 'dsl_core_regression_articles';
    public $timestamps = false;
    protected $guarded = [];
}

final class QueryDslEntryDefaultPageInputParser implements DslInputParser
{
    public function queryInput(array $params, DslInputMap $inputMap): DslQueryInput
    {
        return DslQueryInput::fromRaw([]);
    }

    public function pageInput(array $params, DslInputMap $inputMap): DslPageInput
    {
        return DslPageInput::fromRaw(['page' => 1, 'limit' => 20]);
    }
}
