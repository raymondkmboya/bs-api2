<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'transaction_number',
        'amount_paid',
        'payment_method',
        'transaction_date',
        'reference_number',
        'notes',
        'status'
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'payment_method' => 'string',
        'transaction_date' => 'date',
        'status' => 'string'
    ];

    protected $appends = ['payment_amount', 'fee_group_info'];

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
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

    // Access to student through payment (no redundancy)
    public function student()
    {
        return $this->payment->student;
    }

    // Computed attributes
    public function getPaymentAmountAttribute()
    {
        return $this->payment ? $this->payment->total_amount : 0;
    }

    public function getFeeGroupInfoAttribute()
    {
        return $this->payment && $this->payment->feeStructure && $this->payment->feeStructure->feeGroup
            ? [
                'id' => $this->payment->feeStructure->feeGroup->id,
                'name' => $this->payment->feeStructure->feeGroup->fee_group_name,
                'type' => $this->payment->feeStructure->feeGroup->type
            ]
            : null;
    }

    public function getFormattedAmountPaidAttribute()
    {
        return number_format($this->amount_paid, 2) . ' ' . ($this->feeStructure->currency ?? 'TZS');
    }

    public function getPaymentMethodLabelAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->payment_method));
    }

    public function getStatusLabelAttribute()
    {
        return ucfirst($this->status);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->whereHas('payment', function($q) use ($studentId) {
            $q->where('student_id', $studentId);
        });
    }

    public function scopeByFeeGroup($query, $feeGroupId)
    {
        return $query->whereHas('payment.feeStructure', function($q) use ($feeGroupId) {
            $q->where('fee_group_id', $feeGroupId);
        });
    }
}
