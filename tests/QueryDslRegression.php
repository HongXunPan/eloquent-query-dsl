<?php

declare(strict_types=1);

use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefinition;
use HongXunPan\EloquentQueryDsl\Input\Contract\DslInputParser;
use HongXunPan\EloquentQueryDsl\Input\DslInputMap;
use HongXunPan\EloquentQueryDsl\Input\DslQueryInput;
use HongXunPan\EloquentQueryDsl\Page\DslPageInput;
use HongXunPan\EloquentQueryDsl\Page\DslPaginationPolicy;
use HongXunPan\EloquentQueryDsl\QueryDsl;
use HongXunPan\EloquentQueryDsl\Tests\Support\Assert;
use HongXunPan\EloquentQueryDsl\Tests\Support\FakeDslFilterNormalizer;
use HongXunPan\EloquentQueryDsl\Tests\Support\TestDatabase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/Support/Assert.php';
require __DIR__ . '/Support/FakeDslFilterNormalizer.php';
require __DIR__ . '/Support/TestDatabase.php';

final class QueryDslEntryRegression
{
    public function run(): void
    {
        TestDatabase::boot();
        $this->testDefaultEntryWithCustomInputMap();
        $this->testCustomInputParser();

        echo "Eloquent Query DSL main entry regression 通过\n";
    }

    private function testDefaultEntryWithCustomInputMap(): void
    {
        $definition = DslQueryDefinition::make('article')
            ->strict(true)
            ->allowSearch(['title'])
            ->allowFilter(['status' => 'trim'])
            ->allowSort(['sort_order']);

        $result = QueryDsl::for(QueryDslEntryRegressionArticle::query(), $definition)
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

        Assert::contains('title" like ?', $this->normalizeSql($result->builder()), 'QueryDsl 主入口应应用 search');
        Assert::contains('status" = ?', $this->normalizeSql($result->builder()), 'QueryDsl 主入口应应用 filter');
        Assert::contains('order by "sort_order" desc', $this->normalizeSql($result->builder()), 'QueryDsl 主入口应应用 sort');
        Assert::same(['%Hello%', 'published'], $result->builder()->getBindings(), 'QueryDsl 主入口应使用 parser 与 normalizer 后的绑定值');
        Assert::resultIds($result->builder(), [1], 'QueryDsl 主入口生效结果集应匹配输入条件');
        Assert::same('published', $result->filterValues()->singleValue('status'), 'QueryDslResult 应暴露 filter values');
        Assert::same(2, $result->pagination()->page(), 'QueryDslResult 应暴露 page');
        Assert::same(50, $result->pagination()->limit(), 'QueryDslResult 应暴露策略限制后的 limit');
        Assert::true($result->context()->queryInput()->has('filter'), 'QueryDslResult 应暴露 context');
    }

    private function testCustomInputParser(): void
    {
        $definition = DslQueryDefinition::make('article')
            ->strict(true)
            ->allowFilter(['status'])
            ->allowSort(['id']);

        $parser = new class () implements DslInputParser {
            public function queryInput(array $params, DslInputMap $inputMap): DslQueryInput
            {
                return DslQueryInput::fromRaw([
                    'filter' => ['status' => 'draft'],
                    'sort' => [['field' => 'id', 'order' => 'desc']],
                ]);
            }

            public function pageInput(array $params, DslInputMap $inputMap): DslPageInput
            {
                return DslPageInput::fromRaw(['page' => '3', 'limit' => '5']);
            }
        };

        $result = QueryDsl::for(QueryDslEntryRegressionArticle::query(), $definition)
            ->from(['ignored' => true])
            ->inputParser($parser)
            ->apply();

        Assert::contains('status" = ?', $this->normalizeSql($result->builder()), '自定义 parser 应接入 filter');
        Assert::contains('order by "id" desc', $this->normalizeSql($result->builder()), '自定义 parser 应接入 sort');
        Assert::resultIds($result->builder(), [2], '自定义 parser 生效结果集应匹配输入条件');
        Assert::same(3, $result->pagination()->page(), '自定义 parser page 应生效');
        Assert::same(5, $result->pagination()->limit(), '自定义 parser limit 应生效');
    }

    private function normalizeSql(Builder $query): string
    {
        $sql = strtolower($query->toSql());
        return (string)preg_replace('/\s+/', ' ', $sql);
    }
}

final class QueryDslEntryRegressionArticle extends Model
{
    protected $table = 'dsl_core_regression_articles';
    public $timestamps = false;
    protected $guarded = [];
}

(new QueryDslEntryRegression())->run();
