<?php


namespace SuperPlatform\UnitedTicket\Fetchers;


use BaseTestCase;
use Illuminate\Support\Carbon;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\UnitedTicket\Models\HabaneroTicket;

class HabaneroFetcherTest extends BaseTestCase
{
    protected $rawTicketData_1 = [
        "Username" => "ABC123DEF",
        "PlayerId" => "043a6afa-fe2d-ea11-a601-281878584619",
        "BrandGameId" => "be874928-e470-428a-ad0e-81a2dfffc0ef",
        "GameName" => "Egyptian Dreams Deluxe",
        "GameKeyName" => "SGEgyptianDreamsDeluxe",
        "GameInstanceId" => "29c2fed8-d82d-423d-ab58-a94694f0d7b3",
        "FriendlyGameInstanceId" => 638537808,
        "Stake" => 1.25,
        "Payout" => 0.0,
        "JackpotWin" => 0.0,
        "JackpotContribution" => 0.0,
        "DtStart" => "2020-01-06T07:04:43.537",
        "DtCompleted" => "2020-01-06T07:04:43.663",
        "GameStateName" => "Completed",
        "GameStateId" => 3,
        "GameTypeId" => 11,
        "BalanceAfter" => 1053.5,
    ];

    /**
     * 測試當原生注單注入時，是否會為「聯合鍵資料」產生「主鍵」uuid
     *
     * @test
     */
    public function testRawTicketUuid()
    {
        // -----------
        //   Act
        // -----------
        $ticket = new HabaneroTicket($this->rawTicketData_1);
        $datas = $ticket->toArray();
        $datas['uuid'] = $ticket->uuid;

        // -----------
        //   Assert
        // -----------
        $v3Regex = '/^[0-9a-f]{8}-[0-9a-f]{4}-[3][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        $isUUID3 = preg_match($v3Regex, $ticket->uuid);
        $this->assertEquals(1, $isUUID3);
        $this->assertArrayHasKey('uuid', $datas);
    }

    /**
     * 測試呼叫抓取 (Capture) 的動作成功，並取得成功的回應結果的案例
     *
     * @test
     */
    public function testSuccessCapture()
    {
        // -----------
        //   Act
        // -----------
        $from = Carbon::parse('2020-01-05 09:00:00')->format('YmdHis');
        $to = Carbon::now()->format('YmdHis');

        $username = 'ABC123DEF';
        $fetcher = new HabaneroFetcher([
            'Username' => $username
        ]);
        try {
            $response = $fetcher->setTimeSpan($from, $to)->capture();
            dump($response);
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
}