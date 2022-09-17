<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{

    protected $table = 'reminders';

    protected $fillable = [
        'agent_id',
        'lead_id',
        'name',
        'time'
    ];

    public function lead() {
        return $this->hasOne('App\Models\Lead', 'id', 'lead_id');
    }
}
