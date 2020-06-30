<?php

namespace SuperPlatform\UnitedTicket\Models;

class CmdSportTicket extends RawTicket
{
    protected $table = 'raw_tickets_cmd_sport';

    protected $fillable = [
        'Id',
        'SourceName',
        'ReferenceNo',
        'SocTransId',
        'IsFirstHalf',
        'TransDate',
        'IsHomeGive',
        'IsBetHome',
        'BetAmount',
        'Outstanding',
        'Hdp',
        'Odds',
        'Currency',
        'WinAmount',
        'ExchangeRate',
        'WinLoseStatus',
        'TransType',
        'DangerStatus',
        'MemCommissionSet',
        'MemCommission',
        'BetIp',
        'HomeScore',
        'AwayScore',
        'RunHomeScore',
        'RunAwayScore',
        'IsRunning',
        'RejectReason',
        'SportType',
        'Choice',
        'WorkingDate',
        'OddsType',
        'MatchDate',
        'HomeTeamId',
        'AwayTeamId',
        'LeagueId',
        'SpecialId',
        'StatusChange',
        'StateUpdateTs',
        'IsCashOut',
        'CashOutTotal',
        'CashOutTakeBack',
        'CashOutWinLoseAmount',
        'BetSource',
        'AOSExcluding',
        'MMRPercent',
        'MatchID',
        'MatchGroupID',
        'BetRemarks',
        'IsSpecial',
    ];

    /**
     * 取得唯一的識別碼
     */
    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->ReferenceNo,
            $this->SourceName,
        ]);
    }
}