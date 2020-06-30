<?php

namespace SuperPlatform\UnitedTicket\Models;

/**
 * 「歐博」原生注單
 *
 * @package SuperPlatform\UnitedTicket\Models
 */
class AllBetTicket extends RawTicket
{
    protected $table = 'raw_tickets_all_bet';

    public $incrementing = false;

    protected $fillable = [
        //[PK] 資料識別碼
        'uuid',
        //建立時間
        'created_at',
        //最後更新
        'updated_at',
        //客戶用戶名
        'client',
        // 客戶ID
        'client_id',
        //[UK]注單編號
        'betNum',
        //遊戲局編號
        'gameRoundId',
        //遊戲類型
        'gameType',
        //投注時間
        'betTime',
        //投注金額
        'betAmount',
        //有效投注金額
        'validAmount',
        //輸贏金額
        'winOrLoss',
        //注單狀態(0:正常 1:不正常)
        'state',
        //投注類型
        'betType',
        //開牌結果
        'gameResult',
        //遊戲結束時間
        'gameRoundEndTime',
        //遊戲開始時間
        'gameRoundStartTime',
        //桌台名稱
        'tableName',
        //桌台類型 (100:非免佣 1:免佣)
        'commission'
    ];

    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->client,
            $this->betNum
        ]);
    }
}
