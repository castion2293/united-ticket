<?php


namespace SuperPlatform\UnitedTicket\Models;

/**
 * @property mixed uuid
 * @property mixed meusername1
 * @property mixed id
 */
class VsLotteryTicket extends RawTicket
{
    protected $table = 'raw_tickets_vs_lottery';

    protected $fillable = [
        'FetchId',
        'TrID',
        'TrDetailID',
        'TrDate',
        'DrawDate',
        'UserName',
        'MarketName',
        'BetType',
        'BetNo',
        'Turnover',
        'CommAmt',
        'NetAmt',
        'WinAmt',
        'Stake',
        'StrikeCount',
        'Odds1',
        'Odds2',
        'Odds3',
        'Odds4',
        'Odds5',
        'CurCode',
        'WinLossStatus',
        'IsPending',
        'IsCancelled',
        'LastChangeDate',
    ];

    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->UserName,
            $this->TrDetailID,
        ]);
    }
}