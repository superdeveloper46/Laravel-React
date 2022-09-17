<?php

namespace App\Http\Controllers\Api\Management\Admin;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $itemsPerPage = (int)$request->get('per_page', 10);
        $page = (int)$request->get('current_page', 1);
        return User::getAll($request->only([
            'search',
            'name',
            'email',
            'role',
            'phone',
            'showDeleted',
        ]))->paginate($itemsPerPage, ['*'], 'users', $page);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Agency $user)
    {
        $user->handleAvatar($request);
        $request->merge(['role' => 'AGENCY']);
        $user->createAgency($request->only([
            'name',
            'subscription_type',
            'max_agency_companies',
            'avatar_id',
            'phone',
            'email',
            'password',
            'password_confirmation']));

        if ($request->get('role') === User::$ROLE_AGENCY) {
            $user->companies()->attach($user);
        }

        return $user;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return User::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->handleAvatar($request);
        return $user->updateUser($request->except(['role']));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
    }
}
