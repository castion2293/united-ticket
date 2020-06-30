<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use BaseTestCase;
use Carbon\Carbon;
use Exception;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\UnitedTicket\Models\AmebaTicket;

class AmebaFetcherTest extends BaseTestCase
{
    protected $rawTicketData_1 = [
        'account_name' => 'TT8E7D7040',
        'currency' => 'TWD',
        'game_id' => 1,
        'round_id' => 3214438,
        'free' => false,
        'bet_amt' => '12.00',
        'payout_amt' => '"8.00',
        'completed_at' => '2019-10-03T05:20:18+00:00',
        'jp_pc_con_amt' => '0.06',
        'jp_jc_con_amt' => '0.06',
        'jp_win_id' => '',
        'jp_pc_win_amt' => '0.0',
        'jp_jc_win_amt' => '0.0',
        'jp_win_lv' => null,
        'jp_direct_pay' => false,
        'uuid' => "df03996d-4b95-3c84-b372-e89f9786abe5"
    ];

    protected $rawTicketData_2 = [
        'account_name' => 'TT8E7D7040',
        'currency' => 'TWD',
        'game_id' => 1,
        'round_id' => 3214439,
        'free' => false,
        'bet_amt' => '12.00',
        'payout_amt' => '0.00',
        'completed_at' => '2019-10-03T05:20:26+00:00',
        'jp_pc_con_amt' => '0.06',
        'jp_jc_con_amt' => '0.06',
        'jp_win_id' => '',
        'jp_pc_win_amt' => '0.0',
        'jp_jc_win_amt' => '0.0',
        'jp_win_lv' => null,
        'jp_direct_pay' => false,
        'uuid' => "39df0023-2577-3e80-8bb3-af05fe8a5f91"
    ];

    /**
     * 測試當原生注單注入時，為「聯合鍵資料」產生「主鍵」uuid
     */
    public function testRawTicketUuid(): void
    {
        $ticket = new AmebaTicket($this->rawTicketData_1);
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
        $oFetcher = new AmebaFetcher([]);

        try {
            $aTicketsMerged = $oFetcher->setTimeSpan('2019-10-03 13:10:00', '2019-10-03 13:20:00')->capture();
//            dd($aTicketsMerged);
            $this->assertArrayHasKey('tickets', $aTicketsMerged);
        } catch (ApiCallerException $e) {
            $this->assertInstanceOf('SuperPlatform\ApiCaller\Exceptions\ApiCallerException', $e);
            $this->assertEquals('Api caller receive failure response, use `$exception->response()` get more details.', $e->getMessage());
            $this->assertEquals(true, is_array($e->response()));
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
        $fetcher = new AmebaFetcher([]);

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
        $fetcher = new AmebaFetcher([]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([AmebaTicket::class, 'replace'], $fetchTickets);

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
        $fetcher = new AmebaFetcher([]);

        $fetchTickets = [
            $this->rawTicketData_1
        ];

        call_user_func([AmebaTicket::class, 'replace'], $fetchTickets);

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