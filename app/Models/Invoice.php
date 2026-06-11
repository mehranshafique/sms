<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory, LogsActivity;

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->payment_token)) {
                $invoice->payment_token = Str::random(48);
            }
        });
    }

    protected $fillable = [
        'institution_id',
        'academic_session_id',
        'student_id',
        'invoice_number',
        'issue_date',
        'due_date',
        'total_amount',
        'paid_amount',
        'status', // unpaid, partial, paid, overdue
        'payment_token',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function getPaymentUrlAttribute(): string
    {
        return route('pay.show', $this->payment_token);
    }
}