<?php

declare(strict_types=1);

use HongXunPan\EloquentQueryDsl\Package;
use HongXunPan\EloquentQueryDsl\Condition\DslFilterCondition;
use HongXunPan\EloquentQueryDsl\Condition\DslSortCondition;
use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefinition;
use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslDefinitionException;
use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslException;
use HongXunPan\EloquentQueryDsl\Field\DslFieldPath;
use HongXunPan\EloquentQueryDsl\Filter\DslFilterValue;
use HongXunPan\EloquentQueryDsl\Filter\DslFilterValues;
use HongXunPan\EloquentQueryDsl\Filter\Contract\DslFilterNormalizer;
use HongXunPan\EloquentQueryDsl\Filter\Value\DslNormalizedFilterItem;
use HongXunPan\EloquentQueryDsl\Filter\Value\DslNormalizedFilterSet;
use HongXunPan\EloquentQueryDsl\Input\Contract\DslInputParser;
use HongXunPan\EloquentQueryDsl\Input\DefaultDslInputParser;
use HongXunPan\EloquentQueryDsl\Input\DslInputMap;
use HongXunPan\EloquentQueryDsl\Input\DslQueryInput;
use HongXunPan\EloquentQueryDsl\Kernel\QueryDslV2Kernel;
use HongXunPan\EloquentQueryDsl\Page\DslPageInput;
use HongXunPan\EloquentQueryDsl\Page\DslPaginationRequest;
use HongXunPan\EloquentQueryDsl\QueryDsl;
use HongXunPan\EloquentQueryDsl\QueryDslResult;
use HongXunPan\EloquentQueryDsl\Section\DslFilterSectionApplier;

$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
} else {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'HongXunPan\\EloquentQueryDsl\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }

        $relativeClass = substr($class, strlen($prefix));
        $path = __DIR__ . '/../src/' . str_replace('\\', '/', $relativeClass) . '.php';
        if (is_file($path)) {
            require $path;
        }
    });
}

$fieldPath = DslFieldPath::fromDefinition('status', 'activity');
$relationFieldPath = DslFieldPath::fromInput('alumni_card.real_name', 'activity');
$filterCondition = DslFilterCondition::fromField($fieldPath, 'published');
$sortCondition = DslSortCondition::make($fieldPath, 'DESC');
$definition = DslQueryDefinition::make('activity')
    ->allowSearch(['title'])
    ->allowFilter(['status' => 'string'])
    ->allowSort(['updated_at'])
    ->defaultSort('updated_at', 'DESC')
    ->strict();
$statusFilterDefinition = $definition->getSection('filter')?->field('activity.status');
$filterValue = DslFilterValue::fromDefinition($statusFilterDefinition, ['published']);
$filterValues = new DslFilterValues();
$filterValues->put($filterValue);
$queryInput = DslQueryInput::fromRaw(['filter' => ['status' => 'published']]);
$pageInput = DslPageInput::fromRaw(['page' => '2', 'limit' => '15']);
$paginationRequest = DslPaginationRequest::fromPageInput($pageInput);
$filterSectionApplier = new DslFilterSectionApplier();
$filterConditions = $filterSectionApplier->conditions(
    DslQueryDefinition::make('activity')->allowFilter(['status']),
    DslQueryInput::fromRaw(['filter' => ['status' => 'published']])
);
$filterRuleWithoutNormalizerThrows = false;
try {
    $filterSectionApplier->conditions(
        DslQueryDefinition::make('activity')->allowFilter(['status' => 'string']),
        DslQueryInput::fromRaw(['filter' => ['status' => 'published']])
    );
} catch (DslQueryDslDefinitionException $exception) {
    $filterRuleWithoutNormalizerThrows = str_contains($exception->getMessage(), 'filter normalizer');
}
$filterItem = new DslNormalizedFilterItem('status', ['published']);
$filterSet = DslNormalizedFilterSet::success(
    ['status' => 'published'],
    ['status' => $filterItem]
);
$failureSet = DslNormalizedFilterSet::failure(['query.filter 参数错误']);
$inputMap = DslInputMap::make()
    ->filter('where')
    ->sort('order_by')
    ->arrayInput();
$inputParser = new DefaultDslInputParser();
$mappedInput = $inputParser->queryInput([
    'where' => ['status' => 'published'],
    'order_by' => ['updated_at' => 'desc'],
], $inputMap);

$assertions = [
    '包名常量正确' => Package::name() === 'hongxunpan/eloquent-query-dsl',
    '包标识类可加载' => class_exists(Package::class),
    '输入期异常类可加载' => class_exists(DslQueryDslException::class),
    '字段路径可解析主实体字段' => $fieldPath->canonical() === 'activity.status' && $fieldPath->isMainEntity(),
    '字段路径可解析 relation 字段' => $relationFieldPath->canonical() === 'alumni_card.real_name' && $relationFieldPath->isRelation(),
    'filter condition 保留 section 与值' => $filterCondition->sectionName() === 'filter' && $filterCondition->value() === 'published',
    'sort condition 归一化排序方向' => $sortCondition->order() === DslSortCondition::ORDER_DESC,
    'definition 可声明查询能力' => $definition->isStrict() && $definition->getSection('search')?->hasField('activity.title'),
    'definition 可声明默认排序' => $definition->defaultSorts()[0]->fieldPath()->canonical() === 'activity.updated_at',
    'filter value 集合可按短字段读取' => $filterValues->singleValue('status') === 'published',
    'query input 可解析 section' => $queryInput->has('filter') && $queryInput->get('filter')?->isArray(),
    'page input 可解析分页事实' => $paginationRequest->page() === 2 && $paginationRequest->limit() === 15,
    'filter section 无规则时可生成条件' => count($filterConditions) === 1 && $filterConditions[0]->value() === 'published',
    'filter section 有规则但无 normalizer 时阻断' => $filterRuleWithoutNormalizerThrows,
    'kernel 入口可加载阶段标识' => (new QueryDslV2Kernel([]))->stage() === QueryDslV2Kernel::STAGE,
    'filter normalizer 契约可加载' => interface_exists(DslFilterNormalizer::class),
    'filter item 保留 payload 字段' => $filterItem->payloadField() === 'status',
    'filter item 保留归一化值列表' => $filterItem->normalizedValues() === ['published'],
    'filter set 可按字段读取归一化结果' => $filterSet->item('status')?->normalizedValues() === ['published'],
    'filter set 空字段不返回 item' => $filterSet->item('') === null,
    'filter failure set 保留错误信息' => $failureSet->isFailed() && $failureSet->errors() === ['query.filter 参数错误'],
    'input parser 契约可加载' => interface_exists(DslInputParser::class),
    'input map 可声明自定义参数名' => $inputMap->filterKey() === 'where' && $inputMap->sortKey() === 'order_by',
    '默认 input parser 可映射自定义 filter' => $mappedInput->get('filter')?->value() === ['status' => 'published'],
    '默认 input parser 可映射自定义 sort' => $mappedInput->get('sort')?->value() === [['field' => 'updated_at', 'order' => 'desc']],
    'QueryDsl 主入口可加载' => class_exists(QueryDsl::class),
    'QueryDslResult 可加载' => class_exists(QueryDslResult::class),
];

foreach ($assertions as $message => $passed) {
    if (!$passed) {
        fwrite(STDERR, "[失败] {$message}\n");
        exit(1);
    }

    fwrite(STDOUT, "[通过] {$message}\n");
}

fwrite(STDOUT, "Eloquent Query DSL skeleton 测试通过。\n");
