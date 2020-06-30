<?php

use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\UnitedTicket\Fetchers\DreamGameFetcher;
use SuperPlatform\UnitedTicket\Models\DreamGameTicket;

class DreamGameTest extends BaseTestCase
{
    protected $rawTicketData_1 = [
        // 注单唯一Id
        "id" => 3353574436,
        // 游戏桌号
        "tableId" => 10103,
        // 游戏靴号
        "shoeId" => 15,
        // 游戏局号
        "playId" => 13,
        // 游戏大厅号 1:旗舰厅, 2:竞咪厅, 3:现场厅, 4:波贝厅
        "lobbyId" => 1,
        // 游戏类型
        "gameType" => 1,
        // 游戏Id
        "gameId" => 1,
        // 会员Id
        "memberId" => 16903950,
        // parentId
        "parentId" => 7635,
        // 游戏下注时间
        "betTime" => "2019-10-05 09:54:33",
        // 游戏结算时间
        "calTime" => "2019-10-05 09:54:42",
        // 派彩金额 (输赢应扣除下注金额)
        "winOrLoss" => 0.0,
        // balanceBefore
        "balanceBefore" => 394.0,
        // 下注金额
        "betPoints" => 100.0,
        // 好路追注金额
        "betPointsz" => 0.0,
        // 有效下注金额
        "availableBet" => 100.0,
        // 会员登入账号
        "userName" => "TT8E7D7040",
        // 游戏结果
        "result" => '{\"result\":\"5,2,6\",\"poker\":{\"banker\":\"31-13-0\",\"player\":\"38-16-3\"}}',
        // 下注注单
        "betDetail" => '{\"banker\":100.0}',
        // 下注时客户端IP
        "ip" => "139.162.37.106",
        // 游戏唯一ID
        "ext" => "191005B030731",
        // 是否结算：0:未结算, 1:已结算, 2:已撤销(该注单为对冲注单)
        "isRevocation" => 1,
        // 货币ID
        "currencyId" => 8,
        // 下注时客户端类型
        "deviceType" => 5,
        // roadid
        "roadid" => 0,

        "uuid" => "be9b2d5f-b1a9-358b-8569-e7361d012b9c"
    ];

    /**
     * 測試當原生注單注入時，為「聯合鍵資料」產生「主鍵」uuid
     *
     * @test
     */
    public function testRawTicketUuid()
    {
        // -----------
        //   Act
        // -----------
        $ticket = new DreamGameTicket($this->rawTicketData_1);
        $datas = $ticket->toArray();
        $datas['uuid'] = $ticket->uuid;
        // -----------
        //   Assert
        // -----------
        $v3Regex = '/^[0-9a-f]{8}-[0-9a-f]{4}-[3][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        $isUUID3 = preg_match($v3Regex, $ticket->uuid);

        $this->assertArrayHasKey('uuid', $datas);
        $this->assertEquals(1, $isUUID3);
    }

    /**
     * 測試呼叫 API 成功
     *
     * @test
     */
    public function testSuccessCapture()
    {
        // -----------
        //   Act
        // -----------
        $fetcher = new DreamGameFetcher([]);
        try {
            $response = $fetcher->capture();
            $this->assertArrayHasKey('tickets', $response);
        } catch (ApiCallerException $exc) {
            // -----------
            //   Assert
            // -----------
            $this->assertInstanceOf('SuperPlatform\ApiCaller\Exceptions\ApiCallerException', $exc);
            $this->assertEquals('Api caller receive failure response, use `$exception->response()` get more details.', $exc->getMessage());
            $this->assertEquals(true, is_array($exc->response()));
        }
    }

    /**
     * 測試撈第一張，然後比對
     *
     * @test
     */
    public function testFetchFirstTicket()
    {
        // -----------
        //   Arrange
        // -----------
        $fetcher = new DreamGameFetcher([]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        // -----------
        //   Act
        // -----------
        $tickets = $fetcher->compare($fetchTickets);

        // -----------
        //   Assert
        // -----------
        $this->assertEquals(1, count(array_get($tickets, 'tickets')));
    }
}