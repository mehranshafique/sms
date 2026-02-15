<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffLeave extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'staff_id',
        'type',
        'reason',
        'start_date',
        'end_date',
        'status',
        'admin_remarks',
        'action_by',
        'file_path'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
    
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function actioner()
    {
        return $this->belongsTo(User::class, 'action_by');
    }
}