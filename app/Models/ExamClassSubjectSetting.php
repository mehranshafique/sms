<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamClassSubjectSetting extends Model
{
    protected $fillable = [
        'exam_id',
        'class_section_id',
        'subject_id',
        'is_examined',
    ];

    protected $casts = [
        'is_examined' => 'boolean',
    ];
}
