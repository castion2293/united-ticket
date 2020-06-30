<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use BaseTestCase;
use SuperPlatform\UnitedTicket\Fetchers\UfaSportFetcher;
use SuperPlatform\UnitedTicket\Models\UfaSportTicket;

class UfaSportFetcherTest extends BaseTestCase
{
    protected $rawTicketData_1 = [
        "fid" => 17389119,
        "id" => "OU7749833266",
        "t" => "2019-10-08 17:04:21.547",
        "u" => "TT8E7D7040",
        "b" => 10,
        "w" => 0,
        "a" => 0,
        "c" => 0,
        "ip" => "45.76.208.158",
        "league" => 3544,
        "home" => 98547,
        "away" => 27192,
        "status" => "A",
        "game" => "OU",
        "odds" => -0.98,
        "side" => "1",
        "info" => "4.75",
        "half" => 0,
        "trandate" => "2019-10-08 17:04:18",
        "workdate" => "2019-10-08 00:00:00",
        "matchdate" => "10/08 17:00",
        "runscore" => "1-0",
        "score" => "[]",
        "htscore" => "[]",
        "res" => "P",
        "sportstype" => 1,
        "oddstype" => "MY",
        "uuid" => "cfa74a3d-4168-32a4-8c71-efaf2afdfe28",
        "flg" => "[]",
    ];

    protected $rawTicketData_2 = [
        "fid" => 17389122,
        "id" => "OU7749840717",
        "t" => "2019-10-08 17:05:13.180",
        "u" => "TT8E7D7040",
        "b" => 10,
        "w" => 0,
        "a" => 0,
        "c" => 0,
        "ip" => "45.76.208.158",
        "league" => 26433,
        "home" => 1743,
        "away" => 52739,
        "status" => "N",
        "game" => "OU",
        "odds" => -0.95,
        "side" => "2",
        "info" => "2.5",
        "half" => 0,
        "trandate" => "2019-10-08 17:05:13",
        "workdate" => "2019-10-08 00:00:00",
        "matchdate" => "02:45",
        "runscore" => "[]",
        "score" => "[]",
        "htscore" => "[]",
        "res" => "P",
        "sportstype" => 1,
        "oddstype" => "MY",
        "uuid" => "db2a9487-6321-3a04-811f-e123b125611a",
        "flg" => "[]",
    ];

    /**
     * 測試當原生注單注入時，為「聯合鍵資料」產生「主鍵」uuid
     */
    public function testRawTicketUuid(): void
    {
        $ticket = new UfaSportTicket($this->rawTicketData_1);
        $datas = $ticket->toArray();
        $datas['uuid'] = $ticket->uuid;

        $v3Regex = '/^[0-9a-f]{8}-[0-9a-f]{4}-[3][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        $isUUID3 = preg_match($v3Regex, $ticket->uuid);

        $this->assertArrayHasKey('uuid', $datas);
        $this->assertEquals(1, $isUUID3);
    }

    /**
     * @throws \Exception
     */
    public function testSuccessCapture(): void
    {
        $fetcher = new UfaSportFetcher([]);

        try {
            $response = $fetcher->capture();
            $this->assertArrayHasKey('tickets', $response);
        } catch (\SuperPlatform\ApiCaller\Exceptions\ApiCallerException $exc) {
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
        $fetcher = new UfaSportFetcher([]);

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