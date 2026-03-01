<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'fee_structure_id',
        'invoice_number',
        'control_number',
        'status',
        'created_by'
    ];

    protected $casts = [
        'status' => 'string',
    ];

    // protected $appends = ['total_amount', 'paid_amount', 'balance_amount', 'payment_status'];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function feeStructure()
    {
        return $this->belongsTo(FeeStructure::class, 'fee_structure_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'payment_id');
    }

    // Access to fee group through fee structure (no redundancy)
    public function feeGroup()
    {
        return $this->feeStructure->feeGroup;
    }

    // Computed attributes - no stored redundancy
    public function getTotalAmountAttribute()
    {
        return $this->feeStructure ? $this->feeStructure->amount : 0;
    }

    // Static method for total expected (not a scope since it returns value)
    public static function getTotalExpected()
    {
        return self::with('feeStructure')
            ->get()
            ->sum(function($payment) {
                return $payment->feeStructure ? $payment->feeStructure->amount : 0;
            });
    }

    // Scopes for filtering (return query builders)
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // public function getPaidAmountAttribute()
    // {
    //     return $this->transactions()
    //         ->where('status', 'completed')
    //         ->sum('amount_paid');
    // }

    // public function getBalanceAmountAttribute()
    // {
    //     return $this->total_amount - $this->paid_amount;
    // }

    // public function getPaymentStatusAttribute()
    // {
    //     $balance = $this->balance_amount;
    //     if ($balance <= 0) return 'paid';
    //     if ($this->paid_amount > 0) return 'partial';
    //     return 'pending';
    // }

    // public function getFormattedTotalAmountAttribute()
    // {
    //     return number_format($this->total_amount, 2) . ' ' . ($this->feeStructure->currency ?? 'TZS');
    // }

    // public function getFormattedPaidAmountAttribute()
    // {
    //     return number_format($this->paid_amount, 2) . ' ' . ($this->feeStructure->currency ?? 'TZS');
    // }

    // public function getFormattedBalanceAmountAttribute()
    // {
    //     return number_format($this->balance_amount, 2) . ' ' . ($this->feeStructure->currency ?? 'TZS');
    // }

    // Scopes
    // public function scopeByStudent($query, $studentId)
    // {
    //     return $query->where('student_id', $studentId);
    // }

    // public function scopeByAcademicYear($query, $academicYear)
    // {
    //     return $query->where('academic_year', $academicYear);
    // }

    // public function scopeByTerm($query, $term)
    // {
    //     return $query->where('term', $term);
    // }

    // public function scopePending($query)
    // {
    //     return $query->where('status', 'pending');
    // }

    // public function scopePartial($query)
    // {
    //     return $query->where('status', 'partial');
    // }

    // public function scopePaid($query)
    // {
    //     return $query->where('status', 'paid');
    // }

    // public function scopeOverdue($query)
    // {
    //     return $query->where('due_date', '<', now())
    //                  ->where('status', '!=', 'paid');
    // }
}
