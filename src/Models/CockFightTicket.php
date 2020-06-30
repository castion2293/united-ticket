<?php

namespace SuperPlatform\UnitedTicket\Models;

class CockFightTicket extends RawTicket
{
    protected $table = 'raw_tickets_cock_fight';

    protected $fillable = [
        'ticket_id',
        'login_id',
        'arena_code',
        'arena_name_cn',
        'match_no',
        'match_type',
        'match_date',
        'fight_no',
        'fight_datetime',
        'meron_cock',
        'meron_cock_cn',
        'wala_cock',
        'wala_cock_cn',
        'bet_on',
        'odds_type',
        'odds_asked',
        'odds_given',
        'stake',
        'stake_money',
        'balance_open',
        'balance_close',
        'created_datetime',
        'fight_result',
        'status',
        'winloss',
        'comm_earned',
        'payout',
        'balance_open1',
        'balance_close1',
        'processed_datetime'
    ];

    /**
     * 取得唯一的識別碼
     */
    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->ticket_id,
            $this->login_id,
            $this->created_datetime,
        ]);
    }
}