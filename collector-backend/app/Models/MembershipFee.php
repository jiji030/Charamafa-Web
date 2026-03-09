<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipFee extends Model
{
    protected $table = 'membership_fees';
    protected $primaryKey = 'membership_fee_id';
    public $timestamps = false;

    protected $fillable = ['fee_amount', 'description'];
}