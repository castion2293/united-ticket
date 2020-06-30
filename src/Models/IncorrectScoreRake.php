<?php

namespace SuperPlatform\UnitedTicket\Models;

use Illuminate\Database\Eloquent\Model;

class IncorrectScoreRake extends RawTicket
{
    use ReplaceIntoTrait;

    protected $table = 'incorrect_score_rakes';

    /**
     * 取得唯一的識別碼
     */
    public function getUuidAttribute()
    {
        // TODO: Implement getUuidAttribute() method.
    }
}