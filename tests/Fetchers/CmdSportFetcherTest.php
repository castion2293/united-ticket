<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use BaseTestCase;
use Exception;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\UnitedTicket\Models\CmdSportTicket;
use SuperPlatform\UnitedTicket\Models\RoyalGameTicket;

class CmdSportFetcherTest extends BaseTestCase
{
    protected $rawTicketData_1 = [
      "Id" => 69239256,
      "SourceName" => "TT9B26D8F8",
      "ReferenceNo" => "HDP103952959",
      "SocTransId" => 10395295,
      "IsFirstHalf" => false,
      "TransDate" => 637098445466100000,
      "IsHomeGive" => true,
      "IsBetHome" => true,
      "BetAmount" => 10.0,
      "Outstanding" => -9.4,
      "Hdp" => -0.5,
      "Odds" => -0.94,
      "Currency" => "VD",
      "WinAmount" => -2.0,
      "ExchangeRate" => 0.12,
      "WinLoseStatus" => "P",
      "TransType" => "HDP",
      "DangerStatus" => "N",
      "MemCommissionSet" => 0.0,
      "MemCommission" => 0.0,
      "BetIp" => "192.168.72.41",
      "HomeScore" => 0,
      "AwayScore" => 0,
      "RunHomeScore" => 0,
      "RunAwayScore" => 0,
      "IsRunning" => true,
      "RejectReason" => "",
      "SportType" => "S",
      "Choice" => "1",
      "WorkingDate" => 20191119,
      "OddsType" => "MY",
      "MatchDate" => 637097940000000000,
      "HomeTeamId" => 169224,
      "AwayTeamId" => 169221,
      "LeagueId" => 43060,
      "SpecialId" => "",
      "IsCashOut" => true,
      "CashOutTotal" => 10.0,
      "CashOutTakeBack" => 7.4,
      "CashOutWinLoseAmount" => -2.0,
      "BetSource" => 1,
      "StatusChange" => 0,
      "StateUpdateTs" => 637098475710130000,
      "AOSExcluding" => "",
      "MMRPercent" => 0.0,
      "MatchID" => 6937090,
      "MatchGroupID" => "5b551814-2db3-44cc-a20e-d20014fb8cce",
      "BetRemarks" => "",
      "IsSpecial" => true,
    ];



    /**
     * 測試當原生注單注入時，為「聯合鍵資料」產生「主鍵」uuid
     */
    public function testRawTicketUuid(): void
    {
        $ticket = new CmdSportTicket($this->rawTicketData_1);

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
        $fetcher = new CmdSportFetcher();
        try {
            $aTicketsMerged = $fetcher
                ->setTimeSpan(
                    '2019-11-15 09:00:00',
                    '2019-11-18 16:00:00'
                )->capture();
            $this->assertArrayHasKey('tickets', $aTicketsMerged);
        } catch (ApiCallerException $exc) {
            $this->assertInstanceOf('SuperPlatform\ApiCaller\Exceptions\ApiCallerException', $exc);
            $this->assertEquals('Api caller receive failure response, use `$exception->response()` get more details.',
                $exc->getMessage());
            $this->assertEquals(true, is_array($exc->response()));
        }
    }
}