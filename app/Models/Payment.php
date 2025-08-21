<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Payment extends Model
{
    use HasFactory;

    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'user_id', 'booking_id', 'amount', 'currency', 'payment_method', 'payment_status', 'transaction_id', 'paid_at', 'refunded_amount', 'refund_reason', 'refunded_at' 
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'refunded_amount' => 'decimal:2', 
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',     
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    public function getPaymentReferenceAttribute()
    {
        return 'PAY-' . str_pad($this->payment_id, 8, '0', STR_PAD_LEFT);
    }

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2) . ' ' . strtoupper($this->currency);
    }

    public function getNetAmountAttribute()
    {
        return $this->amount - ($this->refunded_amount ?? 0);
    }

    public function getFormattedNetAmountAttribute()
    {
        return number_format($this->net_amount, 2) . ' ' . strtoupper($this->currency);
    }

    public function getFormattedRefundedAmountAttribute()
    {
        if (!$this->refunded_amount) {
            return null;
        }
        return number_format($this->refunded_amount, 2) . ' ' . strtoupper($this->currency);
    }

    public function getStatusBadgeColorAttribute()
    {
        return match($this->payment_status) {
            'completed', 'paid' => 'success',
            'pending' => 'warning',
            'failed', 'cancelled' => 'danger',
            'refunded' => 'info',
            'partially_refunded' => 'primary',
            'processing' => 'primary',
            default => 'gray',
        };
    }

    public function getPaymentMethodBadgeColorAttribute()
    {
        return match($this->payment_method) {
            'credit_card', 'debit_card' => 'success',
            'paypal' => 'info',
            'bank_transfer' => 'warning',
            'cash' => 'primary',
            'crypto' => 'purple',
            default => 'gray',
        };
    }

    public function getIsRefundableAttribute()
    {
        return in_array($this->payment_status, ['completed', 'paid', 'partially_refunded']) && 
               $this->paid_at && 
               $this->paid_at->diffInDays(now()) <= 30 &&
               ($this->refunded_amount ?? 0) < $this->amount;
    }

    public function getIsOverdueAttribute()
    {
        if ($this->payment_status !== 'pending') {
            return false;
        }
        
        return $this->created_at->diffInDays(now()) > 7;
    }

    public function getRefundPercentageAttribute()
    {
        if (!$this->refunded_amount || !$this->amount) {
            return 0;
        }
        
        return round(($this->refunded_amount / $this->amount) * 100, 1);
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('payment_status', ['completed', 'paid', 'partially_refunded']);
    }

    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('payment_status', ['failed', 'cancelled']);
    }

    public function scopeRefunded($query, $includePartial = true)
    {
        if ($includePartial) {
            return $query->whereIn('payment_status', ['refunded', 'partially_refunded']);
        }
        
        return $query->where('payment_status', 'refunded');
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeByDateRange($query, $start, $end)
    {
        return $query->whereBetween('paid_at', [$start, $end]);
    }

    public function scopeOverdue($query)
    {
        return $query->where('payment_status', 'pending')
                    ->where('created_at', '<', Carbon::now()->subDays(7));
    }
}
