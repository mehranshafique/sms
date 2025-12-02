<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_id',
        'registration_no',
        'first_name',
        'last_name',
        'middle_name',
        'gender',
        'date_of_birth',
        'national_id',
        'nfc_tag_uid',
        'qr_code_token',
        'status',
    ];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }
}
