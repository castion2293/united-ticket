<?php

namespace SuperPlatform\UnitedTicket\Models;

/**
 * @property mixed uuid
 * @property mixed meusername1
 * @property mixed id
 */
class WinnerSportTicket extends RawTicket
{
    protected $table = 'raw_tickets_winner_sport';

    protected $fillable = [
        'id',
        'mrid',
        'pr',
        'status',
        'stats',
        'mark',
        'meid',
        'meusername',
        'meusername1',
        'gold',
        'gold_c',
        'io',
        'result',
        'meresult',
        'gtype',
        'rtype',
        'g_title',
        'r_title',
        'l_sname',
        'orderdate',
        'IP',
        'added_date',
        'modified_date',
        'detail_1',
        'detail',
    ];

    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->meusername1,
            $this->id
        ]);
    }
}