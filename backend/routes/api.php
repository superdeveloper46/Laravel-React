<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::group(['namespace' => 'Auth'], function () {
    Route::post('login', 'ApiLoginController@login');
});
Route::group([
    'namespace' => 'Auth',
    'middleware' => 'api',
    'prefix' => 'password'
], function () {
    Route::post('reset', 'PasswordResetController@reset');
});

Route::group(['namespace' => 'Api'], function () {
    Route::prefix('v1')->group(function () {
        Route::post('agencies', 'AgencyController@store');

        Route::post('twilio/conference/{companyId}/{agentId}', 'TwilioController@conference');
        Route::post('twilio/recording/{leadId}', 'TwilioController@recording');
        Route::get('twilio/token/{leadId}', 'TwilioController@token');

        Route::post('agencies/{agencyUUID}/companies', 'AgencyController@storeCompany');
        Route::get('campaigns/{campaignUUID}', 'CampaignController@getIntegration');
        Route::post('campaigns/{campaignUUID}/leads', 'CampaignController@createLead');
        Route::get('campaigns/leads/webhook', 'CampaignController@facebookWebHook');
        Route::post('campaigns/leads/webhook', 'CampaignController@facebookWebHookPost');
        Route::post('campaigns/leads/twilio/webhook', 'CampaignController@twilioWebHook');
        Route::get('leads/{lead}/export-pdf', 'LeadController@exportToPDF');
        Route::get('leads/{lead}/export-csv', 'LeadController@exportToCSV');

        Route::post('leads/sms-reply', 'LeadReplyController@onSMSReply');
        Route::post('leads/voice-reply', 'LeadReplyController@onVoiceReply');
        Route::get('leads/{lead}/actions/{dealAction}/mail-open', 'LeadReplyController@onMailReply');

        Route::get('reports/{uuid}/download', 'LeadController@download');

        Route::post('leads/{lead}/send-sms', 'TwilioController@sendSMS');

    });
});

Route::middleware(['auth:api', 'auth-user', 'cors'])->prefix('v1')->group(
    function () {
        Route::group(['namespace' => 'Auth'], function () {
            Route::post('autologin', 'ApiLoginController@autologin');
        });

        Route::group(['namespace' => 'Api\Management'], function () {
            Route::get('profile', 'ProfileController@index');
            Route::patch('profile', 'ProfileController@update');
            Route::post('reports', 'ReportController@store');
            Route::get('reports/{uuid}/poll', 'ReportController@poll');
            Route::get('timezones', 'TimezonesController@all');
        });

        Route::group(['namespace' => 'Api\Management\Admin'], function () {
            Route::prefix('admin')->group(function () {
                Route::apiResource('users', 'UserController');
            });
        });

        Route::group(['namespace' => 'Api\Management\Agency'], function () {
            Route::prefix('agency')->group(function () {
                Route::get('deals', 'DealController@all');
                Route::get('deals/leads/statistics', 'DealController@getDealsStatistic');
                Route::get('leads', 'LeadController@all');
                Route::apiResource('agents', 'AgentController');
                Route::get('agents/{agentId}/restore', 'AgentController@restore');
                Route::get('agents/{agentId}/graph/{graphType}', 'AgentController@graph');
                Route::get('agents/{agentId}/campaigns', 'AgentController@campaigns');
                Route::apiResource('companies', 'CompanyController');
                Route::patch('companies/{company}/lock-status', 'CompanyController@lockStatus');
                Route::get('companies/{company}/lead-stats', 'CompanyController@companyLeadStats');
                Route::apiResource('companies/{company}/leads', 'LeadController');
                Route::get('companies/{companyId}/graph/{graphType}', 'CompanyController@graph');
                Route::apiResource('companies/{company}/leads/{lead}/notes', 'LeadNotesController');
                Route::apiResource('companies/{company}/deals', 'DealController');
                Route::apiResource('companies/{company}/deals/{deal}/campaigns', 'CampaignController');

                Route::post(
                    'campaigns/{campaign}/fb-integrations',
                    'CampaignFacebookIntegrationController@subscribe'
                );

                Route::delete(
                    'campaigns/{campaign}/fb-integrations/{integration}',
                    'CampaignFacebookIntegrationController@unsubscribe'); 

            });
        });

        Route::group(['namespace' => 'Api\Management\Agent'], function () {
            Route::prefix('agent')->group(function () {
                Route::get('companies', 'CompanyController@companies');
                Route::apiResource('devices', 'DeviceController')->middleware('scope:DEVICE_READ,DEVICE_WRITE');
                Route::apiResource('leads', 'LeadController')->middleware('scope:LEAD_READ,LEAD_WRITE');
                Route::apiResource('leads/{leadId}/reminders', 'ReminderController')->middleware('scope:LEAD_READ,LEAD_WRITE');
                Route::apiResource('leads/{lead}/notes', 'LeadNoteController')->middleware('scope:LEAD_NOTE_READ,LEAD_NOTE_WRITE');
                Route::get('leads/graph/{graphType}', 'LeadController@graph');
            });
        });

        Route::group(['namespace' => 'Api\Management\Company'], function () {
            Route::prefix('company')->group(function () {
                Route::apiResource('agents', 'AgentController')->middleware('scope:AGENT_READ')->only(['index', 'show']);
                Route::apiResource('agents', 'AgentController')->middleware('scope:AGENT_WRITE')->only(['store', 'update', 'destroy']);
                Route::get('agents/{agentId}/restore', 'AgentController@restore');
                Route::get('agents/{agentId}/campaigns', 'AgentController@campaigns');
                Route::apiResource('deals', 'DealController')->middleware('scope:DEAL_READ')->only(['index', 'show']);
                Route::get('deals/leads/statistics', 'DealController@getDealsStatistic');
                Route::apiResource('deals', 'DealController')->middleware('scope:DEAL_WRITE')->only(['store', 'update', 'destroy']);
                Route::apiResource('deals/{deal}/campaigns', 'CampaignController')->middleware('scope:CAMPAIGN_READ,CAMPAIGN_WRITE');
                Route::apiResource('leads', 'LeadController')->middleware('scope:LEAD_READ,LEAD_WRITE');
                Route::apiResource('leads/{lead}/notes', 'LeadNoteController')->middleware('scope:LEAD_NOTE_READ,LEAD_NOTE_WRITE');
                Route::get('graph/{graphType}', 'CompanyController@graph');
                Route::get('agents/{agentId}/graph/{graphType}', 'AgentController@graph');

                Route::post(
                    'campaigns/{campaign}/fb-integrations',
                    'CampaignFacebookIntegrationController@subscribe'
                );

                Route::delete(
                    'campaigns/{campaign}/fb-integrations/{integration}',
                    'CampaignFacebookIntegrationController@unsubscribe');

                Route::apiResource('deals/{deal}/actions', 'DealActionController')
                    ->middleware('scope:DEAL_ACTION_READ,DEAL_ACTION_WRITE');
            });
        });
    });
