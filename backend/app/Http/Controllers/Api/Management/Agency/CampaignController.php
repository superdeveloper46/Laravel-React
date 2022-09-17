<?php

namespace App\Http\Controllers\Api\Management\Agency;

use App\Models\DealCampaign;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Faker\Generator as Faker;
use Mockery\Exception;

class CampaignController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $company, $deal)
    {
        $itemsPerPage = (int)$request->get('per_page', 10);
        $page = (int)$request->get('current_page', 1);

        $deallist = $request->user()->getCompanyBy($company)->getDealBy($deal);
        if($deallist =='' || $deallist == null)
            return $request->merge([
                'current_page' => 1,
                'data' => '',
                'first_page_url' => '',
                'from'=> 1,
                'last_page'=> 1,
                'last_page_url'=> "",
                'next_page_url'=> null,
                'path'=> "",
                'per_page'=> 10,
                'prev_page_url'=> null,
                'to'=> 10,
                'total'=> 10
            ]);
        else
            return $request
            ->user()
            ->getCompanyBy($company)
            ->getDealBy($deal)
            ->getCampaignsBy($request->only([
                'showDeleted',
                'name',
                'type',
                'leads',
                'avg_time_response',
            ]))
            ->paginate($itemsPerPage, ['*'], 'campaigns', $page);
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Faker $faker, $company, $deal)
    {
        $request->merge([
            'uuid' => $faker->uuid,
            'deal_id' => $deal,
            'agency_company_id' => $request->user()->getCompanyBy($company)->pivot->id,
        ]);
        
        return DealCampaign::createCampaign($request);
    }
    
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $company, $deal, $id)
    {
        $deal = $request->user()->getCompanyBy($company)->getDealBy($deal);
        return $deal->getCampaignBy($id);
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $company, $deal, $id)
    {
        $campaign = $request->user()->getCompanyBy($company)->getDealBy($deal)->getCampaignBy($id);
        $campaign->updateCampaign($request);
        return $campaign;
    }
    
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $company, $deal, $id)
    {
        $campaign = $request
            ->user()
            ->getCompanyBy($company)
            ->getDealBy($deal)
            ->campaigns()->withTrashed()->where('id', $id)->first();
             
        if($campaign->trashed()) {
            $campaign->restore();
        } else {
            $campaign->delete();
        }

        return $campaign;
    }
}
