<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Timezones extends Model {
    use SoftDeletes;
    protected $fillable = [
        'name',
        'offset',
        'offset_dst',
    ];

    public static function getTimeZones(string $search = null) {
        $query = self::query();

        if (!empty($search)) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->get();
    }
}
