<?php

namespace App\Http\Controllers\Api\Management;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $fields = [
            'id',
            'name',
            'avatar_path',
            'role',
            'permissions',
            'agencies',
            'email',
            'phone',
            'twilio_sid',
            'twilio_token',
            'twilio_mobile_number'
        ];

        if ($request->user()->isAgency() || $request->user()->isAgent()) {
            return $request->user()->load(['permissions'])->only($fields);
        }

        return $request->user()->load(['permissions', 'agencies'])->only($fields);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $request->user()->handleAvatar($request);
        $data = $request->only([
            'twilio_sid',
            'twilio_token',
            'twilio_mobile_number',
            'name',
            'email',
            'avatar_id',
            'phone',
            'password',
            'password_confirmation'
        ]);

        $user = $request->user()->updateUser($data);

        $request->user()->setupTwilioSmsWebHook(
            $request->get('twilio_sid'),
            $request->get('twilio_token'),
            $request->get('twilio_mobile_number')
        );

        return $user;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        return $request->user()->delete();
    }
}
