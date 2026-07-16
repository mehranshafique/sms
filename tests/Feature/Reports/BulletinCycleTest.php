<?php

use App\Services\AcademicCycleService;
use App\Enums\AcademicType;

it('resolves primary trimester period keys', function () {
    $service = app(AcademicCycleService::class);
    $keys = $service->periodKeysForTerm(AcademicType::PRIMARY->value, 2);

    expect($keys['pA'])->toBe('p3')
        ->and($keys['pB'])->toBe('p4')
        ->and($keys['examCat'])->toBe('trimester_exam_2');
});

it('resolves secondary semester period keys', function () {
    $service = app(AcademicCycleService::class);
    $keys = $service->periodKeysForTerm(AcademicType::SECONDARY->value, 2);

    expect($keys['pA'])->toBe('p3')
        ->and($keys['pB'])->toBe('p4')
        ->and($keys['examCat'])->toBe('semester_exam_2');
});

it('allows period and trimester scopes for primary', function () {
    $service = app(AcademicCycleService::class);

    expect($service->allowedReportScopes(AcademicType::PRIMARY->value))
        ->toBe(['period', 'trimester']);
});

it('allows period and semester scopes for secondary', function () {
    $service = app(AcademicCycleService::class);

    expect($service->allowedReportScopes(AcademicType::SECONDARY->value))
        ->toBe(['period', 'semester']);
});

it('rejects semester scope validation for primary cycle', function () {
    $service = app(AcademicCycleService::class);

    $error = $service->validateReportRequest(AcademicType::PRIMARY->value, 'term', null, null, 1);

    expect($error)->not->toBeNull();
});

it('rejects trimester scope validation for secondary cycle', function () {
    $service = app(AcademicCycleService::class);

    $error = $service->validateReportRequest(AcademicType::SECONDARY->value, 'term', null, 1, null);

    expect($error)->not->toBeNull();
});

it('accepts valid period for secondary', function () {
    $service = app(AcademicCycleService::class);

    $error = $service->validateReportRequest(AcademicType::SECONDARY->value, 'period', 'p2', null, null);

    expect($error)->toBeNull();
});

it('returns session scope for university cycle', function () {
    $service = app(AcademicCycleService::class);

    expect($service->allowedReportScopes('university'))->toBe(['session']);
});

it('builds dynamic column labels for trimester bulletin', function () {
    $service = app(AcademicCycleService::class);
    $labels = $service->columnLabels(AcademicType::PRIMARY->value, 1, 'term');

    expect($labels)->toHaveKeys(['p1', 'p2', 'exam', 'total', 'total_max']);
});
