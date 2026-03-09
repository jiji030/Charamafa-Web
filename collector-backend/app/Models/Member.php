<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{    protected $table = 'members';
    protected $primaryKey = 'member_id';
    public $timestamps = false; // Disable timestamps - table uses registration_date and update_date instead    
    protected $fillable = [
        'account_no', 'purok_id', 'ts_Id', 'meter_no',
        'fname', 'mname', 'lname', 'suffix',
        'barangay', 'municipality', 'province', 'zip_code', 'region',
        'date_of_birth', 'place_of_birth', 'sex', 'civil_status',
        'religion', 'ethnicity', 'language', 'education_attainment',
        'school_address', 'course', 'year_graduated', 'mobile_no',
        'height', 'weight', 'occupation', 'company_address',
        'spouse_fname', 'spouse_mname', 'spouse_lname', 'spouse_suffix',
        'spouse_date_of_birth', 'spouse_address', 'spouse_ethnicity',
        'spouse_occupation', 'spouse_phone_no', 'membership_fee_id',
        'is_approved', 'photo_name', 'photo_path', 'registration_date',
        'update_date', 'government_type_id', 'government_no',
        'prev_balance', 'connection_status', 'reconnection_date', 'is_read', 'is_paid',
        'damage_charges'
    ];    protected $casts = [
        'prev_balance' => 'decimal:2',
        'damage_charges' => 'decimal:2',
        'connection_status' => 'integer',
        'is_read' => 'boolean',
        'is_paid' => 'boolean',
        'reconnection_date' => 'date'
    ];

    public function purok()
    {
        return $this->belongsTo(Purok::class, 'purok_id');
    }

    public function tsNumber()
    {
        return $this->belongsTo(TsNumber::class, 'ts_Id');
    }

    public function membershipFee()
    {
        return $this->belongsTo(MembershipFee::class, 'membership_fee_id');
    }

    public function waterConsumptions()
    {
        return $this->hasMany(WaterConsumption::class, 'member_Id');
    }

    public function getLatestConsumption()
    {
        return $this->waterConsumptions()->latest('reading_date')->first();
    }    public function getFullNameAttribute()
    {
        return trim("{$this->fname} {$this->mname} {$this->lname} {$this->suffix}");
    }

    /**
     * Get all members that share the same meter number
     */
    public function sharedMeterMembers()
    {
        if (!$this->meter_no) {
            return collect([$this]);
        }
        
        return static::where('meter_no', $this->meter_no)
                     ->where('meter_no', '!=', '')
                     ->whereNotNull('meter_no')
                     ->get();
    }    /**
     * Update shared meter payment status and balance (all members sharing same meter get same values)
     */
    public function updateSharedMeterPaymentStatus($isPaid, $newBalance = null)
    {
        if (!$this->meter_no) {
            // No shared meter, update only this member
            $this->update([
                'is_paid' => $isPaid ? 1 : 0,
                'prev_balance' => $newBalance ?? $this->prev_balance
            ]);
            return;
        }

        // For shared meters: Update ALL members with same meter number
        // They all get the same balance and payment status since they share the same meter
        $updateData = ['is_paid' => $isPaid ? 1 : 0];
        
        if ($newBalance !== null) {
            $updateData['prev_balance'] = $newBalance;
        }
        
        static::where('meter_no', $this->meter_no)
              ->where('meter_no', '!=', '')
              ->whereNotNull('meter_no')
              ->update($updateData);
    }

    /**
     * Update connection status for all members sharing the same meter
     */
    public function updateSharedMeterConnectionStatus($connectionStatus, $reconnectionDate = null)
    {
        if (!$this->meter_no) {
            // No shared meter, update only this member
            $updateData = ['connection_status' => $connectionStatus];
            if ($reconnectionDate) {
                $updateData['reconnection_date'] = $reconnectionDate;
            }
            $this->update($updateData);
            return;
        }

        // Update all members with same meter number
        $updateData = ['connection_status' => $connectionStatus];
        if ($reconnectionDate) {
            $updateData['reconnection_date'] = $reconnectionDate;
        }

        static::where('meter_no', $this->meter_no)
              ->where('meter_no', '!=', '')
              ->whereNotNull('meter_no')
              ->update($updateData);
    }

    /**
     * Check if any member sharing this meter has already paid
     */
    public function hasSharedMeterPaid()
    {
        if (!$this->meter_no) {
            return $this->is_paid;
        }

        return static::where('meter_no', $this->meter_no)
                     ->where('meter_no', '!=', '')
                     ->whereNotNull('meter_no')
                     ->where('is_paid', 1)
                     ->exists();
    }

    /**
     * Get the shared meter balance (should be same for all members sharing meter)
     */
    public function getSharedMeterBalance()
    {
        if (!$this->meter_no) {
            return $this->prev_balance;
        }

        $sharedMember = static::where('meter_no', $this->meter_no)
                             ->where('meter_no', '!=', '')
                             ->whereNotNull('meter_no')
                             ->first();

        return $sharedMember ? $sharedMember->prev_balance : $this->prev_balance;
    }
}