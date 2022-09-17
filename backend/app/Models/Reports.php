<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reports extends Model
{
    use SoftDeletes;

    protected $table = 'reports';
    public static $STATUS_NONE = 'NONE';
    public static $STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public static $STATUS_COMPLETED = 'COMPLETED';
    public static $STATUS_FAILED = 'FAILED';

    public static $TYPE_LEADS_PDF = 'TYPE_LEADS_PDF';
    public static $TYPE_LEADS_CSV = 'TYPE_LEADS_CSV';
    public static $TYPE_COMPANY_CSV = 'TYPE_COMPANY_CSV';
    public static $TYPE_COMPANY_PDF = 'TYPE_COMPANY_PDF';
    public static $TYPE_CAMPAIGN_CSV = 'TYPE_CAMPAIGN_CSV';
    public static $TYPE_CAMPAIGN_PDF = 'TYPE_CAMPAIGN_PDF';

    protected $fillable = [
        'user_id',
        'payload',
        'type',
        'status',
        'uuid',
    ];

    public function user() {
        return $this->belongsTo('App\Models\User', 'user_id');
    }
}
