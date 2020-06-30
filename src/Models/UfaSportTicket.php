<?php

namespace SuperPlatform\UnitedTicket\Models;

/**
 * 「UFA 體育」原生注單
 *
 * @package SuperPlatform\UnitedTicket\Models
 */
class UfaSportTicket extends RawTicket
{
    protected $table = 'raw_tickets_ufa_sport';

    protected $fillable = [
        'fid',
        'id',
        't',
        'u',
        'b',
        'w',
        'a',
        'c',
        'ip',
        'league',
        'home',
        'away',
        'status',
        'game',
        'odds',
        'side',
        'info',
        'half',
        'trandate',
        'workdate',
        'matchdate',
        'runscore',
        'score',
        'htscore',
        'fig',
        'res',
        'sportstype',
        'oddstype',
    ];

    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->trandate,
            $this->u,
            $this->b,
        ]);
    }
}
