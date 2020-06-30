<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use BaseTestCase;
use Exception;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\UnitedTicket\Models\RealTimeGamingTicket;

class RealTimeGamingFetcherTest extends BaseTestCase
{
    protected $rawTicketData_1 = [
        "agentId" => "13343b0b-036e-433d-b923-548fb4722d28",
        "agentName" => "Up Gaming",
        "casinoPlayerId" => "ae89d5de-b0f0-494e-8d44-b36f9d82e857",
        "casinoId" => "6c739417-e080-47aa-8a95-4d2fee54bd53",
        "playerName" => "TT8E7D7040",
        "gameDate" => "2019-10-07T05:26:11Z",
        "gameStartDate" => "2019-10-07T05:26:11Z",
        "gameNumber" => 202625,
        "gameName" => "T-Rex II",
        "gameId" => 1179897,
        "bet" => 50.0,
        "win" => 0.0,
        "jpBet" => 0.3022,
        "jpWin" => 0.0,
        "currency" => "TWD",
        "roundId" => "0",
        "balanceStart" => 118.5,
        "balanceEnd" => 68.5,
        "platform" => "Instant Play",
        "externalGameId" => 18,
        "sideBet" => 0.0,
        "id" => 173843630,
        "uuid" => "3fce1eb7-dc6b-3a58-813c-3287c4bfa63d",
    ];

    protected $rawTicketData_2 = [
        "agentId" => "13343b0b-036e-433d-b923-548fb4722d28",
        "agentName" => "Up Gaming",
        "casinoPlayerId" => "ae89d5de-b0f0-494e-8d44-b36f9d82e857",
        "casinoId" => "6c739417-e080-47aa-8a95-4d2fee54bd53",
        "playerName" => "TT8E7D7040",
        "gameDate" => "2019-10-07T05:27:17Z",
        "gameStartDate" => "2019-10-07T05:27:17Z",
        "gameNumber" => 202626,
        "gameName" => "Storm Lords",
        "gameId" => 1179899,
        "bet" => 50.0,
        "win" => 0.0,
        "jpBet" => 0.0,
        "jpWin" => 0.0,
        "currency" => "TWD",
        "roundId" => "0",
        "balanceStart" => 68.5,
        "balanceEnd" => 18.5,
        "platform" => "Instant Play",
        "externalGameId" => 18,
        "sideBet" => 0.0,
        "id" => 173843631,
        "uuid" => "ef5ce389-99f5-3454-98e4-dfe3eebff39a",
    ];

    /**
     * 測試當原生注單注入時，為「聯合鍵資料」產生「主鍵」uuid
     */
    public function testRawTicketUuid(): void
    {
        $ticket = new RealTimeGamingTicket($this->rawTicketData_1);
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
        $fetcher = new RealTimeGamingFetcher();

        try {
            $aTicketsMerged = $fetcher
                ->setTimeSpan(
                    '2019-04-09 09:00:00',
                    '2019-04-09 21:00:00'
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
        $fetcher = new RealTimeGamingFetcher();

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
        $fetcher = new RealTimeGamingFetcher();

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([RealTimeGamingTicket::class, 'replace'], $fetchTickets);

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
     * 測試撈第2張，然後比對，會產出須轉換的是第二張單
     *
     * @test
     */
    public function testFetchSecondTicket()
    {
        // -----------
        //   Arrange
        // -----------
        $fetcher = new RealTimeGamingFetcher();

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([RealTimeGamingTicket::class, 'replace'], $fetchTickets);

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