<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TsNumber extends Model
{
    protected $table = 'ts_numbers';
    protected $primaryKey = 'ts_Id';
    public $timestamps = false;

    protected $fillable = ['ts_no', 'landmark'];
}