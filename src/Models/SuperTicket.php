<?php

namespace SuperPlatform\UnitedTicket\Models;

/**
 * 「Super 體彩」原生注單
 *
 * @package SuperPlatform\UnitedTicket\Models
 */
class SuperTicket extends RawTicket
{
    protected $table = 'raw_tickets_super';

    protected $fillable = [
        'm_id',
        'm_name',
        'up_no1',
        'up_no2',
        'sn',
        'matter',
        'gameSN',
        'gsn',
        'm_date',
        'count_date',
        'payout_time',
        'team_no',
        'fashion',
        'g_type',
        'league',
        'gold',
        'bet_gold',
        'sum_gold',
        'result_gold',
        'main_team',
        'visit_team',
        'playing_score',
        'mv_set',
        'mode',
        'chum_num',
        'compensate',
        'status',
        'score1',
        'score2',
        'status_note',
        'end',
        'updated_msg',
        'now',
        'detail',
    ];

    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->m_id,
            $this->sn
        ]);
    }
}
