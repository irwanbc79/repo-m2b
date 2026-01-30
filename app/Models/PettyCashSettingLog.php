<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PettyCashSettingLog extends Model
{
    protected $guarded = [];

    public function fund() { return $this->belongsTo(PettyCashFund::class, 'petty_cash_fund_id'); }
    public function changedBy() { return $this->belongsTo(User::class, 'changed_by'); }
}
