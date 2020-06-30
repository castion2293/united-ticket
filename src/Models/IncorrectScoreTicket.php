<?php


namespace SuperPlatform\UnitedTicket\Models;


class IncorrectScoreTicket extends RawTicket
{
    protected $table = 'raw_tickets_incorrect_score';

    protected $fillable = [
        'ticketNo',
        'user',
        'sportType',
        'orderTime',
        'betTime',
        'cancelTime',
        'betamount',
        'validBetAmount',
        'handlingFee',
        'currency',
        'winlose',
        'isFinished',
        'statusType',
        'wagerGrpId',
        'betIp',
        'cType',
        'device',
        'accdate',
        'acctId',
        'refNo',
        'evtid',
        'league',
        'match',
        'betOption',
        'hdp',
        'odds',
        'oddsDesc',
        'winlostTime',
        'scheduleTime',
        'ftScore',
        'curScore',
        'wagerTypeID',
        'cutline',
        'odddesc',
        'profit',
        'realAmount',
    ];

    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->user,
            $this->ticketNo
        ]);
    }
}