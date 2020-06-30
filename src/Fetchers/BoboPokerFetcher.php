<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Models\BoboPokerTicket;

class BoboPokerFetcher extends Fetcher
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
     * 撈單的方法
     *
     * @var string
     */
    private $action = '';

    /**
     * BoboPoker constructor.
     * @param null $secretKey
     */
    public function __construct(array $params)
    {
        parent::__construct();

        // 準備預設的 API 參數
        $this->params = [
            'spId' => config('api_caller.bobo_poker.config.agent_id')
        ];
    }

    public function __destruct()
    {
        unset($this->params);
        unset($this->rawTickets);
    }

    /**
     * 設定查詢的區間
     *
     * @param string $sFromTime
     * @param string $sToTime
     * @return Fetcher
     */
    public function setTimeSpan(string $fromTime = '', string $toTime = ''): Fetcher
    {
        $this->action = 'getBetRecordByHourSetDay';

        if (empty($fromTime) && empty($toTime)) {
            $this->params['date'] = now()->format('Ymd');
            $this->params['hour'] = now()->format('H');

            return $this;
        }

        $this->params['date'] = Carbon::parse($toTime)->format('Ymd');
        $this->params['hour'] = Carbon::parse($toTime)->format('H');

        // 字串轉數字，去掉前面的0，在轉為字串，避免API格式錯誤
        $this->params['hour'] = (string)intval($this->params['hour']);

        return $this;
    }

    /**
     *  自動撈單的時間設定
     *
     * @return Fetcher
     */
    public function autoFetchTimeSpan(): Fetcher
    {
        $this->action = 'getBetRecordByHour';

        return $this;
    }

    /**
     * 抓取帳號注單
     *
     * @param string $username
     * @return array
     * @throws ApiCallerException
     * @throws \Exception
     */
    public function capture(): array
    {
        try {
            $captureBegin = microtime();

            $date = array_get($this->params, 'date');
            $hour = array_get($this->params, 'hour');

            $this->consoleOutput->writeln(join(PHP_EOL, [
                "=====================================",
                "  原生注單抓取程序啟動                  ",
                "-------------------------------------",
                "　　遊戲站: 人人棋牌                    ",
                "　 日期: {$date}",
                "　 時間小時: {$hour}",
                "--",
                ""
            ]));

            // 以遞迴方式取出回應內容
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
     * @throws ApiCallerException
     */
    private function curl()
    {
        $station = 'bobo_poker';
        $action = "datasouce/{$this->action}";
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

        $tickets = array_get($arrData, 'response.data.record');

        if (filled($tickets)) {
            // 因為如果是單一一張注單，需把它加到一個陣列中的元素，避免錯誤
            $tempArray = $tickets;

            if(!is_array(array_shift($tempArray))) {
                $tickets = [$tickets];
            };

            foreach ($tickets as $ticketSet) {
                $betDetails = array_get($ticketSet, 'betDetail');

                foreach ($betDetails as $betDetail) {
                    $rawTicket['account'] = array_get($ticketSet, 'account');
                    $rawTicket['betId'] = array_get($ticketSet, 'betId');
                    $rawTicket['gameNumber'] = array_get($ticketSet, 'gameNumber');
                    $rawTicket['gameName'] = array_get($ticketSet, 'gameName');
                    $rawTicket['result'] = array_get($ticketSet, 'result');
                    $rawTicket['betDetailId'] = array_get($betDetail, 'betDetailId');
                    $rawTicket['betAmt'] = array_get($betDetail, 'betAmt');
                    $rawTicket['earn'] = array_get($betDetail, 'earn');
                    $rawTicket['content'] = array_get($betDetail, 'content');
                    $rawTicket['betTime'] = array_get($ticketSet, 'betTime');
                    $rawTicket['payoutTime'] = array_get($betDetail, 'payoutTime');
                    $rawTicket['status'] = array_get($betDetail, 'status');

                    $rawTicketModel = new BoboPokerTicket($rawTicket);

                    // 回傳套用原生注單模組後的資料(會產生 uuid)
                    $rawTicket = $rawTicketModel->toArray();
                    $rawTicket['uuid'] = $rawTicketModel->uuid->__toString();

                    array_push($this->rawTickets, $rawTicket);
                }
            }
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
            $lastTicketsSet = DB::table('raw_tickets_bobo_poker')
                ->select('uuid', 'status')
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
            $fetchRawTicketState = array_get($fetchRawTicket, 'status');
            $lastTicketState = data_get($lastTicket, 'status');

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