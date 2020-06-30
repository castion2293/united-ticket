<?php

namespace SuperPlatform\UnitedTicket\Models;

class QTechTicket extends RawTicket
{
    protected $table = 'raw_tickets_q_tech';

    protected $fillable = [
        'id',
        'status',
        'totalBet',
        'totalPayout',
        'totalBonusBet',
        'currency',
        'initiated',
        'completed',
        'playerId',
        'operatorId',
        'device',
        'gameProvider',
        'gameId',
        'gameCategory',
        'gameClientType',
        'bonusType',
    ];

    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->id,
            $this->playerId,
            $this->initiated,
        ]);
    }
}