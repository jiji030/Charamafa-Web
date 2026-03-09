<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaterConsumption extends Model
{
    protected $table = 'water_consumptions';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'member_Id',
        'prev_CUM_consumption',
        'present_CUM_consumption',
        'prev_meter_reading',
        'present_meter_reading',
        'others',
        'reading_date',
        'processed_by',
        'is_synced'
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_Id');
    }
}