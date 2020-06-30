<?php


namespace SuperPlatform\UnitedTicket\Models;


class HabaneroTicket extends RawTicket
{
    protected $table = 'raw_tickets_habanero';

    protected $fillable = [
        'Username',
        'BrandId',
        'PlayerId',
        'BrandGameId',
        'GameName',
        'GameKeyName',
        'GameInstanceId',
        'FriendlyGameInstanceId',
        'Stake',
        'Payout',
        'JackpotWin',
        'JackpotContribution',
        'GameStateId',
        'GameStateName',
        'GameTypeId',
        'DtStarted',
        'CurrencyCode',
        'ChannelTypeId',
        'DtCompleted',
        'BalanceAfter',
    ];

    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->GameInstanceId,
            $this->Username,
        ]);
    }
}