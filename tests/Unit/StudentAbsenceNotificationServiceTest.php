<?php

use App\Services\StudentAbsenceNotificationService;

it('extracts absent student ids from attendance map', function () {
    $service = app(StudentAbsenceNotificationService::class);

    $ids = $service->absentStudentIdsFromMap([
        10 => 'present',
        11 => 'absent',
        12 => ['status' => 'absent'],
        13 => 'late',
        14 => ['status' => 'present'],
    ]);

    expect($ids->all())->toBe([11, 12]);
});
