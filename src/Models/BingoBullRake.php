<?php

namespace SuperPlatform\UnitedTicket\Models;

use Illuminate\Database\Eloquent\Model;

class BingoBullRake extends RawTicket
{
    use ReplaceIntoTrait;

    protected $table = 'bingo_bull_rakes';

    /**
     * 取得唯一的識別碼
     */
    public function getUuidAttribute()
    {
        // TODO: Implement getUuidAttribute() method.
    }
}