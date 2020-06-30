<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Models\MgPokerTicket;

/**
 * 「MG棋牌」原生注單抓取器
 *
 * @package SuperPlatform\UnitedTicket\Fetchers
 */
class MgPokerFetcher extends Fetcher
{
    /**
     * 收集抓到的原始注單
     *
     * @var array
     */
    private $rawTickets = [];

    /**
     * 抓注單 takeBetLogs 會用到的參數集
     *
     * @var array
     */
    private $params;

    /**
     * 時間區間字串
     *
     * @var string
     */
    private $timeSpan = '';

    /**
     * @var string
     * 用戶名
     */
    private $username = '';

    /**
     * MgPokerFetcher constructor.
     * @param null $secretKey
     */
    public function __construct(array $user)
    {
        parent::__construct();

        $this->username = array_get($user, 'username');
        // 準備預設的 API 參數
        $this->params = [
            'startTime' => '',
            'endTime' => '',
            'size' => 100,
            'page' => 0,
        ];
        $this->setTimeSpan();
    }

    public function __destruct()
    {
        unset($this->params);
        unset($this->username);
        unset($this->rawTickets);
        unset($this->step);
        unset($this->timeSpan);
    }

    /**
     * 設定查詢的區間（秒）
     *
     * 最多不能超過 1 天
     *
     * @param string $fromTime
     * @param string $toTime
     * @return Fetcher
     */
    public function setTimeSpan(string $fromTime = '', string $toTime = ''): Fetcher
    {
        // 自動撈單
        if (empty($fromTime) && empty($toTime)) {
            $this->params['startTime'] = Carbon::parse($fromTime)->subdays(1)->format('YmdHis');
            $this->params['endTime'] = Carbon::parse($toTime)->format('YmdHis');
            return $this;
        }

        // 手動撈單
        $this->params['startTime'] = Carbon::parse($fromTime)->format('YmdHis');
        $this->params['endTime'] = Carbon::parse($toTime)->format('YmdHis');

        return $this;
    }

    /**
     * 自動撈單的時間區間
     */
    public function autoFetchTimeSpan(): Fetcher
    {
        $this->params['startTime'] = now()->subMinutes(15)->format('YmdHis');
        $this->params['endTime'] = now()->format('YmdHis');

        return $this;
    }

    /**
     * 抓取帳號注單
     *
     * @return array
     * @throws ApiCallerException
     * @throws Exception
     */
    public function capture(): array
    {
        try {
            $this->params['account'] = $this->username;

            $captureBegin = microtime();

            $this->consoleOutput->writeln(join(PHP_EOL, [
                "=====================================",
                "  原生注單抓取程序啟動                  ",
                "-------------------------------------",
                "　　遊戲站: mg_poker                  ",
                "　　　帳號: {$this->params['account']}",
                "　開始時間: {$this->params['startTime']}",
                "　結束時間: {$this->params['endTime']}",
                "--",
                ""
            ]));

            // 以遞迴方式取出回應內容
            $this->rawTickets = $this->curl();

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
            $this->showExceptionInfo($exc);
            $this->consoleOutput->writeln(print_r($exc->response(), true));
            throw $exc;
        } catch (Exception $exc) {
            $this->showExceptionInfo($exc);
            throw $exc;
        }
    }

    /**
     * 遞迴式 CURL 請求
     *
     * @return array|mixed
     * @throws ApiCallerException
     * @throws Exception
     */
    private function curl()
    {
        $station = 'mg_poker';
        $action = 'takeBetLogs';
        try {
            $response = ApiCaller::make($station)
                ->methodAction('POST', $action)
                ->params($this->params)
                ->submit();
        } catch (Exception $exception) {
            event(new FetcherExceptionOccurred(
                $exception,
                $station,
                $action,
                $this->params
            ));
            throw $exception;
        }

        $arrData = array_get($response, 'response.data');
        // 取得注單資料並追加至原生注單收集器
        $rawTickets = array_get($arrData, 'bets', []);

        if (filled($rawTickets)) {
            // 因為如果是單一一張注單，需把它加到一個陣列中的元素，避免錯誤
            $isOnlyOneTicket = array_has($rawTickets, 'roundId');
            if ($isOnlyOneTicket) {
                $rawTickets = [$rawTickets];
            }

            // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            //   重要！！每個遊戲站都要為自己處理這一塊
            //
            //   一定要為原生注單生產生主要識別碼，如果已經有唯一識別碼
            //   就直接使用，沒有的話就要用聯合鍵來產生
            // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            foreach ($rawTickets as $rawTicket) {
                
                $rawTicketModel = new MgPokerTicket($rawTicket);

                // 回傳套用原生注單模組後的資料(會產生 uuid)
                $ticketSet = $rawTicketModel->toArray();
                $ticketSet['uuid'] = $rawTicketModel->uuid->__toString();

                array_push($this->rawTickets, $ticketSet);
            }

        }
        
        return $this->rawTickets;
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
            $lastTicketsSet = DB::table('raw_tickets_mg_poker')
                ->select('uuid', 'reset')
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
            $fetchRawTicketState = array_get($fetchRawTicket, 'reset');
            $lastTicketState = data_get($lastTicket, 'reset');

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