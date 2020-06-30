<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Models\RealTimeGamingTicket;

/**
 * 「RTG」原生注單抓取器
 *
 * @property  params
 * @package SuperPlatform\UnitedTicket\Fetchers
 */
class RealTimeGamingFetcher extends Fetcher
{
    /**
     * 收集抓到的原始注單
     *
     * @var array
     */
    private $rawTickets = [];

    /**
     * 抓注單 GetUserBetItemDV 會用到的參數集
     * @var array
     */
    private $params = [];

    /**
     * 記錄 Request 次數
     * @var int
     */
    private $step = 0;

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
        // 設定查詢的時間範圍
        // 沒有限制但建議每次拉取紀錄都撈30分鐘
        if (empty($sFromTime) && empty($sToTime)) {
            $this->params['fromDate'] = Carbon::parse($sFromTime)->subMinutes(30)->timezone('Europe/London')->toIso8601String();
            $this->params['toDate'] = Carbon::parse($sToTime)->timezone('Europe/London')->toIso8601String();
            return $this;
        }

        $this->params['fromDate'] = Carbon::parse($sFromTime)->timezone('Europe/London')->toIso8601String();
        $this->params['toDate'] = Carbon::parse($sToTime)->timezone('Europe/London')->toIso8601String();

        return $this;
    }

    public function autoFetchTimeSpan(): Fetcher
    {
        $this->params['fromDate'] = now()->subMinutes(30)->timezone('Europe/London')->toIso8601String();
        $this->params['toDate'] = now()->timezone('Europe/London')->toIso8601String();

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
     * @return array
     * @throws ApiCallerException
     * @throws Exception
     */
    public function capture(): array
    {
        try {
            $captureBegin = microtime();

            $startTime = Carbon::parse($this->params['fromDate'])->addHours(8)->toDateTimeString();
            $endTime = Carbon::parse($this->params['toDate'])->addHours(8)->toDateTimeString();
            $this->consoleOutput->writeln(join(
                PHP_EOL,
                [
                    "=====================================",
                    "  原生注單抓取程序啟動                  ",
                    "-------------------------------------",
                    "　　遊戲站: real_time_gaming                  ",
                    "　開始時間: {$startTime}  ",
                    "　結束時間: {$endTime}",
                    "--",
                    ""
                ]
            ));

            $this->curl();

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

        } catch (ApiCallerException $e) {
            show_exception_message($e);
            throw $e;
        } catch (Exception $e) {
            show_exception_message($e);
            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    private function curl(): void
    {
        $this->step++;

        $station = 'real_time_gaming';
        $action = 'report/playergame';

        // ===
        //   抓取原生注單
        // ===
        try {
            $aResponseFormatData = ApiCaller::make($station)
                ->methodAction('POST', $action)
                ->params([
                    'params' => [
                        'agentId' => $this->getGameAgentId(),
                        'fromDate' => $this->params['fromDate'],
                        'toDate' => $this->params['toDate'],
                    ],
                ])
                ->submit();
        } catch (Exception $exception) {
            event(new FetcherExceptionOccurred(
                $exception,
                $station,
                $action,
                [
                    'params' => [
                        'agentId' => $this->getGameAgentId(),
                        'fromDate' => $this->params['fromDate'],
                        'toDate' => $this->params['toDate'],
                    ],
                ]
            ));
            throw $exception;
        }
        // 取得注單資料並追加至原生注單收集器
        $aRawTickets = array_get($aResponseFormatData, 'response.items');

        // 檢查 $aRawTickets 是否為空
        if (filled($aRawTickets)) {
            /**
             * TODO: 重要!!! 每個遊戲站都要為自己處理這一塊!!!
             *
             * 一定要為原生注單生產生主要識別碼
             * 如果已經有唯一識別碼就直接使用
             * 沒有的話就要用聯合鍵來產生
             */
            $aTemp = [];

            foreach ($aRawTickets as $aTicket) {
                $oRawTicketModel = new RealTimeGamingTicket($aTicket);
                // 回傳套用原生注單模組後的資料 (會產生 uuid)
                $aTicket = $oRawTicketModel->toArray();
                $aTicket['uuid'] = $oRawTicketModel->uuid->__toString();

                $aTemp[] = $aTicket;
            }

            $this->rawTickets = array_merge($this->rawTickets, $aTemp);

            $this->consoleOutput->writeln(sprintf(
                "#%d 累積收集共 %d 筆，已無注單，停止程序" . PHP_EOL,
                $this->step,
                count($this->rawTickets)
            ));
        }
    }

    /**
     * 取得RTG的 agentID (戳其他 API 需要)
     *
     * @throws Exception
     */
    public function getGameAgentId()
    {
        try {
            // Act
            $agentId = ApiCaller::make('real_time_gaming')->methodAction('get', 'start', [
                // 路由參數這邊設定
            ])->params([
                // 一般參數這邊設定

            ])->submit()['response']['agentId'];

        } catch (ApiCallerException $exception) {
            $this->console->writeln($exception->response());
            throw $exception;
        }
        return $agentId;
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
            $lastTicketsSet = DB::table('raw_tickets_real_time_gaming')
                ->select('uuid')
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