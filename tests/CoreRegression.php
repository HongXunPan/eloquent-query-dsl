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
use HongXunPan\EloquentQueryDsl\Filter\Contract\DslFilterNormalizer;
use HongXunPan\EloquentQueryDsl\Filter\Value\DslNormalizedFilterItem;
use HongXunPan\EloquentQueryDsl\Filter\Value\DslNormalizedFilterSet;
use HongXunPan\EloquentQueryDsl\Input\DslQueryInput;
use HongXunPan\EloquentQueryDsl\Kernel\QueryDslV2Kernel;
use HongXunPan\EloquentQueryDsl\Page\DslPageInput;
use HongXunPan\EloquentQueryDsl\Page\DslPaginationRequest;
use HongXunPan\EloquentQueryDsl\Section\DslFilterSectionApplier;
use HongXunPan\EloquentQueryDsl\Section\DslSortSectionApplier;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

require __DIR__ . '/../vendor/autoload.php';

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
        $this->bootDatabase();
        $this->testQueryAndPageInputBoundaries();
        $this->testSearchSemantics();
        $this->testFilterSemantics();
        $this->testStrictBoundaries();
        $this->testRelationSemanticsAndBoundaries();
        $this->testSortSemantics();

        echo "Eloquent Query DSL core regression 通过\n";
    }

    private function bootDatabase(): void
    {
        $capsule = new Capsule();
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $schema = $capsule->schema();
        $schema->create('dsl_core_regression_articles', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->string('code')->nullable();
            $table->string('status')->nullable();
            $table->integer('sort_order')->nullable();
            $table->timestamp('published_at')->nullable();
        });

        $schema->create('dsl_core_regression_comments', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('article_id');
            $table->string('body')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    private function testQueryAndPageInputBoundaries(): void
    {
        $queryInput = DslQueryInput::fromRaw('{"search":{"title":"Hello"}}');
        $this->assertTrue($queryInput->has('search'), 'query JSON object 应成功解析');

        $this->assertThrows(
            fn() => DslQueryInput::fromRaw('[1,2]'),
            DslQueryDslException::class,
            'query格式错误'
        );

        $pageInput = DslPageInput::fromRaw('{"page":"2","limit":"30"}', '200');
        $paginationRequest = DslPaginationRequest::fromPageInput($pageInput);
        $this->assertSame(1, $paginationRequest->page(), 'export_limit 生效时 page 应回到 1');
        $this->assertSame(200, $paginationRequest->limit(), 'export_limit 生效时 limit 应切换为 export_limit');
        $this->assertSame(200, $paginationRequest->exportLimit(), 'export_limit 应成功解析');

        $fallbackRequest = DslPaginationRequest::fromPageInput(
            DslPageInput::fromRaw(['page' => '0', 'limit' => 'abc'], 'oops')
        );
        $this->assertSame(1, $fallbackRequest->page(), '非法 page 应回退默认第一页');
        $this->assertSame(20, $fallbackRequest->limit(), '非法 limit / export_limit 应回退默认 limit');
        $this->assertSame(null, $fallbackRequest->exportLimit(), '非法 export_limit 不应写入分页事实');
    }

    private function testSearchSemantics(): void
    {
        $definition = DslQueryDefinition::make('article')
            ->strict(true)
            ->allowSearch(['title', 'code', 'display_code'])
            ->searchRightLike(['code', 'display_code'])
            ->allowDerivedSearch('display_code', function (Builder $query, string $value, string $mode): void {
                $pattern = $mode === 'right_like' ? $value . '%' : '%' . $value . '%';
                $query->whereRaw("CONCAT(code, '-', id) LIKE ?", [$pattern]);
            });

        $fullLikeQuery = $this->kernel->apply($this->newArticleQuery(), $definition, DslQueryInput::fromRaw([
            'search' => ['title' => 'Hello'],
        ]));
        $this->assertContains('title" like ?', $this->normalizeSql($fullLikeQuery), 'full_like 应写入 like 条件');
        $this->assertSame(['%Hello%'], $fullLikeQuery->getBindings(), 'full_like 绑定值应带两侧百分号');

        $rightLikeQuery = $this->kernel->apply($this->newArticleQuery(), $definition, DslQueryInput::fromRaw([
            'search' => ['code' => 'AB'],
        ]));
        $this->assertContains('code" like ?', $this->normalizeSql($rightLikeQuery), 'right_like 应写入 like 条件');
        $this->assertSame(['AB%'], $rightLikeQuery->getBindings(), 'right_like 绑定值应只带右侧百分号');

        $derivedSearchQuery = $this->kernel->apply($this->newArticleQuery(), $definition, DslQueryInput::fromRaw([
            'search' => ['display_code' => 'AC'],
        ]));
        $this->assertContains("concat(code, '-', id) like ?", $this->normalizeSql($derivedSearchQuery), 'derived search 应命中自定义 handler');
        $this->assertSame(['AC%'], $derivedSearchQuery->getBindings(), 'derived search 应按 right_like 绑定值');

        $this->assertThrows(
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
        $this->assertContains('status" = ?', $this->normalizeSql($whereQuery), '单值 filter 应生成 where');
        $this->assertSame(['draft'], $whereQuery->getBindings(), '单值 filter 绑定值应保持原样');

        $whereInDefinition = DslQueryDefinition::make('article')->strict(true)->allowFilter(['id']);
        $whereInInput = DslQueryInput::fromRaw(['filter' => ['id' => [1, 2]]]);
        $whereInQuery = $this->kernel->apply($this->newArticleQuery(), $whereInDefinition, $whereInInput);
        $this->assertContains('id" in (?, ?)', $this->normalizeSql($whereInQuery), '多值 filter 应生成 whereIn');
        $this->assertSame([1, 2], $whereInQuery->getBindings(), 'whereIn 绑定值应保持数组值');

        $defaultRuleDefinition = DslQueryDefinition::make('article')->strict(true)->allowFilter(['status' => 'default:draft']);
        $defaultRuleValues = $this->filterSectionApplier->filterValues($defaultRuleDefinition, DslQueryInput::empty());
        $this->assertSame('draft', $defaultRuleValues->singleValue('status'), 'fake normalizer 应支持 default 规则');

        $trimRuleDefinition = DslQueryDefinition::make('article')->strict(true)->allowFilter(['status' => 'trim']);
        $trimRuleValues = $this->filterSectionApplier->filterValues($trimRuleDefinition, DslQueryInput::fromRaw([
            'filter' => ['status' => '  draft  '],
        ]));
        $this->assertSame('draft', $trimRuleValues->singleValue('status'), 'fake normalizer 应支持 trim 规则');

        $requiredDefinition = DslQueryDefinition::make('article')->strict(true)->allowFilter(['article_id' => 'required']);
        $this->assertThrows(
            fn() => $this->filterSectionApplier->filterValues($requiredDefinition, DslQueryInput::empty()),
            DslQueryDslException::class,
            '请输入article_id'
        );

        $normalizerMissingApplier = new DslFilterSectionApplier();
        $this->assertThrows(
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
        $this->assertContains('published_at" is null', $this->normalizeSql($nullQuery), 'derived rule map 应支持 null');
        $this->assertContains('published_at" is not null', $this->normalizeSql($notNullQuery), 'derived rule map 应支持 not_null');

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
        $this->assertContains('exists', $this->normalizeSql($hasRelationQuery), 'derived rule map 应支持 has');
        $this->assertContains('not exists', $this->normalizeSql($doesntHaveRelationQuery), 'derived rule map 应支持 doesnt_have');
    }

    private function testStrictBoundaries(): void
    {
        $strictDefinition = DslQueryDefinition::make('article')->strict(true)->allowSearch(['title']);
        $this->assertThrows(
            fn() => $this->kernel->apply($this->newArticleQuery(), $strictDefinition, DslQueryInput::fromRaw(['filter' => ['status' => 'draft']])),
            DslQueryDslException::class,
            '当前查询未开放 filter 能力'
        );

        $looseFieldQuery = $this->kernel->apply(
            $this->newArticleQuery(),
            DslQueryDefinition::make('article')->strict(false)->allowSearch(['title']),
            DslQueryInput::fromRaw(['search' => ['unknown' => 'x']])
        );
        $this->assertNotContains('where', $this->normalizeSql($looseFieldQuery), 'strict(false) 下未开放字段应被忽略');
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
        $this->assertContains('exists', $this->normalizeSql($relationSearchQuery), 'relation search 应转换为 whereHas');
        $this->assertContains('"body" like ?', $this->normalizeSql($relationSearchQuery), 'relation search 应命中关联字段 like');
        $this->assertSame(['%first%'], $relationSearchQuery->getBindings(), 'relation search 绑定值应正确');

        $relationFilterDefinition = DslQueryDefinition::make('article')
            ->relation('comments', 'comments')
            ->strict(true)
            ->allowFilter(['comments.body']);
        $relationFilterQuery = $this->kernel->apply($this->newArticleQuery(), $relationFilterDefinition, DslQueryInput::fromRaw([
            'filter' => ['comments' => ['body' => 'first']],
        ]));
        $this->assertContains('exists', $this->normalizeSql($relationFilterQuery), 'relation filter 应转换为 whereHas');
        $this->assertContains('"body" = ?', $this->normalizeSql($relationFilterQuery), 'relation filter 应命中关联字段 where');
        $this->assertSame(['first'], $relationFilterQuery->getBindings(), 'relation filter 绑定值应正确');

        $this->assertThrows(
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
        $this->assertContains('order by "sort_order" desc, "id" asc', $this->normalizeSql($explicitSortQuery), '显式排序应保持声明顺序');

        $ascOnlyDefinition = DslQueryDefinition::make('article')->strict(true)->allowSort(['sort_order'])->sortAscOnly(['sort_order']);
        $this->assertThrows(
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
        $this->assertContains('order by "id" asc', $this->normalizeSql($derivedDefaultSortHitQuery), '命中 derived default sort 时应优先使用派生排序');
        $this->assertNotContains('sort_order', $this->normalizeSql($derivedDefaultSortHitQuery), '命中 derived default sort 时不应回退 default sort');

        $batchDefaultSortDefinition = DslQueryDefinition::make('article')->strict(true)->defaultSortMany(
            DslQueryDefaultSort::desc('sort_order', 'article'),
            DslQueryDefaultSort::asc('id', 'article')
        );
        $batchDefaultSortQuery = $this->kernel->apply($this->newArticleQuery(), $batchDefaultSortDefinition, DslQueryInput::empty());
        $this->assertContains('order by "sort_order" desc, "id" asc', $this->normalizeSql($batchDefaultSortQuery), 'defaultSortMany 应批量声明默认排序');
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

    private function assertSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message . "\nexpected: " . var_export($expected, true) . "\nactual: " . var_export($actual, true));
        }
    }

    private function assertTrue(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    private function assertContains(string $needle, string $haystack, string $message): void
    {
        if (!str_contains($haystack, strtolower($needle))) {
            throw new RuntimeException($message . "\nneedle: " . $needle . "\nhaystack: " . $haystack);
        }
    }

    private function assertNotContains(string $needle, string $haystack, string $message): void
    {
        if (str_contains($haystack, strtolower($needle))) {
            throw new RuntimeException($message . "\nneedle: " . $needle . "\nhaystack: " . $haystack);
        }
    }

    private function assertThrows(callable $callback, string $expectedException, string $messageContains = ''): void
    {
        try {
            $callback();
        } catch (Throwable $throwable) {
            if (!$throwable instanceof $expectedException) {
                throw new RuntimeException('异常类型不符合预期：' . $throwable::class . '，期望：' . $expectedException, 0, $throwable);
            }

            if ($messageContains !== '' && !str_contains($throwable->getMessage(), $messageContains)) {
                throw new RuntimeException('异常消息不符合预期：' . $throwable->getMessage() . '，期望包含：' . $messageContains, 0, $throwable);
            }

            return;
        }

        throw new RuntimeException('预期应抛出异常：' . $expectedException);
    }
}

final class FakeDslFilterNormalizer implements DslFilterNormalizer
{
    public function normalize(array $payload, array $rules, array $options = array()): DslNormalizedFilterSet
    {
        $normalizedPayload = $payload;
        $items = [];
        $errors = [];

        foreach ($rules as $payloadField => $rule) {
            $rule = trim($rule);
            $valueInfo = $this->getArrayValueByPath($normalizedPayload, $payloadField);

            if (str_contains($rule, 'default:') && !$valueInfo['exists']) {
                $default = $this->ruleArgument($rule, 'default:');
                $this->setArrayValueByPath($normalizedPayload, $payloadField, $default);
                $valueInfo = ['exists' => true, 'value' => $default];
            }

            if (str_contains($rule, 'required') && !$valueInfo['exists']) {
                $errors[] = '请输入' . $payloadField;
                continue;
            }

            if (!$valueInfo['exists']) {
                continue;
            }

            $value = $valueInfo['value'];
            if (str_contains($rule, 'trim') && is_string($value)) {
                $value = trim($value);
                $this->setArrayValueByPath($normalizedPayload, $payloadField, $value);
            }

            $items[$payloadField] = new DslNormalizedFilterItem($payloadField, $this->normalizeQueryValues($value));
        }

        if ($errors !== []) {
            return DslNormalizedFilterSet::failure($errors);
        }

        return DslNormalizedFilterSet::success($normalizedPayload, $items);
    }

    private function ruleArgument(string $rule, string $prefix): string
    {
        foreach (explode('|', $rule) as $segment) {
            $segment = trim($segment);
            if (str_starts_with($segment, $prefix)) {
                return substr($segment, strlen($prefix));
            }
        }

        return '';
    }

    /**
     * @return array{exists: bool, value: mixed}
     */
    private function getArrayValueByPath(array $data, string $path): array
    {
        if ($path === '') {
            return ['exists' => true, 'value' => $data];
        }

        $current = $data;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return ['exists' => false, 'value' => null];
            }
            $current = $current[$segment];
        }

        return ['exists' => true, 'value' => $current];
    }

    private function setArrayValueByPath(array &$data, string $path, mixed $value): void
    {
        $current = &$data;
        foreach (explode('.', $path) as $index => $segment) {
            if ($index === count(explode('.', $path)) - 1) {
                $current[$segment] = $value;
                return;
            }

            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }
    }

    /**
     * @return array<int, mixed>
     */
    private function normalizeQueryValues(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, fn(mixed $item): bool => $this->hasMeaningfulValue($item)));
        }

        return $this->hasMeaningfulValue($value) ? [$value] : [];
    }

    private function hasMeaningfulValue(mixed $value): bool
    {
        if (is_array($value)) {
            return $value !== [];
        }

        return $value || $value === '0' || $value === 0;
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
