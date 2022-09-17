<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CompayAgentPivot extends Pivot {
    protected $table = 'company_agents';
}