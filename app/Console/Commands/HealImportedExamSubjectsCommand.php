<?php

namespace App\Console\Commands;

use App\Models\Institution;
use App\Services\ExamRecordSubjectBinder;
use Illuminate\Console\Command;

class HealImportedExamSubjectsCommand extends Command
{
    protected $signature = 'exams:heal-imported-subjects {institution_id? : Limit to one school}';

    protected $description = 'Rebind imported exam marks onto this school\'s subjects so Enter Marks matches the result sheet';

    public function handle(ExamRecordSubjectBinder $binder): int
    {
        $ids = $this->argument('institution_id')
            ? [(int) $this->argument('institution_id')]
            : Institution::query()->orderBy('id')->pluck('id')->all();

        if ($ids === []) {
            $this->warn('No institutions found.');

            return self::SUCCESS;
        }

        foreach ($ids as $id) {
            $count = $binder->rebindForInstitution((int) $id);
            $this->info("Institution {$id}: rebound {$count} exam record(s).");
        }

        return self::SUCCESS;
    }
}
