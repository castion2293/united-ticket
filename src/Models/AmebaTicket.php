<?php

namespace SuperPlatform\UnitedTicket\Models;

class AmebaTicket extends RawTicket
{
    protected $table = 'raw_tickets_ameba';

    protected $fillable = [
        'account_name',
        'currency',
        'game_id',
        'round_id',
        'free',
        'bet_amt',
        'payout_amt',
        'completed_at',
        'rebate_amt',
        'jp_pc_con_amt',
        'jp_jc_con_amt',
        'jp_win_id',
        'jp_pc_win_amt',
        'jp_jc_win_amt',
        'jp_win_lv',
        'jp_direct_pay',
        'prize_type',
        'prize_amt',
        'site_id',
    ];

    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->account_name,
            $this->round_id
        ]);
    }
}