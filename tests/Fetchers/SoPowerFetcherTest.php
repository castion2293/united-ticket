<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use BaseTestCase;
use Exception;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\UnitedTicket\Models\SoPowerTicket;

class SoPowerFetcherTest extends BaseTestCase
{
    /**
     * 測試當原生注單注入時，為「聯合鍵資料」產生「主鍵」uuid
     */
    public function testRawTicketUuid(): void
    {
        $aRawTicketData = [
            'USERNAME' => 'upgtest2',
            'BETID' => '123',
        ];

        $ticket = new SoPowerTicket($aRawTicketData);
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
        $fetcher = new SoPowerFetcher();

        try {
            $aTicketsMerged = $fetcher
                ->setTimeSpan(
                    '2019-03-18 20:00:00',
                    '2019-03-18 21:00:00'
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