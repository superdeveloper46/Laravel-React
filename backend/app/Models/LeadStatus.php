<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadStatus extends Model
{
    use SoftDeletes;

    public static $STATUS_NONE = 'NONE';
    public static $STATUS_NEW = 'NEW';
    public static $STATUS_VIEWED = 'VIEWED';
    public static $STATUS_CONTACTED_SMS = 'CONTACTED_SMS';
    public static $STATUS_CONTACTED_CALL = 'CONTACTED_CALL';
    public static $STATUS_CONTACTED_EMAIL = 'CONTACTED_EMAIL';
    public static $STATUS_MISSED = 'MISSED';
    public static $STATUS_BAD = 'BAD';
    public static $STATUS_SOLD = 'SOLD';
    public static $SMS_REPLY = 'SMS_REPLY';

    protected $fillable = [
        'name',
        'description',
        'type',
    ];
}
