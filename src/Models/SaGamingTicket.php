<?php

namespace SuperPlatform\UnitedTicket\Models;

/**
 * 「沙龍」原生注單
 *
 * @package SuperPlatform\UnitedTicket\Models
 */
class SaGamingTicket extends RawTicket
{
    protected $table = 'raw_tickets_sa_gaming';

    protected $fillable = [
        'Username',
        'BetID',
        'BetTime',
        'PayoutTime',
        'GameID',
        'HostID',
        'HostName',
        'GameType',
        'Set',
        'Round',
        'BetType',
        'BetAmount',
        'Rolling',
        'Detail',
        'GameResult',
        'ResultAmount',
        'Balance',
        'State',
    ];

    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->Username,
            $this->BetID
        ]);
    }

}
