<?php

use App\Services\ApplicationGradeService;

it('maps primary application grades from default scale', function () {
    $service = new ApplicationGradeService();

    expect($service->fromPercentage(85, null, 'primary'))->toBe('A')
        ->and($service->fromPercentage(70, null, 'primary'))->toBe('B')
        ->and($service->fromPercentage(55, null, 'primary'))->toBe('AB')
        ->and($service->fromPercentage(40, null, 'primary'))->toBe('C');
});

it('maps secondary application grades from legacy defaults when unset', function () {
    $service = new ApplicationGradeService();

    expect($service->fromPercentage(82, null, 'secondary'))->toBe('E')
        ->and($service->fromPercentage(72, null, 'secondary'))->toBe('TB')
        ->and($service->fromPercentage(62, null, 'secondary'))->toBe('B')
        ->and($service->fromPercentage(52, null, 'secondary'))->toBe('AB')
        ->and($service->fromPercentage(40, null, 'secondary'))->toBe('F');
});
