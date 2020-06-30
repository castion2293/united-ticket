<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use Carbon\Carbon;
use Exception;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\UnitedTicket\Models\SoPowerTicket;

/**
 * 「手中寶」原生注單抓取器
 *
 * @package SuperPlatform\UnitedTicket\Fetchers
 */
class SoPowerFetcher extends Fetcher
{
    /**
     * 收集抓到的原始注單
     *
     * @var array
     */
    private $rawTickets = [];

    /**
     * 查詢的時間區間
     *
     * @var array
     */
    protected $timeSpan = [];

    /**
     * 設定查詢的區間
     *
     * @param string $sFromTime
     * @param string $sToTime
     * @return Fetcher
     */
    public function setTimeSpan(string $sFromTime = null, string $sToTime = null): Fetcher
    {
        if (empty($sFromTime) && empty($sToTime)) {
            $this->timeSpan['start_time'] = Carbon::parse($sToTime)->subHour(1);
            $this->timeSpan['end_time'] = Carbon::parse($sToTime);
            return $this;
        }

        $this->timeSpan['start_time'] = Carbon::parse($sFromTime);
        $this->timeSpan['end_time'] = Carbon::parse($sToTime);
        return $this;
    }

    /**
     * 自動撈單的時間區間
     */
    public function autoFetchTimeSpan(): Fetcher
    {
        // 手中寶目前不使用自動抓單時間

        return $this;
    }

    /**
     * SoPowerFetcher constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 抓取帳號注單
     *
     * + 最多不能超過 1 小時 (3600 秒)
     * + 間隔 1 分鐘以上，才能重新進行查詢
     *
     * @return array
     * @throws ApiCallerException
     * @throws Exception
     */
    public function capture(): array
    {
        try {
            $captureBegin = microtime();
            $station = 'so_power';
            $action = 'GET_REPORT';
            $endTime = array_get($this->timeSpan, 'end_time', Carbon::now());
            $startTime = array_get($this->timeSpan, 'start_time', Carbon::now()->subHours(1));

            $this->consoleOutput->writeln(join(PHP_EOL, [
                "=====================================",
                "  原生注單抓取程序啟動                  ",
                "-------------------------------------",
                "　　遊戲站: {$station}                  ",
                "　開始時間: {$startTime->toDateTimeString()}  ",
                "　結束時間: {$endTime->toDateTimeString()}",
                "　時間區間: 1 Hours",
                "--",
                ""
            ]));

            // ===
            //   抓取原生注單
            // ===
            try {
                $response = ApiCaller::make($station)
                    ->methodAction('POST', $action)
                    ->params([
                        'start_time' => $startTime->timestamp,
                        'end_time' => $endTime->timestamp,
                        'result_ok' => 'all',
                    ])
                    ->submit();
            } catch (ApiCallerException $e) {
                // 若無資料則不寫進log紀錄
                if (array_get($e->response(), 'message') !== 'NO_DATA') {
                    event(new FetcherExceptionOccurred(
                        $e,
                        $station,
                        $action,
                        [
                            'start_time' => $startTime->timestamp,
                            'end_time' => $endTime->timestamp,
                            'result_ok' => 'all',
                        ]
                    ));
                }
                throw $e;
            } catch (Exception $exception) {
                // 若無資料則不寫進log紀錄
//                if (array_get($exception->response(), 'message') !== 'NO_DATA') {
//                    event(new FetcherExceptionOccurred(
//                        $exception,
//                        $station,
//                        $action,
//                        [
//                            'start_time' => $startTime->timestamp,
//                            'end_time' => $endTime->timestamp,
//                            'result_ok' => 'all',
//                        ]
//                    ));
//                }
                throw $exception;
            }

            $rawTickets = array_get($response, 'response.data', []);


            foreach ($rawTickets as $ticket) {
                $rawTicketModel = new SoPowerTicket($ticket);

                // 回傳套用原生注單模組後的資料(會產生 uuid)
                $ticket = $rawTicketModel->toArray();
                $ticket['uuid'] = $rawTicketModel->uuid->__toString();
                $this->rawTickets[] = $ticket;

                // 注意這邊不直接對 Model 一個一個進行 save()
                // 而且整理後，讓接的人去做批次 save()
            }

            $this->consoleOutput->writeln("--");
            $this->consoleOutput->writeln("　共花費 " . $this->microTimeDiff($captureBegin, microtime()) . ' 秒');
            $this->consoleOutput->writeln("=====================================");
            $this->consoleOutput->writeln("");

            // 回傳
            return [
                'tickets' => $this->rawTickets
            ];

        } catch (ApiCallerException $exc) {
            $this->showExceptionInfo($exc);
            $this->consoleOutput->writeln(print_r($exc->response(), true));
            throw $exc;
        } catch (Exception $exc) {
            $this->showExceptionInfo($exc);
            throw $exc;
        }
    }

    /**
     * 找出需轉換的注單
     *
     * @param array $fetchTickets
     * @return array
     */
    public function compare(array $fetchRawTickets): array
    {
        // 比對後的注單
        $tickets = [];

        // 上一次狀態的注單
        $lastTickets = [];

        $uuidsChunk = collect($fetchRawTickets)
            ->pluck('uuid')
            ->chunk(500)
            ->toArray();

        foreach ($uuidsChunk as $uuids) {
            $lastTicketsSet = DB::table('raw_tickets_so_power')
                ->select('uuid', 'RESULT_OK')
                ->whereIn('uuid', $uuids)
                ->get()
                ->keyBy('uuid')
                ->toArray();

            $lastTickets = array_merge($lastTickets, $lastTicketsSet);
        }

        $tickets = collect($fetchRawTickets)->filter(function ($fetchRawTicket) use ($lastTickets) {
            $uuid = array_get($fetchRawTicket, 'uuid');

            $lastTicket = array_get($lastTickets, $uuid);

            // 新單需要做轉換
            if (empty($lastTicket)) {
                return true;
            }

            // 與上一次state狀態不同的單需要做轉換
            $fetchRawTicketState = array_get($fetchRawTicket, 'RESULT_OK');
            $lastTicketState = data_get($lastTicket, 'RESULT_OK');

            if ($fetchRawTicketState !== $lastTicketState) {
                return true;
            }
        })
            ->toArray();

        $this->consoleOutput->writeln(sprintf("真正需轉換的筆數 %d 筆" . PHP_EOL,
                count($tickets))
        );

        return [
            'tickets' => $tickets
        ];

    }
}