<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportantInformation extends Model
{
    protected $table = 'important_information';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'minimum_amount_per_month',
        'excess_minimum_CUM_per_month',
        'lossdamage_and_other_charges',
        'electricity_consumption',
        'generator_consumption',        'announcement',
        'free_CUM_per_month',
        'miscellaneous',
        'connector_damage_with_unknown_person'
    ];
}
