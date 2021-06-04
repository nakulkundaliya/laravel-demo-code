<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'session_id', 'source_address', 'request_payload', 'response_payload', 'response', 'request_timestamp', 'response_timestamp',
    ];

    static function create_audit_log($audit_data = array()) {
        if (!empty($audit_data)) {
            AuditLog::create($audit_data);
        }
    }
}
