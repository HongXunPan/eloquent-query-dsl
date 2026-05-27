<?php

declare(strict_types=1);

use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefaultSort;
use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefinition;
use HongXunPan\EloquentQueryDsl\Derived\DslDerivedFilterRuleMap;
use HongXunPan\EloquentQueryDsl\Derived\DslDoesntHaveRelationDerivedFilterRule;
use HongXunPan\EloquentQueryDsl\Derived\DslFilterValueDerivedDefaultSortStrategy;
use HongXunPan\EloquentQueryDsl\Derived\DslHasRelationDerivedFilterRule;
use HongXunPan\EloquentQueryDsl\Derived\DslNotNullColumnDerivedFilterRule;
use HongXunPan\EloquentQueryDsl\Derived\DslNullColumnDerivedFilterRule;
use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslDefinitionException;
use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslException;
use HongXunPan\EloquentQueryDsl\Input\DslQueryInput;
use HongXunPan\EloquentQueryDsl\Kernel\QueryDslV2Kernel;
use HongXunPan\EloquentQueryDsl\Page\DslPageInput;
use HongXunPan\EloquentQueryDsl\Page\DslPaginationRequest;
use HongXunPan\EloquentQueryDsl\Section\DslFilterSectionApplier;
use HongXunPan\EloquentQueryDsl\Section\DslSortSectionApplier;
use HongXunPan\EloquentQueryDsl\Tests\Support\Assert;
use HongXunPan\EloquentQueryDsl\Tests\Support\FakeDslFilterNormalizer;
use HongXunPan\EloquentQueryDsl\Tests\Support\TestDatabase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/Support/Assert.php';
require __DIR__ . '/Support/FakeDslFilterNormalizer.php';
require __DIR__ . '/Support/TestDatabase.php';

final class QueryDslCoreRegression
{
    private QueryDslV2Kernel $kernel;
    private DslFilterSectionApplier $filterSectionApplier;

    public function __construct()
    {
        $normalizer = new FakeDslFilterNormalizer();
        $this->filterSectionApplier = new DslFilterSectionApplier(filterNormalizer: $normalizer);
        $this->kernel = new QueryDslV2Kernel([
            new \HongXunPan\EloquentQueryDsl\Section\DslSearchSectionApplier(),
            $this->filterSectionApplier,
            new \HongXunPan\EloquentQueryDsl\Section\DslBetweenSectionApplier(),
            new DslSortSectionApplier(filterSectionApplier: $this->filterSectionApplier),
        ]);
    }

    public function run(): void
    {
        TestDatabase::boot();
        $this->testQueryAndPageInputBoundaries();
        $this->testSearchSemantics();
        $this->testFilterSemantics();
        $this->testStrictBoundaries();
        $this->testRelationSemanticsAndBoundaries();
        $this->testSortSemantics();

        echo "Eloquent Query DSL core regression 通过\n";
    }

    private function testQueryAndPageInputBoundaries(): void
    {
        $queryInput = DslQueryInput::fromRaw('{"search":{"title":"Hello"}}');
        Assert::true($queryInput->has('search'), 'query JSON object 应成功解析');

        Assert::throws(
            fn() => DslQueryInput::fromRaw('[1,2]'),
            DslQueryDslException::class,
            'query格式错误'
        );

        $pageInput = DslPageInput::fromRaw('{"page":"2","limit":"30"}', '200');
        $paginationRequest = DslPaginationRequest::fromPageInput($pageInput);
        Assert::same(1, $paginationRequest->page(), 'export_limit 生效时 page 应回到 1');
        Assert::same(200, $paginationRequest->limit(), 'export_limit 生效时 limit 应切换为 export_limit');
        Assert::same(200, $paginationRequest->exportLimit(), 'export_limit 应成功解析');

        $fallbackRequest = DslPaginationRequest::fromPageInput(
            DslPageInput::fromRaw(['page' => '0', 'limit' => 'abc'], 'oops')
        );
        Assert::same(1, $fallbackRequest->page(), '非法 page 应回退默认第一页');
        Assert::same(20, $fallbackRequest->limit(), '非法 limit / export_limit 应回退默认 limit');
        Assert::same(null, $fallbackRequest->exportLimit(), '非法 export_limit 不应写入分页事实');
    }

    private function testSearchSemantics(): void
    {
        $definition = DslQueryDefinition::make('article')
            ->strict(true)
            ->allowSearch(['title', 'code', 'display_code'])
            ->searchRightLike(['code', 'display_code'])
            ->allowDerivedSearch('display_code', function (Builder $query, string $value, string $mode): void {
                $pattern = $mode === 'right_like' ? $value . '%' : '%' . $value . '%';
                $query->whereRaw("code || '-' || id LIKE ?", [$pattern]);
            });

        $fullLikeQuery = $this->kernel->apply($this->newArticleQuery(), $definition, DslQueryInput::fromRaw([
            'search' => ['title' => 'Hello'],
        ]));
        Assert::contains('title" like ?', $this->normalizeSql($fullLikeQuery), 'full_like 应写入 like 条件');
        Assert::same(['%Hello%'], $fullLikeQuery->getBindings(), 'full_like 绑定值应带两侧百分号');
        Assert::resultIds($fullLikeQuery, [1, 2], 'full_like 生效结果集应匹配输入条件');

        $rightLikeQuery = $this->kernel->apply($this->newArticleQuery(), $definition, DslQueryInput::fromRaw([
            'search' => ['code' => 'AB'],
        ]));
        Assert::contains('code" like ?', $this->normalizeSql($rightLikeQuery), 'right_like 应写入 like 条件');
        Assert::same(['AB%'], $rightLikeQuery->getBindings(), 'right_like 绑定值应只带右侧百分号');
        Assert::resultIds($rightLikeQuery, [1, 2], 'right_like 生效结果集应匹配输入条件');

        $derivedSearchQuery = $this->kernel->apply($this->newArticleQuery(), $definition, DslQueryInput::fromRaw([
            'search' => ['display_code' => 'AC'],
        ]));
        Assert::contains("code || '-' || id like ?", $this->normalizeSql($derivedSearchQuery), 'derived search 应命中自定义 handler');
        Assert::same(['AC%'], $derivedSearchQuery->getBindings(), 'derived search 应按 right_like 绑定值');
        Assert::resultIds($derivedSearchQuery, [3], 'derived search 生效结果集应匹配输入条件');

        Assert::throws(
            fn() => $this->kernel->apply($this->newArticleQuery(), $definition, DslQueryInput::fromRaw(['search' => ['unknown' => 'x']])),
            DslQueryDslException::class,
            '未开放字段'
        );
    }

    private function testFilterSemantics(): void
    {
        $whereDefinition = DslQueryDefinition::make('article')->strict(true)->allowFilter(['status']);
        $whereQuery = $this->kernel->apply($this->newArticleQuery(), $whereDefinition, DslQueryInput::fromRaw([
            'filter' => ['status' => 'draft'],
        ]));
        Assert::contains('status" = ?', $this->normalizeSql($whereQuery), '单值 filter 应生成 where');
        Assert::same(['draft'], $whereQuery->getBindings(), '单值 filter 绑定值应保持原样');
        Assert::resultIds($whereQuery, [2], '单值 filter 生效结果集应匹配输入条件');

        $whereInDefinition = DslQueryDefinition::make('article')->strict(true)->allowFilter(['id']);
        $whereInInput = DslQueryInput::fromRaw(['filter' => ['id' => [1, 2]]]);
        $whereInQuery = $this->kernel->apply($this->newArticleQuery(), $whereInDefinition, $whereInInput);
        Assert::contains('id" in (?, ?)', $this->normalizeSql($whereInQuery), '多值 filter 应生成 whereIn');
        Assert::same([1, 2], $whereInQuery->getBindings(), 'whereIn 绑定值应保持数组值');
        Assert::resultIds($whereInQuery, [1, 2], 'whereIn 生效结果集应匹配输入条件');

        $defaultRuleDefinition = DslQueryDefinition::make('article')->strict(true)->allowFilter(['status' => 'default:draft']);
        $defaultRuleValues = $this->filterSectionApplier->filterValues($defaultRuleDefinition, DslQueryInput::empty());
        Assert::same('draft', $defaultRuleValues->singleValue('status'), 'fake normalizer 应支持 default 规则');
        $defaultRuleQuery = $this->kernel->apply($this->newArticleQuery(), $defaultRuleDefinition, DslQueryInput::empty());
        Assert::same(['draft'], $defaultRuleQuery->getBindings(), 'default filter 绑定值应来自默认规则');
        Assert::resultIds($defaultRuleQuery, [2], 'default filter 生效结果集应匹配默认输入条件');

        $trimRuleDefinition = DslQueryDefinition::make('article')->strict(true)->allowFilter(['status' => 'trim']);
        $trimRuleInput = DslQueryInput::fromRaw(['filter' => ['status' => '  draft  ']]);
        $trimRuleValues = $this->filterSectionApplier->filterValues($trimRuleDefinition, $trimRuleInput);
        Assert::same('draft', $trimRuleValues->singleValue('status'), 'fake normalizer 应支持 trim 规则');
        $trimRuleQuery = $this->kernel->apply($this->newArticleQuery(), $trimRuleDefinition, $trimRuleInput);
        Assert::same(['draft'], $trimRuleQuery->getBindings(), 'trim filter 绑定值应使用归一化结果');
        Assert::resultIds($trimRuleQuery, [2], 'trim filter 生效结果集应匹配归一化输入条件');

        $requiredDefinition = DslQueryDefinition::make('article')->strict(true)->allowFilter(['article_id' => 'required']);
        Assert::throws(
            fn() => $this->filterSectionApplier->filterValues($requiredDefinition, DslQueryInput::empty()),
            DslQueryDslException::class,
            '请输入article_id'
        );

        $normalizerMissingApplier = new DslFilterSectionApplier();
        Assert::throws(
            fn() => $normalizerMissingApplier->filterValues($trimRuleDefinition, DslQueryInput::fromRaw(['filter' => ['status' => 'draft']])),
            DslQueryDslDefinitionException::class,
            'filter normalizer 未配置'
        );

        $nullMapDefinition = DslQueryDefinition::make('article')
            ->strict(true)
            ->allowFilter(['published_state'])
            ->allowDerivedFilter('published_state', DslDerivedFilterRuleMap::make()
                ->when('empty', DslNullColumnDerivedFilterRule::forField('published_at'))
                ->when('ready', DslNotNullColumnDerivedFilterRule::forField('published_at')));
        $nullQuery = $this->kernel->apply($this->newArticleQuery(), $nullMapDefinition, DslQueryInput::fromRaw([
            'filter' => ['published_state' => 'empty'],
        ]));
        $notNullQuery = $this->kernel->apply($this->newArticleQuery(), $nullMapDefinition, DslQueryInput::fromRaw([
            'filter' => ['published_state' => 'ready'],
        ]));
        Assert::contains('published_at" is null', $this->normalizeSql($nullQuery), 'derived rule map 应支持 null');
        Assert::contains('published_at" is not null', $this->normalizeSql($notNullQuery), 'derived rule map 应支持 not_null');
        Assert::resultIds($nullQuery, [2], 'derived null filter 生效结果集应匹配输入条件');
        Assert::resultIds($notNullQuery, [1, 3], 'derived not_null filter 生效结果集应匹配输入条件');

        $relationRuleDefinition = DslQueryDefinition::make('article')
            ->strict(true)
            ->allowFilter(['comment_state'])
            ->allowDerivedFilter('comment_state', DslDerivedFilterRuleMap::make()
                ->when('has', DslHasRelationDerivedFilterRule::forRelation('comments'))
                ->when('none', DslDoesntHaveRelationDerivedFilterRule::forRelation('comments')));
        $hasRelationQuery = $this->kernel->apply($this->newArticleQuery(), $relationRuleDefinition, DslQueryInput::fromRaw([
            'filter' => ['comment_state' => 'has'],
        ]));
        $doesntHaveRelationQuery = $this->kernel->apply($this->newArticleQuery(), $relationRuleDefinition, DslQueryInput::fromRaw([
            'filter' => ['comment_state' => 'none'],
        ]));
        Assert::contains('exists', $this->normalizeSql($hasRelationQuery), 'derived rule map 应支持 has');
        Assert::contains('not exists', $this->normalizeSql($doesntHaveRelationQuery), 'derived rule map 应支持 doesnt_have');
        Assert::resultIds($hasRelationQuery, [1, 2], 'derived has relation filter 生效结果集应匹配输入条件');
        Assert::resultIds($doesntHaveRelationQuery, [3], 'derived doesnt_have relation filter 生效结果集应匹配输入条件');
    }

    private function testStrictBoundaries(): void
    {
        $strictDefinition = DslQueryDefinition::make('article')->strict(true)->allowSearch(['title']);
        Assert::throws(
            fn() => $this->kernel->apply($this->newArticleQuery(), $strictDefinition, DslQueryInput::fromRaw(['filter' => ['status' => 'draft']])),
            DslQueryDslException::class,
            '当前查询未开放 filter 能力'
        );

        $looseFieldQuery = $this->kernel->apply(
            $this->newArticleQuery(),
            DslQueryDefinition::make('article')->strict(false)->allowSearch(['title']),
            DslQueryInput::fromRaw(['search' => ['unknown' => 'x']])
        );
        Assert::notContains('where', $this->normalizeSql($looseFieldQuery), 'strict(false) 下未开放字段应被忽略');
        Assert::resultIds($looseFieldQuery, [1, 2, 3], 'strict(false) 下未开放字段不应影响结果集');
    }

    private function testRelationSemanticsAndBoundaries(): void
    {
        $relationSearchDefinition = DslQueryDefinition::make('article')
            ->relation('comments', 'comments')
            ->strict(true)
            ->allowSearch(['comments.body']);
        $relationSearchQuery = $this->kernel->apply($this->newArticleQuery(), $relationSearchDefinition, DslQueryInput::fromRaw([
            'search' => ['comments' => ['body' => 'first']],
        ]));
        Assert::contains('exists', $this->normalizeSql($relationSearchQuery), 'relation search 应转换为 whereHas');
        Assert::contains('"body" like ?', $this->normalizeSql($relationSearchQuery), 'relation search 应命中关联字段 like');
        Assert::same(['%first%'], $relationSearchQuery->getBindings(), 'relation search 绑定值应正确');
        Assert::resultIds($relationSearchQuery, [1], 'relation search 生效结果集应匹配输入条件');

        $relationFilterDefinition = DslQueryDefinition::make('article')
            ->relation('comments', 'comments')
            ->strict(true)
            ->allowFilter(['comments.body']);
        $relationFilterQuery = $this->kernel->apply($this->newArticleQuery(), $relationFilterDefinition, DslQueryInput::fromRaw([
            'filter' => ['comments' => ['body' => 'first comment']],
        ]));
        Assert::contains('exists', $this->normalizeSql($relationFilterQuery), 'relation filter 应转换为 whereHas');
        Assert::contains('"body" = ?', $this->normalizeSql($relationFilterQuery), 'relation filter 应命中关联字段 where');
        Assert::same(['first comment'], $relationFilterQuery->getBindings(), 'relation filter 绑定值应正确');
        Assert::resultIds($relationFilterQuery, [1], 'relation filter 生效结果集应匹配输入条件');

        Assert::throws(
            fn() => $this->kernel->apply(
                $this->newArticleQuery(),
                DslQueryDefinition::make('article')->strict(true)->allowSearch(['comments.body']),
                DslQueryInput::fromRaw(['search' => ['comments.body' => 'first']])
            ),
            DslQueryDslDefinitionException::class,
            'relation 未配置映射'
        );
    }

    private function testSortSemantics(): void
    {
        $explicitSortDefinition = DslQueryDefinition::make('article')->strict(true)->allowSort(['sort_order', 'id']);
        $explicitSortQuery = $this->kernel->apply($this->newArticleQuery(), $explicitSortDefinition, DslQueryInput::fromRaw([
            'sort' => [
                ['field' => 'sort_order', 'order' => 'desc'],
                ['field' => 'id', 'order' => 'asc'],
            ],
        ]));
        Assert::contains('order by "sort_order" desc, "id" asc', $this->normalizeSql($explicitSortQuery), '显式排序应保持声明顺序');
        Assert::resultIds($explicitSortQuery, [3, 1, 2], '显式排序生效结果集应匹配排序条件');

        $ascOnlyDefinition = DslQueryDefinition::make('article')->strict(true)->allowSort(['sort_order'])->sortAscOnly(['sort_order']);
        Assert::throws(
            fn() => $this->kernel->apply($this->newArticleQuery(), $ascOnlyDefinition, DslQueryInput::fromRaw([
                'sort' => [['field' => 'sort_order', 'order' => 'desc']],
            ])),
            DslQueryDslException::class,
            '该字段不支持当前排序方向'
        );

        $derivedDefaultSortDefinition = DslQueryDefinition::make('article')
            ->strict(true)
            ->allowFilter(['tab'])
            ->allowSort(['id'])
            ->allowDerivedDefaultSortByFilter('tab', DslFilterValueDerivedDefaultSortStrategy::make()
                ->when('pending', DslQueryDefaultSort::asc('id', 'article')))
            ->defaultSort('sort_order', 'desc');

        $derivedDefaultSortHitQuery = $this->kernel->apply($this->newArticleQuery(), $derivedDefaultSortDefinition, DslQueryInput::fromRaw([
            'filter' => ['tab' => 'pending'],
        ]));
        Assert::contains('order by "id" asc', $this->normalizeSql($derivedDefaultSortHitQuery), '命中 derived default sort 时应优先使用派生排序');
        Assert::notContains('sort_order', $this->normalizeSql($derivedDefaultSortHitQuery), '命中 derived default sort 时不应回退 default sort');

        $batchDefaultSortDefinition = DslQueryDefinition::make('article')->strict(true)->defaultSortMany(
            DslQueryDefaultSort::desc('sort_order', 'article'),
            DslQueryDefaultSort::asc('id', 'article')
        );
        $batchDefaultSortQuery = $this->kernel->apply($this->newArticleQuery(), $batchDefaultSortDefinition, DslQueryInput::empty());
        Assert::contains('order by "sort_order" desc, "id" asc', $this->normalizeSql($batchDefaultSortQuery), 'defaultSortMany 应批量声明默认排序');
        Assert::resultIds($batchDefaultSortQuery, [3, 1, 2], 'defaultSortMany 生效结果集应匹配默认排序条件');
    }

    private function newArticleQuery(): Builder
    {
        return QueryDslCoreRegressionArticle::query();
    }

    private function normalizeSql(Builder $query): string
    {
        $sql = strtolower($query->toSql());
        return (string)preg_replace('/\s+/', ' ', $sql);
    }
}

final class QueryDslCoreRegressionArticle extends Model
{
    protected $table = 'dsl_core_regression_articles';
    public $timestamps = false;
    protected $guarded = [];

    public function comments()
    {
        return $this->hasMany(QueryDslCoreRegressionComment::class, 'article_id');
    }
}

final class QueryDslCoreRegressionComment extends Model
{
    protected $table = 'dsl_core_regression_comments';
    public $timestamps = false;
    protected $guarded = [];
}

(new QueryDslCoreRegression())->run();
