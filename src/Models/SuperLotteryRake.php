<?php

namespace SuperPlatform\UnitedTicket\Models;

use Illuminate\Database\Eloquent\Model;

class SuperLotteryRake extends RawTicket
{
    use ReplaceIntoTrait;

    protected $table = 'super_lottery_rakes';

    protected $fillable = [
        'level',
        'user_identify',
        'account',
        'bet_date',
        'game_scope',
        'category',
        'ccount',
        'cmount',
        'bmount',
        'm_gold',
        'm_rake',
        'm_result',
        'up_no1_result',
        'up_no2_result',
        'up_no1_rake',
        'up_no2_rake',
        'up_no1',
        'up_no2',
    ];

    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->account,
            $this->bet_date,
            $this->game_scope,
        ]);
    }
}