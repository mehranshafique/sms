<?php

namespace Database\Seeders;

use App\Models\AcademicSession;
use App\Models\ClassSection;
use App\Models\Department;
use App\Models\Election;
use App\Models\ElectoralCycle;
use App\Models\GradeLevel;
use App\Models\Institution;
use App\Services\Voting\ElectionEligibilityService;
use App\Services\Voting\ElectoralCycleService;
use Illuminate\Database\Seeder;

/**
 * Optional demo: one cycle with three elections and different eligibility.
 * php artisan db:seed --class=VotingV2DemoSeeder
 */
class VotingV2DemoSeeder extends Seeder
{
    public function run(): void
    {
        $institution = Institution::query()->first();
        if (! $institution) {
            $this->command?->warn('No institution found; skip VotingV2DemoSeeder.');

            return;
        }

        $session = AcademicSession::where('institution_id', $institution->id)->where('is_current', true)->first()
            ?? AcademicSession::where('institution_id', $institution->id)->first();

        $cycles = app(ElectoralCycleService::class);
        $eligibility = app(ElectionEligibilityService::class);

        $cycle = ElectoralCycle::withoutGlobalScopes()->updateOrCreate(
            [
                'institution_id' => $institution->id,
                'title' => 'Student Elections Demo 2026',
            ],
            [
                'academic_session_id' => $session?->id,
                'description' => 'Demo cycle: President (all), Department race, Class delegate',
                'start_date' => now()->subDay(),
                'end_date' => now()->addWeeks(2),
                'status' => 'active',
            ]
        );

        $president = Election::withoutGlobalScopes()->updateOrCreate(
            ['electoral_cycle_id' => $cycle->id, 'title' => 'Student President'],
            [
                'institution_id' => $institution->id,
                'academic_session_id' => $session?->id,
                'start_date' => now()->subDay(),
                'end_date' => now()->addWeeks(2),
                'status' => Election::STATUS_DRAFT,
                'eligibility_type' => Election::ELIGIBILITY_ALL,
                'eligibility_ids' => [],
            ]
        );
        if ($president->positions()->count() === 0) {
            $president->positions()->create(['name' => 'President', 'sequence' => 1]);
        }

        $dept = Department::where('institution_id', $institution->id)->first();
        $faculty = Election::withoutGlobalScopes()->updateOrCreate(
            ['electoral_cycle_id' => $cycle->id, 'title' => 'Faculty / Department Representative'],
            [
                'institution_id' => $institution->id,
                'academic_session_id' => $session?->id,
                'start_date' => now()->subDay(),
                'end_date' => now()->addWeeks(2),
                'status' => Election::STATUS_DRAFT,
                'eligibility_type' => $dept ? Election::ELIGIBILITY_DEPARTMENTS : Election::ELIGIBILITY_ALL,
                'eligibility_ids' => $dept ? [$dept->id] : [],
            ]
        );
        if ($faculty->positions()->count() === 0) {
            $faculty->positions()->create(['name' => 'Representative', 'sequence' => 1]);
        }

        $section = ClassSection::where('institution_id', $institution->id)->first();
        $delegate = Election::withoutGlobalScopes()->updateOrCreate(
            ['electoral_cycle_id' => $cycle->id, 'title' => 'Year-Group / Class Delegate'],
            [
                'institution_id' => $institution->id,
                'academic_session_id' => $session?->id,
                'start_date' => now()->subDay(),
                'end_date' => now()->addWeeks(2),
                'status' => Election::STATUS_DRAFT,
                'eligibility_type' => $section ? Election::ELIGIBILITY_SECTIONS : Election::ELIGIBILITY_ALL,
                'eligibility_ids' => $section ? [$section->id] : [],
            ]
        );
        if ($delegate->positions()->count() === 0) {
            $delegate->positions()->create(['name' => 'Class Delegate', 'sequence' => 1]);
        }

        foreach ([$president, $faculty, $delegate] as $election) {
            $eligibility->syncFromRules($election);
        }

        $this->command?->info('Voting V2 demo cycle ready: '.$cycle->title.' (institution #'.$institution->id.')');
    }
}
