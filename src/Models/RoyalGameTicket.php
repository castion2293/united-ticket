<?php

namespace SuperPlatform\UnitedTicket\Models;

/**
 * 「皇家」原生注單
 *
 * @package SuperPlatform\UnitedTicket\Models
 */
class RoyalGameTicket extends RawTicket
{
    protected $table = 'raw_tickets_royal_game';

    protected $fillable = [
        'ID',
        'BucketID',
        'BatchRequestID',
        'BetRequestID',
        'BetScore',
        'BetStatus',
        'BetTime',
        'BucketPublicScore',
        'BucketRebateRate',
        'ClientIP',
        'ClientType',
        'Currency',
        'CurrentScore',
        'DetailURL',
        'ExchangeRate',
        'FinalScore',
        'FundRate',
        'GameDepartmentID',
        'GameItem',
        'GameType',
        'JackpotScore',
        'Member',
        'MemberID',
        'MemberName',
        'NoRun',
        'NoActive',
        'Odds',
        'OriginID',
        'OriginBetRequestID',
        'LastBetRequestID',
        'PortionRate',
        'ProviderID',
        'PublicScore',
        'RebateRate',
        'RewardScore',
        'ServerID',
        'ServerName',
        'SettlementTime',
        'StakeID',
        'StakeName',
        'SubClientType',
        'ValidBetScore',
        'WinScore',
        'TimeInt'
    ];

    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->Member,
            $this->ID,
        ]);
    }
}
