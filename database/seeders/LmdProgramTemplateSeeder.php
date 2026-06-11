<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\Department;
use App\Models\Program;
use App\Models\AcademicUnit;
use Illuminate\Database\Seeder;

/**
 * Seeds Licence (6 semesters) and Master (4 semesters) LMD program templates.
 * Run after a university institution exists: php artisan db:seed --class=LmdProgramTemplateSeeder
 */
class LmdProgramTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $institution = Institution::query()
            ->whereIn('type', ['university', 'mixed', 'vocational'])
            ->where('is_active', true)
            ->first();

        if (!$institution) {
            $this->command?->warn('No university-type institution found. Skipping LMD program templates.');
            return;
        }

        $department = Department::firstOrCreate(
            ['institution_id' => $institution->id, 'name' => 'Sciences Générales'],
            ['code' => 'SG']
        );

        $templates = [
            [
                'name' => 'Licence (LMD)',
                'code' => 'LIC',
                'total_semesters' => 6,
                'duration_years' => 3,
                'units_per_semester' => 4,
            ],
            [
                'name' => 'Master (LMD)',
                'code' => 'MAS',
                'total_semesters' => 4,
                'duration_years' => 2,
                'units_per_semester' => 3,
            ],
        ];

        foreach ($templates as $tpl) {
            $program = Program::firstOrCreate(
                ['institution_id' => $institution->id, 'code' => $tpl['code']],
                [
                    'department_id' => $department->id,
                    'name' => $tpl['name'],
                    'total_semesters' => $tpl['total_semesters'],
                    'duration_years' => $tpl['duration_years'],
                    'is_active' => true,
                ]
            );

            for ($sem = 1; $sem <= $tpl['total_semesters']; $sem++) {
                for ($u = 1; $u <= $tpl['units_per_semester']; $u++) {
                    $code = $tpl['code'] . '-S' . $sem . '-UE' . $u;
                    AcademicUnit::firstOrCreate(
                        ['program_id' => $program->id, 'code' => $code],
                        [
                            'institution_id' => $institution->id,
                            'name' => "UE {$u} — Semestre {$sem}",
                            'type' => 'fundamental',
                            'semester' => $sem,
                            'total_credits' => 6,
                        ]
                    );
                }
            }
        }

        $this->command?->info("LMD program templates seeded for institution #{$institution->id} ({$institution->name}).");
    }
}
