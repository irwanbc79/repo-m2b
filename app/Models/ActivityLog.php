<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'user_name', 'role', 
        'action', 'module', 'target_ref', 
        'description', 'old_values', 'new_values',
        'ip_address', 'user_agent'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ENHANCED: Record activity with old/new values comparison
     * 
     * @param string $module - Module name (e.g., 'Shipment', 'Customer')
     * @param string $action - Action type (e.g., 'CREATE', 'UPDATE', 'DELETE')
     * @param string $targetRef - Reference ID (e.g., 'IMP-260101-192')
     * @param string|null $description - Human-readable description
     * @param array|null $oldValues - Previous values before change
     * @param array|null $newValues - New values after change
     */
    public static function record($module, $action, $targetRef, $description = null, $oldValues = null, $newValues = null)
    {
        if (Auth::check()) {
            self::create([
                'user_id'     => Auth::id(),
                'user_name'   => Auth::user()->name,
                'role'        => Auth::user()->role,
                'action'      => $action,
                'module'      => $module,
                'target_ref'  => $targetRef,
                'description' => $description,
                'old_values'  => $oldValues,
                'new_values'  => $newValues,
                'ip_address'  => Request::ip(),
                'user_agent'  => Request::userAgent(),
            ]);
        }
    }

    /**
     * Get formatted changes for display
     */
    public function getChangesAttribute()
    {
        if (!$this->old_values || !$this->new_values) {
            return [];
        }

        $changes = [];
        $oldData = is_string($this->old_values) ? json_decode($this->old_values, true) : $this->old_values;
        $newData = is_string($this->new_values) ? json_decode($this->new_values, true) : $this->new_values;

        foreach ($newData as $key => $newValue) {
            $oldValue = $oldData[$key] ?? null;
            if ($oldValue != $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }

    /**
     * Check if log has comparison data
     */
    public function hasComparison()
    {
        return !empty($this->old_values) && !empty($this->new_values);
    }
}
