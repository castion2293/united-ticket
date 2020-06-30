<?php

namespace SuperPlatform\UnitedTicket\Models;

class KkLotteryTicket extends RawTicket
{
    protected $table = 'raw_tickets_kk_lottery';

    protected $fillable = [
        'bet_id',
        'platform_id',
        'user_id',
        'user_name',
        'issue_code',
        'issue_seq',
        'lottery_id',
        'lottery_enname',
        'lottery_name',
        'method_id',
        'method_name',
        'modes',
        'method_code',
        'bet_count',
        'single_price',
        'multiple',
        'total_money',
        'user_bonus_group',
        'win_price',
        'winning_status',
        'cancel_status',
        'open_lottery_status',
        'bet_number',
        'issue_winning_code',
        'create_time',
        'modify_time',
    ];

    /**
     * 取得唯一的識別碼
     */
    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->bet_id,
            $this->user_name,
        ]);
    }
}