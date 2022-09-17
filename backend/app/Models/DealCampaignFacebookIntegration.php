<?php

namespace App\Models;

use App\Services\MailService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Mockery\Exception;

class DealCampaignFacebookIntegration extends Model
{
    protected $table = 'deal_campaign_facebook_integrations';
    protected $fillable = [
        'deal_campaign_id',
        'form_name',
        'page_name',
        'fb_page_id',
        'fb_form_id',
        'fb_page_access_token',
        'fb_token_expire_at',
        'fb_ad_account_id',
    ];
}
