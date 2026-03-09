<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purok extends Model
{
    protected $table = 'puroks';
    protected $primaryKey = 'purok_id';
    public $timestamps = false;

    protected $fillable = ['purok'];
}