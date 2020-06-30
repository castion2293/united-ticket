<?php


namespace SuperPlatform\UnitedTicket\Fetchers;

use Carbon\Carbon;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Models\AwcSexyTicket;

class AwcSexyFetcher extends Fetcher
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
     * 撈注單的API 指令
     *
     * @var string
     */
    private $action = '';

    public function __construct(array $params)
    {
        parent::__construct();
        $this->params = [
            'platform' => 'SEXYBCRT',
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
        $this->action = 'getTransactionByTxTime';

        $this->inputDateTimeRange = [
            'from' => $fromTime,
            'to' => $toTime
        ];

        // 設定查詢的時間範圍
        // 沒有限制但建議每次拉取紀錄都撈1小時
        if (empty($fromTime) && empty($toTime)) {
            $this->params['startTime'] = now()->subHours(1)->toIso8601String();
            $this->params['endTime'] = now()->toIso8601String();
            return $this;
        }

        $this->params['startTime'] = Carbon::parse($fromTime)->toIso8601String();
        $this->params['endTime'] = Carbon::parse($toTime)->toIso8601String();

        return $this;
    }

    /**
     *  自動撈單的時間設定
     *
     * @return Fetcher
     */
    public function autoFetchTimeSpan(): Fetcher
    {
        $this->action = 'getTransactionByUpdateDate';

        $from = now()->subMinutes(30);
        $to = now();

        $this->inputDateTimeRange = [
            'from' => $from->toDateTimeString(),
            'to' => $to->toDateTimeString()
        ];

        $this->params['timeFrom'] = $from->toIso8601String();

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
                "　　遊戲站: 性感百家                    ",
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
        $station = 'awc_sexy';

        try {
            $arrData = ApiCaller::make($station)
                ->methodAction('POST', $this->action)
                ->params($this->params)
                ->submit();
        } catch (\Exception $exception) {

            $errorCode = '0';
            if (method_exists($exception, 'response')) {
                $errorCode = array_get($exception->response(), 'errorCode');
            }

            // 1028 訪問太頻繁不需要噴出錯誤
            $isNotFrequencyError = ($errorCode !== '1028');
            if ($isNotFrequencyError) {
                event(new FetcherExceptionOccurred(
                    $exception,
                    $station,
                    $this->action,
                    $this->params
                ));
            }

            throw $exception;
        }

        $tickets = array_get($arrData, 'response.transactions');

        if (!empty($tickets)) {
            // 因為如果是單一一張注單，需把它加到一個陣列中的元素，避免錯誤
            $tempArray = $tickets;

            if(!is_array(array_shift($tempArray))) {
                $tickets = [$tickets];
            };

            foreach ($tickets as $ticketSet) {
                $rawTicket = [
                    'ID' => array_get($ticketSet, 'ID'),
                    'userId' => array_get($ticketSet, 'userId'),
                    'platformTxId' => array_get($ticketSet, 'platformTxId'),
                    'platform' => array_get($ticketSet, 'platform'),
                    'gameCode' => array_get($ticketSet, 'gameCode'),
                    'gameType' => array_get($ticketSet, 'gameType'),
                    'betType' => array_get($ticketSet, 'betType'),
                    'txTime' => $this->iso8061ToDateTime(array_get($ticketSet, 'txTime')),
                    'betAmount' => array_get($ticketSet, 'betAmount'),
                    'winAmount' => array_get($ticketSet, 'winAmount'),
                    'turnover' => array_get($ticketSet, 'turnover'),
                    'txStatus' => array_get($ticketSet, 'txStatus'),
                    'realBetAmount' => array_get($ticketSet, 'realBetAmount'),
                    'realWinAmount' => array_get($ticketSet, 'realWinAmount'),
                    'jackpotBetAmount' => array_get($ticketSet, 'jackpotBetAmount'),
                    'jackpotWinAmount' => array_get($ticketSet, 'jackpotWinAmount'),
                    'currency' => array_get($ticketSet, 'currency'),
                    'comm' => array_get($ticketSet, 'comm'),
                    'createTime' => $this->iso8061ToDateTime(array_get($ticketSet, 'createTime')),
                    'updateTime' => $this->iso8061ToDateTime(array_get($ticketSet, 'updateTime')),
                    'bizDate' => $this->iso8061ToDateTime(array_get($ticketSet, 'bizDate')),
                    'modifyTime' => $this->iso8061ToDateTime(array_get($ticketSet, 'modifyTime')),
                    'roundId' => array_get($ticketSet, 'roundId'),
                    'gameInfo' => array_get($ticketSet, 'gameInfo'),
                ];

                $rawTicketModel = new AwcSexyTicket($rawTicket);

                // 回傳套用原生注單模組後的資料(會產生 uuid)
                $rawTicket = $rawTicketModel->toArray();
                $rawTicket['uuid'] = $rawTicketModel->uuid->__toString();

                array_push($this->rawTickets, $rawTicket);
            }
        }
    }

    /**
     * ISO8061轉換成DateTime格式
     *
     * @param string|null $iso8061
     * @return string|null
     */
    public function iso8061ToDateTime(string $iso8061 = null)
    {
        if (empty($iso8061)) {
            return $iso8061;
        }

        return Carbon::parse($iso8061)->toDateTimeString();
    }
}