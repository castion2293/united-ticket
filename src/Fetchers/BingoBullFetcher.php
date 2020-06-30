<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use Carbon\Carbon;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Models\BingoBullTicket;

class BingoBullFetcher extends Fetcher
{
    /**
     * 收集抓到的原始注單
     *
     * @var array
     */
    private $rawTickets = [];

    /**
     * api 參數
     *
     * @var array
     */
    private $params = [];

    /**
     * 原始的起訖時間
     *
     * @var array
     */
    private $inputDateTimeRange = [];

    public function __construct(array $params)
    {
        parent::__construct();

        // 準備預設的 API 參數
        $this->params = [
            'page' => 1,
        ];
    }

    public function __destruct()
    {
        unset($this->params);
        unset($this->rawTickets);
        unset($this->inputDateTimeRange);
    }

    /**
     * 設定查詢的區間
     *
     * @param string $fromTime
     * @param string $toTime
     * @return Fetcher
     */
    public function setTimeSpan(string $fromTime = '', string $toTime = ''): Fetcher
    {
        $this->inputDateTimeRange = [
            'from' => $fromTime,
            'to' => $toTime
        ];

        // 設定查詢的時間範圍
        // 沒有限制但建議每次拉取紀錄都撈30分鐘
        if (empty($fromTime) && empty($toTime)) {
            $this->params['startTime'] = now()->subMinutes(30)->timestamp;
            $this->params['endTime'] = now()->timestamp;
            return $this;
        }

        $this->params['startTime'] = Carbon::parse($fromTime)->timestamp;
        $this->params['endTime'] = Carbon::parse($toTime)->timestamp;

        return $this;
    }

    /**
     *  自動撈單的時間設定
     *
     * @return Fetcher
     */
    public function autoFetchTimeSpan(): Fetcher
    {
        $from = now()->subMinutes(30);
        $to = now();

        $this->inputDateTimeRange = [
            'from' => $from->toDateTimeString(),
            'to' => $to->toDateTimeString()
        ];

        $this->params['startTime'] = $from->timestamp;
        $this->params['endTime'] = $to->timestamp;

        return $this;
    }

    /**
     * @return array
     * @throws ApiCallerException
     */
    public function capture(): array
    {
        try {
            $captureBegin = microtime();

            $this->consoleOutput->writeln(join(PHP_EOL, [
                "=====================================",
                "  原生注單抓取程序啟動                  ",
                "-------------------------------------",
                "　　遊戲站: 賓果牛牛                    ",
                "　 開始時間: {$this->inputDateTimeRange['from']}",
                "　 結束時間: {$this->inputDateTimeRange['to']}",
                "--",
                ""
            ]));

            $this->curl();

            $this->consoleOutput->writeln(sprintf("累積收集共 %d 筆，已無注單，停止程序" . PHP_EOL,
                    count($this->rawTickets))
            );

            $this->consoleOutput->writeln(join(
                PHP_EOL,
                [
                    '--',
                    '　共花費 ' . $this->microTimeDiff($captureBegin, microtime()) . ' 秒',
                    '=====================================',
                    '',
                ]
            ));

            // 回傳
            return [
                'tickets' => $this->rawTickets,
            ];

        } catch (ApiCallerException $exc) {
            show_exception_message($exc);
            throw $exc;
        } catch (\Exception $exc) {
            show_exception_message($exc);
            throw $exc;
        }
    }

    private function curl(): void
    {
        $station = 'bingo_bull';
        $action = '/api/getReport';
        try {
            $arrData = ApiCaller::make($station)
                ->methodAction('POST', $action)
                ->params($this->params)
                ->submit();
        } catch (\Exception $exception) {
            event(new FetcherExceptionOccurred(
                $exception,
                $station,
                $action,
                $this->params
            ));
            throw $exception;
        }

        $response = array_get($arrData, 'response');
        $tickets = array_get($response, 'reportData');

        if (!empty($tickets) && is_array($tickets)) {
            // 因為如果是單一一張注單，需把它加到一個陣列中的元素，避免錯誤
            $tempArray = $tickets;

            if(!is_array(array_shift($tempArray))) {
                $tickets = [$tickets];
            };

            foreach ($tickets as $ticketSet) {
                $rawTicket['status'] = array_get($ticketSet, 'status');
                $rawTicket['betNo'] = array_get($ticketSet, 'betNo');
                $rawTicket['betData'] = array_get($ticketSet, 'betData');
                $rawTicket['realBetMoney'] = floatval(array_get($ticketSet, 'realBetMoney'));
                $rawTicket['openNo'] = array_get($ticketSet, 'openNo');
                $rawTicket['okMoney'] = floatval(array_get($ticketSet, 'okMoney'));
                $rawTicket['totalMoney'] = floatval(array_get($ticketSet, 'totalMoney'));
                $rawTicket['pumpMoney'] = floatval(array_get($ticketSet, 'pumpMoney'));
                $rawTicket['reportTime'] = Carbon::createFromTimestamp(array_get($ticketSet, 'reportTime'))->toDateTimeString();
                $rawTicket['createTime'] = Carbon::createFromTimestamp(array_get($ticketSet, 'createTime'))->toDateTimeString();
                $rawTicket['userType'] = array_get($ticketSet, 'userType');
                $rawTicket['account'] = array_get($ticketSet, 'account');
                $rawTicket['roomCode'] = array_get($ticketSet, 'roomCode');
                $rawTicket['coin'] = intval(array_get($ticketSet, 'coin'));
                $rawTicket['mainGame'] = array_get($ticketSet, 'mainGame');

                $rawTicketModel = new BingoBullTicket($rawTicket);

                // 回傳套用原生注單模組後的資料(會產生 uuid)
                $rawTicket = $rawTicketModel->toArray();
                $rawTicket['uuid'] = $rawTicketModel->uuid->__toString();

                array_push($this->rawTickets, $rawTicket);
            }
        }

        // 如果還有注單，遞迴方式連續請求
        $selectPage = array_get($response, 'selectPage');
        $allPage = array_get($response, 'dataAllPage');

        if ($selectPage < $allPage) {
            $this->consoleOutput->writeln(
                sprintf("#%d 累積收集共 %d 筆，尚有注單，繼續查詢" . PHP_EOL,
                    $this->params['page'],
                    count($this->rawTickets)
                )
            );

            $this->params['page']++;
            $this->curl();
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
        // TODO: Implement compare() method.
    }
}