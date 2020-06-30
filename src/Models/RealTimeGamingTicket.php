<?php

namespace SuperPlatform\UnitedTicket\Models;


/**
 * 「RTG」原生注單
 *
 * @package SuperPlatform\UnitedTicket\Models
 */
class RealTimeGamingTicket extends RawTicket
{
    protected $table = 'raw_tickets_real_time_gaming';

    protected $fillable = [
        'agentId',
        'agentName',
        'casinoPlayerId',
        'casinoId',
        'playerName',
        'gameDate',
        'gameStartDate',
        'gameNumber',
        'gameName',
        'gameId',
        'bet',
        'win',
        'jpBet',
        'jpWin',
        'currency',
        'roundId',
        'balanceStart',
        'balanceEnd',
        'platform',
        'externalGameId',
        'sideBet',
        'jpType',
        'jackpotBet',
        'id',
    ];

    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->playerName,
            $this->id,
        ]);
    }
}