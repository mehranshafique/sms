<?php

namespace Tests\Feature;

use App\Models\Institution;
use Tests\TestCase;

class InstitutionHeadPersonTest extends TestCase
{
    public function test_institution_model_includes_head_person_and_epst_fields(): void
    {
        $institution = new Institution([
            'name' => 'Test School',
            'head_person_name' => 'Jean Moutard',
            'head_person_phone' => '+243900000001',
            'epst_school_code' => 'EPST-1234',
        ]);

        $this->assertSame('Jean Moutard', $institution->head_person_name);
        $this->assertSame('EPST-1234', $institution->epst_school_code);
        $this->assertContains('head_person_name', $institution->getFillable());
        $this->assertContains('epst_school_code', $institution->getFillable());
    }
}
