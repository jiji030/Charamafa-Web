<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'payment_id';
    public $timestamps = false; // Since you're using manual created_at
    protected $fillable = [
        'member_id',
        'billing_date',
        'reference',
        'meter_reading',
        'cum_usage',
        'bill_amount',
        'balance',
        'amount_paid',
        'payment_date',
        'or_number',
        'payment_method',
        'collected_by'
    ];

    protected $casts = [
        'meter_reading' => 'integer',
        'cum_usage' => 'float',
        'bill_amount' => 'float',
        'balance' => 'float',
        'amount_paid' => 'float',
        'payment_date' => 'date',
        'billing_date' => 'date'
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id', 'member_id');
    }

    public function collector()
    {
        return $this->belongsTo(User::class, 'collected_by', 'admin_id');
    }

    // Helper methods
    public function isReconnectionPayment()
    {
        return str_contains($this->reference ?? '', 'RECON-') || 
               str_contains($this->or_number ?? '', 'RCN-');
    }

    public function isBalanceOnlyPayment()
    {
        return str_contains($this->reference ?? '', 'BAL-') || 
               str_contains($this->or_number ?? '', 'BAL-');
    }

    public function isRegularBillPayment()
    {
        return str_contains($this->reference ?? '', 'BILL-') || 
               str_contains($this->or_number ?? '', 'RCP-');
    }

    public function isRegularPayment()
    {
        return $this->isRegularBillPayment();
    }

    // Get payment amounts from actual database fields instead of notes
    public function getReconnectionFee()
    {
        return $this->isReconnectionPayment() ? 100 : 0; // Default reconnection fee
    }

    public function getPrevBalancePaid()
    {
        return $this->balance ?? 0; // The balance field stores the previous balance
    }

    public function getCashReceived()
    {
        return $this->amount_paid + $this->getChangeGiven();
    }

    public function getChangeGiven()
    {
        // Calculate change based on bill amount and amount paid
        if ($this->bill_amount && $this->amount_paid) {
            return max(0, $this->getCashReceived() - $this->bill_amount);
        }
        return 0;
    }

    // Scopes for filtering by payment type using new reference field
    public function scopeReconnections($query)
    {
        return $query->where('reference', 'LIKE', 'RECON-%')
                    ->orWhere('or_number', 'LIKE', 'RCN-%');
    }

    public function scopeBalanceOnly($query)
    {
        return $query->where('reference', 'LIKE', 'BAL-%')
                    ->orWhere('or_number', 'LIKE', 'BAL-%');
    }

    public function scopeRegular($query)
    {
        return $query->where('reference', 'LIKE', 'BILL-%')
                    ->orWhere('or_number', 'LIKE', 'RCP-%');
    }
}
