<?php

namespace SuperPlatform\UnitedTicket\Models;

class RenNiYingTicket extends RawTicket
{
    protected $table = 'raw_tickets_ren_ni_ying';

    protected $fillable = [
        'id',
        'status',
        'userId',
        'created',
        'gameId',
        'roundId',
        'place',
        'guess',
        'odds',
        'money',
        'result',
        'playerRebate',
    ];

    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->id,
            $this->userId,
            $this->created,
            $this->roundId
        ]);
    }
}