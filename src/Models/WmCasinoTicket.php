<?php


namespace SuperPlatform\UnitedTicket\Models;

/**
 * 「WM真人」原生注單
 *
 * @package SuperPlatform\UnitedTicket\Models
 */

class WmCasinoTicket extends RawTicket
{
    protected $table = 'raw_tickets_wm_casino';

    protected $fillable = [
        'user',
        'betId',
        'betTime',
        'beforeCash',
        'bet',
        'validbet',
        'water',
        'result',
        'betResult',
        'waterbet',
        'winLoss',
        'ip',
        'gid',
        'event',
        'round',
        'eventChild',
        'subround',
        'tableId',
        'gameResult',
        'gname',
        'commission',
        'reset',
        'settime',
    ];

    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->betId,
            $this->user,
        ]);
    }
}