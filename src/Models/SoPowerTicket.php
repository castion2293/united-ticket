<?php

namespace SuperPlatform\UnitedTicket\Models;

/**
 * 「手中寶」原生注單
 *
 * @package SuperPlatform\UnitedTicket\Models
 */
class SoPowerTicket extends RawTicket
{
    protected $table = 'raw_tickets_so_power';

    protected $fillable = [
        'USERNAME',
        'GTYPE',
        'BETID',
        'RTYPE',
        'GOLD',
        'IORATIO',
        'RESULT',
        'ADDDATE',
        'WINGOLD',
        'WGOLD_DM',
        'ORDERIP',
        'BETCONTENT',
        'PERIODNUMBER',
        'BETDETAIL',
        'RESULT_OK',
    ];

    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->USERNAME,
            $this->BETID,
            $this->ADDDATE,
        ]);
    }
}
