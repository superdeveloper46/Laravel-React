<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

class Device extends Model
{
    use SoftDeletes;

    protected $table = 'devices';

    protected $fillable = [
        'agent_id',
        'device_token',
        'type',
    ];

    public function agent() {
        return $this->belongsTo('App\Models\Agent', 'id', 'agent_id');
    }

    public static function createDeviceToken(Request $request) {
        try {
            \DB::beginTransaction();

            $request->merge([
                'agent_id' => $request->user()->id,
            ]);

            \Validator::validate($request->all(), [
                'device_token' => 'required|string|max:255',
                'type' => 'required|string|max:255',
                'agent_id' => 'required|int',
            ]);

            $deviceToken = Device::where('agent_id', $request->agent_id)
                ->where('device_token', $request->device_token)
                ->where('type', $request->type)
                ->first();

            if (!$deviceToken) {
                $deviceToken = Device::create($request->only([
                    'agent_id',
                    'device_token',
                    'type',
                ]));
            }

            \DB::commit();
            return $deviceToken;
        } catch (\Exception $exception) {
            \DB::rollBack();
            throw $exception;
        }
    }

    public static function getTokenListFromAgentIds($agentIds=[]) {
        $tokenList = [];
        $devices = Device::whereIn('agent_id', $agentIds)->get();
        foreach ($devices as $device) {
            $tokenList[] = $device->device_token;
        }
        return $tokenList;
    }
}
