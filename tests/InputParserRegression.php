<?php

declare(strict_types=1);

use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslException;
use HongXunPan\EloquentQueryDsl\Input\DefaultDslInputParser;
use HongXunPan\EloquentQueryDsl\Input\DslInputMap;
use HongXunPan\EloquentQueryDsl\Tests\Support\Assert;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/Support/Assert.php';

$parser = new DefaultDslInputParser();

$defaultMap = DslInputMap::make();
$defaultQueryInput = $parser->queryInput([
    'query' => [
        'search' => ['title' => '校友会'],
        'filter' => ['status' => 'published'],
        'between' => ['created_at' => ['2026-01-01', '2026-12-31']],
        'sort' => [
            ['field' => 'published_at', 'order' => 'desc'],
            ['field' => 'id', 'order' => 'asc'],
        ],
    ],
    'page' => ['page' => '2', 'limit' => '15'],
], $defaultMap);
$defaultPageInput = $parser->pageInput([
    'page' => ['page' => '2', 'limit' => '15'],
], $defaultMap);

Assert::same(['title' => '校友会'], $defaultQueryInput->get('search')?->value(), '默认 query.search 应保持字段 map');
Assert::same(['status' => 'published'], $defaultQueryInput->get('filter')?->value(), '默认 query.filter 应保持字段 map');
Assert::same(
    [
        ['field' => 'published_at', 'order' => 'desc'],
        ['field' => 'id', 'order' => 'asc'],
    ],
    $defaultQueryInput->get('sort')?->value(),
    '默认 query.sort 应保持排序列表顺序',
);
Assert::same('2', $defaultPageInput->pageValue(), '默认 page.page 应成功读取');
Assert::same('15', $defaultPageInput->limitValue(), '默认 page.limit 应成功读取');

$jsonQueryInput = $parser->queryInput([
    'query' => '{"filter":{"status":"draft"},"sort":[{"field":"updated_at","order":"desc"}]}',
], $defaultMap);
$jsonPageInput = $parser->pageInput([
    'page' => '{"page":"3","limit":"50"}',
    'export_limit' => '200',
], $defaultMap);

Assert::same(['status' => 'draft'], $jsonQueryInput->get('filter')?->value(), 'JSON query.filter 应成功解析');
Assert::same(
    [['field' => 'updated_at', 'order' => 'desc']],
    $jsonQueryInput->get('sort')?->value(),
    'JSON query.sort 排序列表应成功解析',
);
Assert::same('3', $jsonPageInput->pageValue(), 'JSON page.page 应成功解析');
Assert::same('50', $jsonPageInput->limitValue(), 'JSON page.limit 应成功解析');
Assert::same('200', $jsonPageInput->rawExportLimit(), '顶层 export_limit 应保持为导出上限原始值');

$emptyJsonQueryInput = $parser->queryInput([
    'query' => '{}',
], $defaultMap);
$emptyJsonPageInput = $parser->pageInput([
    'page' => '{}',
], $defaultMap);

Assert::same([], $emptyJsonQueryInput->names(), 'JSON 空对象 query 应解析为空查询');
Assert::same(null, $emptyJsonPageInput->pageValue(), 'JSON 空对象 page 应解析为空分页参数');

$arrayMap = DslInputMap::make()
    ->filter('where')
    ->sort('order_by')
    ->page('page')
    ->limit('per_page')
    ->arrayInput();
$arrayQueryInput = $parser->queryInput([
    'where' => '{"status":"published"}',
    'order_by' => [
        ['field' => 'published_at', 'order' => 'desc'],
    ],
    'page' => '4',
    'per_page' => '30',
], $arrayMap);
$arrayPageInput = $parser->pageInput([
    'page' => '4',
    'per_page' => '30',
], $arrayMap);

Assert::same(['status' => 'published'], $arrayQueryInput->get('filter')?->value(), '自定义 where 应映射为 filter');
Assert::same(
    [['field' => 'published_at', 'order' => 'desc']],
    $arrayQueryInput->get('sort')?->value(),
    '自定义 order_by 排序列表应映射为 sort 列表',
);
Assert::same('4', $arrayPageInput->pageValue(), '自定义顶层 page 标量应映射为 page.page');
Assert::same('30', $arrayPageInput->limitValue(), '自定义 per_page 应映射为 page.limit');

$flatMap = DslInputMap::make()
    ->flatInput()
    ->search('keyword')
    ->filterPrefix('where_')
    ->sort('sort')
    ->page('page')
    ->limit('per_page');
$flatQueryInput = $parser->queryInput([
    'keyword' => '校友会',
    'where_status' => 'published',
    'where_category' => 'news',
    'sort' => [
        ['field' => 'published_at', 'order' => 'desc'],
    ],
], $flatMap);

Assert::same(['keyword' => '校友会'], $flatQueryInput->get('search')?->value(), 'flat keyword 应映射为 search 字段 map');
Assert::same(
    ['status' => 'published', 'category' => 'news'],
    $flatQueryInput->get('filter')?->value(),
    'flat where_ 前缀应映射为 filter 字段 map',
);
Assert::same(
    [['field' => 'published_at', 'order' => 'desc']],
    $flatQueryInput->get('sort')?->value(),
    'flat sort 排序列表应映射为 sort 列表',
);

$wrappedCustomMap = DslInputMap::make()
    ->query('q')
    ->filter('where')
    ->sort('order_by');
$wrappedCustomQueryInput = $parser->queryInput([
    'q' => [
        'where' => ['status' => 'draft'],
        'order_by' => [
            ['field' => 'updated_at', 'order' => 'asc'],
        ],
    ],
], $wrappedCustomMap);

Assert::same(['status' => 'draft'], $wrappedCustomQueryInput->get('filter')?->value(), '包裹协议内 where 应映射为 filter');
Assert::same(
    [['field' => 'updated_at', 'order' => 'asc']],
    $wrappedCustomQueryInput->get('sort')?->value(),
    '包裹协议内 order_by 应映射为 sort',
);

Assert::throws(
    fn () => $parser->queryInput(['query' => '[1,2]'], DslInputMap::make()),
    DslQueryDslException::class,
    'query格式错误',
);
Assert::throws(
    fn () => $parser->queryInput(['where' => '{"status":'], $arrayMap),
    DslQueryDslException::class,
    'query.filter格式错误',
);
Assert::throws(
    fn () => $parser->queryInput(['where' => '[1,2]'], $arrayMap),
    DslQueryDslException::class,
    'query.filter格式错误',
);
Assert::throws(
    fn () => $parser->queryInput(['q' => ['filter' => [], 'where' => []]], $wrappedCustomMap),
    DslQueryDslException::class,
    'query格式错误',
);
Assert::throws(
    fn () => $parser->queryInput(['query' => '{"sort":"-updated_at"}'], DslInputMap::make()),
    DslQueryDslException::class,
    'query.sort格式错误',
);
Assert::throws(
    fn () => $parser->queryInput(['query' => ['sort' => ['updated_at' => 'desc']]], DslInputMap::make()),
    DslQueryDslException::class,
    'query.sort格式错误',
);
Assert::throws(
    fn () => DslInputMap::make()->flatInput()->filterPrefix(''),
    DslQueryDslException::class,
    'filter prefix不能为空',
);

echo "Eloquent Query DSL input parser regression 通过\n";
