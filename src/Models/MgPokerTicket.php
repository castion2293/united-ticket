<?php


namespace SuperPlatform\UnitedTicket\Models;

/**
 * 「MG棋牌」原生注單
 *
 * @package SuperPlatform\UnitedTicket\Models
 */

class MgPokerTicket extends RawTicket
{
    protected $table = 'raw_tickets_mg_poker';

    protected $fillable = [
        'total',
        'bets',
        'gameId',
        'account',
        'accountId',
        'platform',
        'roundId',
        'fieldId',
        'filedName',
        'tableId',
        'chair',
        'bet',
        'validBet',
        'win',
        'lose',
        'fee',
        'enterMoney',
        'createTime',
        'roundBeginTime',
        'roundEndTime',
        'ip',
    ];

    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->account,
            $this->roundId
        ]);
    }
}