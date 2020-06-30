<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use BaseTestCase;
use Exception;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\UnitedTicket\Models\RoyalGameTicket;

class RoyalGameFetcherTest extends BaseTestCase
{
    protected $rawTicketData_1 = [
        "ID" => 384486643,
        "BatchRequestID" => null,
        "BetRequestID" => "caa5d4991343445cac3df13587a7b085",
        "OriginID" => 0,
        "ProviderID" => "Royal",
        "BucketID" => "super",
        "MemberID" => "TT02F73CC4",
        "Member" => "TT02F73CC4",
        "MemberName" => "TT02F73CC4",
        "ClientType" => 3,
        "SubClientType" => "s",
        "ClientIP" => "45.76.208.158",
        "NoRun" => "020924001",
        "NoActive" => "0003",
        "ServerID" => "0604250006",
        "GameItem" => "Bacc",
        "ServerName" => "BaccB",
        "GameType" => 1,
        "GameDepartmentID" => "RoyalGclub",
        "StakeID" => "06042500060002",
        "StakeName" => "Player",
        "Odds" => 2,
        "BetScore" => 1,
        "ValidBetScore" => 1,
        "WinScore" => 1,
        "FinalScore" => 1,
        "BucketRebateRate" => 0,
        "RebateRate" => 0,
        "JackpotScore" => 0,
        "PortionRate" => 0,
        "BucketPublicScore" => 0,
        "PublicScore" => 0,
        "FundRate" => 0,
        "RewardScore" => 0,
        "CurrentScore" => 298,
        "BetTime" => "2019-09-25 11:11:44",
        "SettlementTime" => "2019-09-25 11:26:39",
        "Currency" => "NT",
        "ExchangeRate" => 1,
        "BetStatus" => 4,
        "DetailURL" => "http://h1-game.rg-show.com/OpenDetail?ServerID=0604250006&NoRun=020924001&NoActive=0003",
        "LastBetRequestID" => null,
        "OriginBetRequestID" => null,
        "uuid" => "1a73dc55-1d59-3fd1-9017-fc137a1bf538",
    ];

    protected $rawTicketData_2 = [
        "ID" => 384492059,
        "BatchRequestID" => null,
        "BetRequestID" => "5efeb0da55a44c95b2f0554fcb61cb33",
        "OriginID" => 0,
        "ProviderID" => "Royal",
        "BucketID" => "super",
        "MemberID" => "TT8E7D7040",
        "Member" => "TT8E7D7040",
        "MemberName" => "TT8E7D7040",
        "ClientType" => 3,
        "SubClientType" => "s",
        "ClientIP" => "45.76.208.158",
        "NoRun" => "20191009001",
        "NoActive" => "0006",
        "ServerID" => "1201010010",
        "GameItem" => "Bacc",
        "ServerName" => "BaccT",
        "GameType" => 1,
        "GameDepartmentID" => "RoyalGclub",
        "StakeID" => "12010100100001",
        "StakeName" => "Banker",
        "Odds" => 1.95,
        "BetScore" => 1,
        "ValidBetScore" => 1,
        "WinScore" => -1,
        "FinalScore" => -1,
        "BucketRebateRate" => 0,
        "RebateRate" => 0,
        "JackpotScore" => 0,
        "PortionRate" => 0,
        "BucketPublicScore" => 0,
        "PublicScore" => 0,
        "FundRate" => 0,
        "RewardScore" => 0,
        "CurrentScore" => 1059,
        "BetTime" => "2019-10-09 14:18:58",
        "SettlementTime" => "2019-10-09 14:19:18",
        "Currency" => "NT",
        "ExchangeRate" => 1,
        "BetStatus" => 4,
        "DetailURL" => "http://h1-game.rg-show.com/OpenDetail?ServerID=1201010010&NoRun=20191009001&NoActive=0006",
        "LastBetRequestID" => null,
        "OriginBetRequestID" => null,
        "uuid" => "d757f48e-7370-3152-8394-85a76fc28d72",
    ];

    /**
     * 測試當原生注單注入時，為「聯合鍵資料」產生「主鍵」uuid
     */
    public function testRawTicketUuid(): void
    {
        $ticket = new RoyalGameTicket($this->rawTicketData_1);
        $datas = $ticket->toArray();
        $datas['uuid'] = $ticket->uuid;

        $v3Regex = '/^[0-9a-f]{8}-[0-9a-f]{4}-[3][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        $isUUID3 = preg_match($v3Regex, $ticket->uuid);

        $this->assertArrayHasKey('uuid', $datas);
        $this->assertEquals(1, $isUUID3);
    }

    /**
     * @throws Exception
     */
    public function testSuccessCapture(): void
    {
        $fetcher = new RoyalGameFetcher();

        try {
            $aTicketsMerged = $fetcher
                ->setTimeSpan(
                    '2019-05-21 09:00:00',
                    '2019-05-21 21:00:00'
                )->capture();

            $this->assertArrayHasKey('tickets', $aTicketsMerged);
        } catch (ApiCallerException $exc) {
            $this->assertInstanceOf('SuperPlatform\ApiCaller\Exceptions\ApiCallerException', $exc);
            $this->assertEquals('Api caller receive failure response, use `$exception->response()` get more details.',
                $exc->getMessage());
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
        $fetcher = new RoyalGameFetcher();

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

    /**
     * 測試撈同樣第一張，然後比對會找出同樣的結果，所以不會產出需要轉換的注單
     *
     * @test
     */
    public function testFetchSameFirstTicket()
    {
        // -----------
        //   Arrange
        // -----------
        $fetcher = new RoyalGameFetcher();

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([RoyalGameTicket::class, 'replace'], $fetchTickets);

        // -----------
        //   Act
        // -----------
        $tickets = $fetcher->compare($fetchTickets);

        // -----------
        //   Assert
        // -----------
        $this->assertEquals(0, count(array_get($tickets, 'tickets')));
    }

    /**
     * 測試撈同樣第一張，然後比對會找出不一樣的結果，所以會產出需要轉換的注單
     *
     * @test
     */
    public function testFetchSameFirstTicketWithDifferentState()
    {
        // -----------
        //   Arrange
        // -----------
        $fetcher = new RoyalGameFetcher();

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([RoyalGameTicket::class, 'replace'], $fetchTickets);

        // 轉換狀態
        $this->rawTicketData_1['BetStatus'] = 3;

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

    /**
     * 測試撈第2張，然後比對，會產出須轉換的是第二張單
     *
     * @test
     */
    public function testFetchSecondTicket()
    {
        // -----------
        //   Arrange
        // -----------
        $fetcher = new RoyalGameFetcher();

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([RoyalGameTicket::class, 'replace'], $fetchTickets);

        // 出現第二張單
        $fetchTickets = [
            $this->rawTicketData_1,
            $this->rawTicketData_2
        ];

        // -----------
        //   Act
        // -----------
        $tickets = $fetcher->compare($fetchTickets);

        // -----------
        //   Assert
        // -----------
        $ticket = array_get($tickets, 'tickets');
        $this->assertEquals(1, count($ticket));
        $this->assertEquals($this->rawTicketData_2['uuid'], array_first($ticket)['uuid']);
    }
}