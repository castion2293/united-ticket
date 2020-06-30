<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use Carbon\Carbon;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Models\KkLotteryTicket;

class KkLotteryFetcher extends Fetcher
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

    /**
     * API接口名稱
     *
     * @var array
     */
    private $actions = [
        '/data/betlist',
        '/data/official/betlist',
        '/data/prebuylist',
        '/data/official/prebuylist',
    ];

    /**
     * 每頁筆數
     *
     * @var int
     */
    private $pageSize = 1000;

    public function __construct(array $params)
    {
        parent::__construct();

        $this->params = [
            'pagesize' => $this->pageSize,
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
        // 沒有限制但建議每次拉取紀錄都撈1小時
        if (empty($fromTime) && empty($toTime)) {
            $this->params['starttime'] = now()->subHours(1)->toDateTimeString();
            $this->params['endtime'] = now()->toDateTimeString();
            return $this;
        }

        $this->params['starttime'] = Carbon::parse($fromTime)->toDateTimeString();
        $this->params['endtime'] = Carbon::parse($toTime)->toDateTimeString();

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

        $this->params['modifystarttime'] = $this->inputDateTimeRange['from'];
        $this->params['modifyendtime'] = $this->inputDateTimeRange['to'];

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
                "　　遊戲站: KK彩票                    ",
                "　 開始時間: {$this->inputDateTimeRange['from']}",
                "　 結束時間: {$this->inputDateTimeRange['to']}",
                "--",
                ""
            ]));

            $this->curl();

            $this->consoleOutput->writeln(sprintf("累積收集共 %d 筆，已無注單，停止程序" . PHP_EOL,
                    count($this->rawTickets))
            );

            $this->consoleOutput->writeln("--");
            $this->consoleOutput->writeln("　共花費 " . $this->microTimeDiff($captureBegin, microtime()) . ' 秒');
            $this->consoleOutput->writeln("=====================================");
            $this->consoleOutput->writeln("");

            // 回傳
            return [
                'tickets' => $this->rawTickets
            ];

        } catch (ApiCallerException $exc) {
            show_exception_message($exc);
            throw $exc;
        } catch (\Exception $exc) {
            show_exception_message($exc);
            throw $exc;
        }
    }

    /**
     * 遞迴式 CURL 請求
     *
     * @return array|mixed
     * @throws \Exception
     */
    private function curl()
    {
        foreach ($this->actions as $action) {
            $this->params['pagenumber'] = 1;
            $this->fetching($action);
        }

        return $this->rawTickets;
    }

    /**
     * 撈單動作
     *
     * @param string $action
     * @throws \Exception
     */
    private function fetching(string $action)
    {
        $station = 'kk_lottery';

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

        $tickets = array_get($arrData, 'response.rows');

        if (!empty($tickets)) {
            // 因為如果是單一一張注單，需把它加到一個陣列中的元素，避免錯誤
            $tempArray = $tickets;

            if(!is_array(array_shift($tempArray))) {
                $tickets = [$tickets];
            };

            foreach ($tickets as $ticketSet) {
                $rawTicketModel = new KkLotteryTicket($ticketSet);

                // 回傳套用原生注單模組後的資料(會產生 uuid)
                $rawTicket = $rawTicketModel->toArray();
                $rawTicket['uuid'] = $rawTicketModel->uuid->__toString();

                // 增加追號標註
                if (strpos($action, 'prebuylist') !== false) {
                    $rawTicket['lottery_name'] .= '(追號)';
                }

                array_push($this->rawTickets, $rawTicket);
            }
        }

        // 如果還有注單，遞迴方式連續請求
        if (count($tickets) >= $this->pageSize) {
            $this->consoleOutput->writeln(
                sprintf(
                    "累積收集共 %d 筆，尚有注單，繼續查詢",
                    count($this->rawTickets)
                )
            );

            $this->params['pagenumber']++;
            $this->fetching($action);
        }
    }
}