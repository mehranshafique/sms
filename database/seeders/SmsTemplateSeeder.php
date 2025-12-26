<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SmsTemplate;

class SmsTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Institution Creation Template (Global)
        SmsTemplate::updateOrCreate(
            ['event_key' => 'institution_creation', 'institution_id' => null],
            [
                'name' => 'Institution Created',
                'body' => 'Dear Partner, your institution has been created $Acronym or $InstitutionName from digitex system, your ID: $ID, Password: $Pw web site: e-digitex.com.',
                'available_tags' => '$Acronym, $InstitutionName, $ID, $Pw',
                'is_active' => true,
            ]
        );

        // 2. Candidate Added (Example for Voting Module)
        SmsTemplate::updateOrCreate(
            ['event_key' => 'candidate_added', 'institution_id' => null],
            [
                'name' => 'Candidate Registration',
                'body' => 'Your child $StudentName has submitted a candidacy for the position of $Position.',
                'available_tags' => '$StudentName, $Position',
                'is_active' => true,
            ]
        );
    }
}