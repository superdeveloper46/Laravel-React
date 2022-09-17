<?php

use Illuminate\Http\Request;

Route::post('funnel_webhooks/test', 'Api\CampaignController@clickFunnelWebHookAuthorise');
