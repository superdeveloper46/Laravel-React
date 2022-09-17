<?php

namespace App\Http\Controllers\Api;

use App\Models\DealCampaign;
use App\Models\DealCampaignFacebookIntegration;
use App\Models\Device;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\LeadStatus;
use Facebook\Facebook;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Mockery\Exception;
use Twilio\Rest\Client;

class CampaignController extends Controller
{
    public function twilioWebHook(Request $request) {
        \Log::critical('twilio callback income');
        \Log::critical(print_r($request->all(), true));
        return response()->json(['success']);
    }

    public function getIntegration(Request $request, $campaignUUID) {
        $campaign = DealCampaign::where('uuid', $campaignUUID)->firstOrFail();
        if ($campaign->integration !== DealCampaign::$INTEGRATION_OPTIN_FORM) {
            $this->validate($request, [
                'missing_integration' => 'required'
            ]);
        }
        
        return $campaign->only(['uuid', 'integration', 'integration_config']);
    }
    
    public function createLead(Request $request, $campaignUUID) {
       try {
           \DB::beginTransaction();
           if ($request->has('contact')) {
               $contact = $request->input('contact');
               $request->merge([
                   'email' => $contact['email'],
                   'fullname' => $contact['name'],
                   'phone' => $contact['phone'],
                   'metadata' => $contact,
               ]);
           }

           $campaign = DealCampaign::where('uuid', $campaignUUID)->firstOrFail();

           $this->validateLead($request, $campaign);
           $leadStatus = LeadStatus::where('type', LeadStatus::$STATUS_NEW)->firstOrFail();
           $agent = $campaign->agents->first();
           if (!$agent) {
               throw new \Exception('Missing required agent');
           }

           $campaign
               ->agents()
               ->updateExistingPivot($agent['id'], [
                   'agent_leads_count' => $agent['pivot']['agent_leads_count'] + 1,
               ]);
           $request->merge([
               'agency_company_id' => $campaign->agency_company_id,
               'agent_id' => $agent['id'],
               'lead_status_id' => $leadStatus->id,
               'deal_campaign_id' => $campaign->id,
           ]);
           $lead = new Lead();
           $request->merge([
               'metadata' => \json_encode($request->input('metadata')),
           ]);
           $lead->fill($request->only([
               'agency_company_id',
               'agent_id',
               'lead_status_id',
               'deal_campaign_id',
               'fullname',
               'phone',
               'email',
               'metadata',
           ]));
           $lead->save();
           LeadNote::create([
               'lead_status_id' => $leadStatus->id,
               'lead_id' => $lead->id,
               'agent_id' => $agent['id'],
               'message' => "Lead Created from {$campaign->integration}",
           ]);
           \DB::commit();
           $notification = [
               'title' => 'New Lead',
               'body' => 'New Lead created: '.$lead->fullname,
               'sound' => true,
           ];
           $tokenList = Device::getTokenListFromAgentIds([$lead->agent_id]);
           Lead::notification($tokenList, $notification);

           if ($campaign->deal->has_automation) {
               $action = $campaign->deal->getFirstRootAction();
               if ($action) {
                   $action->scheduleLeadAction($lead);
               }
           }

           return $lead;
       } catch (Exception $exception) {
           \DB::rollBack();
           \Log::critical($exception->getMessage());
           throw $exception;
       }
    }
    
    /**
     * @param Request $request
     * @param $campaign
     */
    public function validateLead(Request $request, $campaign)
    {
        if ($campaign->integration_config && strtoupper($campaign->integration) !== DealCampaign::$INTEGRATION_FACEBOOK) {
            $formFields = \json_decode($campaign->integration_config);
            if ($formFields && $formFields->fullname->isRequired) {
                $this->validate($request, [
                    'fullname' => 'required|string',
                ]);
            }

            if ($formFields && $formFields->phone->isRequired) {
                $this->validate($request, [
                    'phone' => 'required|string',
                ]);
            }


            if ($formFields && $formFields->email->isRequired) {
                $this->validate($request, [
                    'email' => 'required|email',
                ]);
            }
        } else {
            $this->validate($request, [
                'email' => 'required|email',
            ]);
        }
    }

    public function facebookWebHook(Request $request) {
        \Log::critical(json_encode($_REQUEST));
        $challenge = isset($_REQUEST['hub_challenge']) ? $_REQUEST['hub_challenge'] : null;
        $verify_token = isset($_REQUEST['hub_verify_token']) ? $_REQUEST['hub_verify_token'] : null;
        echo $challenge;
        return;
    }

    public function facebookWebHookPost(Request $request, Facebook $fb) {
        $leads = $request->input('entry');
        if ($leads) {
            foreach($leads as $lead) {
                foreach ($lead['changes'] as $leadFields) {
                    $leadForm = $leadFields['value'];
                    $leadId = $leadForm['leadgen_id'];

                    $integrations = DealCampaignFacebookIntegration::where('fb_form_id', $leadForm['form_id'])
                        ->where('fb_page_id', $leadForm['page_id'])->get();
                    if (!$integrations) {
                        \Log::critical('--------------NO FB integration----------------');
                        \Log::critical('Missing FB integration');
                        \Log::critical(print_r($leads, true));
                        \Log::critical('--------------NO FB integration----------------');
                        continue;
                    }

                    foreach ($integrations as $integration) {
                        if (!$integration) {
                            \Log::critical('---------Not found lead form---------');
                            \Log::critical(print_r($leadForm, true));
                            \Log::critical('---------Not found lead form---------');
                            continue;
                        }
                        $accessToken = $integration->fb_page_access_token;
                        $dealCampaign = DealCampaign::where('id', $integration->deal_campaign_id)->firstOrFail();
                        $fbLeadData = null;
                        try {
                            $oAuth2Client = $fb->getOAuth2Client();
                            $accessTokenData = $oAuth2Client->getLongLivedAccessToken($accessToken);

                            $longLiveAccessToken = $accessTokenData->getValue();
                            $integration->fill([
                                'fb_page_access_token' => $longLiveAccessToken,
                                'fb_token_expire_at' => $accessTokenData->getExpiresAt()->getTimestamp(),
                            ]);
                            $integration->save();

                            $leadResponse = $fb->get("/{$leadId}", $accessToken);
                            $fbLeadData = $leadResponse->getBody();
                            $fbLeadData = json_decode($fbLeadData);
                            $fbLeadData = $fbLeadData->field_data;
                        } catch (\Exception $exception) {
                            \Log::critical('Access token for page: '. $integration->fb_page_access_token);
                            \Log::critical('--------------LEAD retrieve ERROR----------------');
                            \Log::critical($exception->getMessage());
                            \Log::critical(print_r($leads, true));
                            \Log::critical('--------------LEAD retrieve ERROR----------------');
                        }

                        if ($fbLeadData) {
                            foreach ($fbLeadData as $field) {
                                $field->name = ($field->name === 'full_name' ? 'fullname' : $field->name);
                                $field->name = ($field->name === 'phone_number' ? 'phone' : $field->name);
                                $request->merge([
                                    $field->name => (is_array($field->values) ? array_first($field->values) : ''),
                                ]);
                            }
                            $request->merge([
                                'metadata' => $fbLeadData,
                            ]);
                            $this->createLead($request, $dealCampaign->uuid);
                        }
                    }
                }
            }
        }

        $challenge = isset($_REQUEST['hub_challenge']) ? $_REQUEST['hub_challenge'] : null;
        $verify_token = isset($_REQUEST['hub_verify_token']) ? $_REQUEST['hub_verify_token'] : null;
        echo $challenge;
        return;
    }

    protected function clickFunnelWebHookAuthorise(Request $request) {
        return [
            'response' => 'success'
        ];
    }
}
