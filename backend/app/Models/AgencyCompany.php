<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AgencyCompany extends Model {
    protected $table = 'agency_companies';
    protected $fillable = ['agency_id', 'company_id', 'is_locked'];
}