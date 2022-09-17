<?php

namespace App\Http\Controllers\Api\Management;

use App\Models\Timezones;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TimezonesController extends Controller
{
    public function all(Request $request) {
        return Timezones::getTimeZones($request->get('search', null));
    }
}
