<?php

namespace SuperPlatform\UnitedTicket\Models;

/**
 * 「賓果」原生注單
 *
 * @package SuperPlatform\UnitedTicket\Models
 */
class BingoTicket extends RawTicket
{
    protected $table = 'raw_tickets_bingo';

    protected $fillable = [
        'account',
        'serial_no',
        'bingo_no',
        'bet_suit',
        'bet_type_group',
        'numbers',
        'bet',
        'odds',
        'real_bet',
        'real_rebate',
        'bingo_type',
        'bingo_odds',
        'result',
        'status',
        'win_lose',
        'remark',
        'bet_at',
        'adjust_at',
        'root_serial_no',
        'root_created_at',
        'duplicated',
        'player',
        'results',
        'history'
    ];

    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->account,
            $this->serial_no
        ]);
    }

}
