<?php

namespace App\Http\Controllers\Api\Management\Agent;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CompanyController extends Controller
{
    public function companies(Request $request) {
        $itemsPerPage = (int)$request->get('per_page', 10);
        $page = (int)$request->get('current_page', 1);
        return $request
            ->user()
            ->companies()->with('campaigns')
            ->paginate($itemsPerPage, ['*'], 'page', $page);
    }
}
