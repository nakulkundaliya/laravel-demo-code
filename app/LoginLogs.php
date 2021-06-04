<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LoginLogs extends Model
{
    //
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 
        'session_id', 
        'session_origin', 
        'response'
    ];

    static function createLoginLog($audit_data = array()) {
        if (!empty($audit_data)) {
            LoginLogs::create($audit_data);
        }
    }
}
