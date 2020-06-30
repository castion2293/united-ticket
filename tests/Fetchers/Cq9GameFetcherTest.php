<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use BaseTestCase;
use Exception;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\UnitedTicket\Fetchers\Cq9GameFetcher;
use SuperPlatform\UnitedTicket\Models\Cq9GameTicket;

class Cq9GameFetcherTest extends BaseTestCase
{
    protected $rawTicketData_1 = [
        'gamehall' => 'cq9',
        "gametype" => "slot",
        "gameplat" => "web",
        "gamecode" => "64",
        "account" => "TT8E7D7040",
        "round" => "2480730477",
        "balance" => 17717.8,
        "win" => 11764,
        "bet" => 17.6,
        "jackpot" => 0,
        "jackpotcontribution" => "[]",
        "jackpottype" => "",
        "status" => "complete",
        "endroundtime" => "2019-10-04T03:29:18.622-04:00",
        "createtime" => "2019-10-04T03:29:18-04:00",
        "bettime" => "2019-10-04T03:29:02-04:00",
        "detail" => '[{"freegame":8},{"luckydraw":0},{"bonus":0}]',
        "singlerowbet" => false,
        "gamerole" => "",
        "bankertype" => "",
        "rake" => 0,
        "uuid" => "b1267e28-ad8c-37f9-b823-2206510bb0b3"
    ];

    protected $rawTicketData_2 = [
        'gamehall' => 'cq9',
        "gametype" => "slot",
        "gameplat" => "web",
        "gamecode" => "64",
        "account" => "TT8E7D7040",
        "round" => "2480730303",
        "balance" => 5971.4,
        "win" => 5400,
        "bet" => 17.6,
        "jackpot" => 0,
        "jackpotcontribution" => "[]",
        "jackpottype" => "",
        "status" => "complete",
        "endroundtime" => "2019-10-04T03:29:01.974-04:00",
        "createtime" => "2019-10-04T03:29:01-04:00",
        "bettime" => "2019-10-04T03:28:45-04:00",
        "detail" => '[{"freegame":8},{"luckydraw":0},{"bonus":0}]',
        "singlerowbet" => false,
        "gamerole" => "",
        "bankertype" => "",
        "rake" => 0,
        "uuid" => "92b8d5a5-d2fb-350a-8bc0-6b73917eca37"
    ];

    /**
     * 測試當原生注單注入時，為「聯合鍵資料」產生「主鍵」uuid
     */
    public function testRawTicketUuid(): void
    {
        $ticket = new Cq9GameTicket($this->rawTicketData_1);
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
        $oFetcher = new Cq9GameFetcher([]);

        try {
            $aTicketsMerged = $oFetcher
                ->setTimeSpan(
                    '2019-06-11 00:00:00',
                    '2019-06-11 23:59:59'
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
        $fetcher = new Cq9GameFetcher([]);

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
     * 測試撈同樣第一張，然後比對會找出不一樣的結果，所以會產出需要轉換的注單
     *
     * @test
     */
    public function testFetchSameFirstTicketWithDifferentState()
    {
        // -----------
        //   Arrange
        // -----------
        $fetcher = new Cq9GameFetcher([]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([Cq9GameTicket::class, 'replace'], $fetchTickets);

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
        $fetcher = new Cq9GameFetcher([]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([Cq9GameTicket::class, 'replace'], $fetchTickets);

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