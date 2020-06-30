<?php

namespace SuperPlatform\UnitedTicket\Models;

/**
 * 「Lottery 彩球」原生注單
 *
 * @package SuperPlatform\UnitedTicket\Models
 */
class SuperLotteryTicket extends RawTicket
{
    protected $table = 'raw_tickets_super_lottery';

    protected $fillable = [
        'state',
        'name',
        'lottery',
        'bet_no',
        'bet_time',
        'count_date',
        'account',
        'game_id',
        'game_type',
        'bet_type',
        'detail',
        'cmount',
        'gold',
        'odds',
        'retake',
        'status'
    ];

    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->bet_no,
            $this->bet_time,
            $this->account,
            $this->detail,
            $this->game_type,
            $this->cmount
        ]);
    }
}