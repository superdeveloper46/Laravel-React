<?php
namespace App\Repositories;

use App\Models\Company;

trait AgencyRepository {

    public function getCompanyBy($id, $withTrashed = false) {
        if ($withTrashed) {
            return $this->companies()->where('company_id', $id)->withTrashed()->first();
        }
        return $this->companies()->where('company_id', $id)->first();
    }

    public function getAgent($id) {
        return $this->agents()->withTrashed()->where('id', $id)->first();
    }

    public function createAgency($data) {
        $data['role'] = Company::$ROLE_AGENCY;
        return $this->createUser($data);
    }
    
    public function getDealBy($dealId) {
        return $this->deals()->where('deals.id', $dealId)->firstOrFail();
    }
}