<?php

namespace SuperPlatform\UnitedTicket\Models;


/**
 * 「cq9」原生注單
 *
 * @package SuperPlatform\UnitedTicket\Models
 */
class Cq9GameTicket extends RawTicket
{
    protected $table = 'raw_tickets_cq9_game';

    protected $fillable = [
        'gamehall',
        'gametype',
        'gameplat',
        'gamecode',
        'account',
        'round',
        'balance',
        'win',
        'bet',
        'jackpot',
        'winpc',
        'jackpotcontribution',
        'jackpottype',
        'status',
        'endroundtime',
        'createtime',
        'bettime',
        'detail',
        'singlerowbet',
        'gamerole',
        'bankertype',
        'rake',
    ];

    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->account,
            $this->bettime,
            $this->round,
        ]);
    }
}