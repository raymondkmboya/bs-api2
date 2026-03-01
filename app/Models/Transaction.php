<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'student_id',
        'transaction_number',
        'amount_paid',
        'payment_method',
        'transaction_date',
        'transaction_ref',
        'transaction_reciept',
        'notes',
        'verified_by',
        'created_by',
        'status'
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'payment_method' => 'string',
        'transaction_date' => 'date',
        'status' => 'string'
    ];

    // protected $appends = ['payment_amount', 'fee_group_info'];

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    /**
     * Get the student for this transaction.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    // Access to fee structure through payment (no redundancy)
    public function feeStructure()
    {
        return $this->payment->feeStructure;
    }

    // Access to fee group through payment->feeStructure (no redundancy)
    public function feeGroup()
    {
        return $this->payment->feeStructure->feeGroup;
    }

    /**
     * Get the user who created this transaction.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by')->with('staff');
    }

    // Scopes for transaction statistics
    public function scopeByStatus($query, $status)
    {
        return $query->where('verification_status', $status);
    }

    public function scopeApproved($query)
    {
        return $query->where('verification_status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('verification_status', 'rejected');
    }

    public function scopePending($query)
    {
        return $query->where('verification_status', 'pending');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->with('staff');
    }

    public function scopeDaily($query)
    {
        return $query->where('transaction_date', now());
    }

    // Computed attributes
    // public function getPaymentAmountAttribute()
    // {
    //     return $this->payment ? $this->payment->total_amount : 0;
    // }

    // public function getFeeGroupInfoAttribute()
    // {
    //     return $this->payment && $this->payment->feeStructure && $this->payment->feeStructure->feeGroup
    //         ? [
    //             'id' => $this->payment->feeStructure->feeGroup->id,
    //             'name' => $this->payment->feeStructure->feeGroup->fee_group_name,
    //             'type' => $this->payment->feeStructure->feeGroup->type
    //         ]
    //         : null;
    // }

    // public function getFormattedAmountPaidAttribute()
    // {
    //     return number_format($this->amount_paid, 2) . ' ' . ($this->feeStructure->currency ?? 'TZS');
    // }

    // public function getPaymentMethodLabelAttribute()
    // {
    //     return ucfirst(str_replace('_', ' ', $this->payment_method));
    // }

    // public function getStatusLabelAttribute()
    // {
    //     return ucfirst($this->status);
    // }

    // Scopes
    // public function scopeCompleted($query)
    // {
    //     return $query->where('status', 'completed');
    // }

    // public function scopeFailed($query)
    // {
    //     return $query->where('status', 'failed');
    // }

    // public function scopeRefunded($query)
    // {
    //     return $query->where('status', 'refunded');
    // }

    // public function scopeByPaymentMethod($query, $method)
    // {
    //     return $query->where('payment_method', $method);
    // }

    // public function scopeByDateRange($query, $startDate, $endDate)
    // {
    //     return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    // }

    // public function scopeByStudent($query, $studentId)
    // {
    //     return $query->whereHas('payment', function($q) use ($studentId) {
    //         $q->where('student_id', $studentId);
    //     });
    // }

    // public function scopeByFeeGroup($query, $feeGroupId)
    // {
    //     return $query->whereHas('payment.feeStructure', function($q) use ($feeGroupId) {
    //         $q->where('fee_group_id', $feeGroupId);
    //     });
    // }
}
