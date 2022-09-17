<?php
namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class DealAction
 *
 * @package App\Models
 *
 * @property int lead_id
 * @property int deal_action_id
 * @property bool is_completed
 * @property Carbon created_at
 * @property Carbon updated_at
 */
class LeadActionHistory extends Model {
    use SoftDeletes;

    protected $fillable = [
        'lead_id',
        'deal_action_id',
        'is_completed',
    ];

    public function moveToCompleted() {
        $this->is_completed = 1;
        $this->save();
    }
}
