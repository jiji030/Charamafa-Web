<?php
// app/Models/User.php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'users';
    protected $primaryKey = 'admin_id';
    public $timestamps = false;

    protected $fillable = [
        'fname', 'mname', 'lname', 'suffix', 'contact_no',
        'username', 'password', 'purok_id', 'role_id',
        'association_id', 'last_login', 'last_date_synced'
    ];

    protected $hidden = ['password'];

    // ADD THIS METHOD - Tell Sanctum to use admin_id
    public function getAuthIdentifierName()
    {
        return 'admin_id';
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function purok()
    {
        return $this->belongsTo(Purok::class, 'purok_id');
    }
}