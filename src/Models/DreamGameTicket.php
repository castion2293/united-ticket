<?php

namespace SuperPlatform\UnitedTicket\Models;

/**
 * 「DG」原生注單
 *
 * @package SuperPlatform\UnitedTicket\Models
 */
class DreamGameTicket extends RawTicket
{
    protected $table = 'raw_tickets_dream_game';

    public $incrementing = false;

    protected $fillable = [
        //[PK] 資料識別碼
        'uuid',
        //建立時間
        'created_at',
        //最後更新
        'updated_at',
        //注单唯一Id
        'id',
        //游戏大厅号 1:旗舰厅, 2:竞咪厅, 3:现场厅, 4:波贝厅
        'lobbyId',
        //游戏桌号
        'tableId',
        //游戏靴号
        'shoeId',
        //游戏局号
        'playId',
        //游戏类型
        'gameType',
        //游戏Id
        'gameId',
        //会员Id
        'memberId',
        //parentId
        'parentId',
        //游戏下注时间
        'betTime',
        //游戏结算时间
        'calTime',
        //派彩金额 (输赢应扣除下注金额)
        'winOrLoss',
        //好路追注派彩金额
        'winOrLossz',
        //balanceBefore
        'balanceBefore',
        //下注金额
        'betPoints',
        //好路追注金额
        'betPointsz',
        //有效下注金额
        'availableBet',
        //会员登入账号
        'userName',
        //游戏结果
        'result',
        //下注注单
        'betDetail',
        //好路追注注单
        'betDetailz',
        //下注时客户端IP
        'ip',
        //游戏唯一ID
        'ext',
        //是否结算：0:未结算, 1:已结算, 2:已撤销(该注单为对冲注单)
        'isRevocation',
        //撤销的那比注单的ID
        'parentBetId',
        //货币ID
        'currencyId',
        //下注时客户端类型
        'deviceType',
        //roadid
        'roadid',
        //追注转账流水号
        'pluginid'
    ];

    public function getUuidAttribute()
    {
        return $this->uniqueToUuid([
            $this->userName,
            $this->id
        ]);
    }
}