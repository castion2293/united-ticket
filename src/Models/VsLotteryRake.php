<?php

namespace SuperPlatform\UnitedTicket\Models;

use Illuminate\Database\Eloquent\Model;

class VsLotteryRake extends RawTicket
{
    use ReplaceIntoTrait;

    protected $table = 'vs_lottery_rakes';

    /**
     * 取得唯一的識別碼
     */
    public function getUuidAttribute()
    {
        // TODO: Implement getUuidAttribute() method.
    }
}