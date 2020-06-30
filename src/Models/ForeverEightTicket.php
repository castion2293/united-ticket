<?php


namespace SuperPlatform\UnitedTicket\Models;


class ForeverEightTicket extends RawTicket
{
    protected $table = 'raw_tickets_forever_eight';

    protected $fillable = [
        'BillNo',
        'GameID',
        'BetValue',
        'NetAmount',
        'SettleTime',
        'AgentsCode',
        'Account',
        'TicketStatus',
    ];

    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->BillNo,
            $this->Account,
        ]);
    }
}