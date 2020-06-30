<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Models\Cq9GameTicket;

/**
 * 「cq9」原生注單抓取器
 *
 * @property  params
 * @package SuperPlatform\UnitedTicket\Fetchers
 */
class Cq9GameFetcher extends Fetcher
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
     * SoPowerFetcher constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->params = [
            'page' => "1",  // 單前頁數
            'pagesize' => "20000"  // 每頁幾筆(預設為
        ];
    }

    public function __destruct()
    {
        unset($this->params);
        unset($this->rawTickets);
        unset($this->step);
        unset($this->timeSpan);
    }

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
            $this->params['starttime'] = Carbon::parse($sFromTime)->subMinutes(30)->timezone('America/Boa_Vista')->toRfc3339String();
            $this->params['endtime'] = Carbon::parse($sToTime)->timezone('America/Boa_Vista')->toRfc3339String();
            return $this;
        }

        $this->params['starttime'] = Carbon::parse($sFromTime)->timezone('America/Boa_Vista')->toRfc3339String();
        $this->params['endtime'] = Carbon::parse($sToTime)->timezone('America/Boa_Vista')->toRfc3339String();

        return $this;
    }

    /**
     * 自動撈單的時間區間
     */
    public function autoFetchTimeSpan(): Fetcher
    {
        $this->params['starttime'] = now()->subMinutes(30)->timezone('America/Boa_Vista')->toRfc3339String();
        $this->params['endtime'] = now()->timezone('America/Boa_Vista')->toRfc3339String();

        return $this;
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

            $formatStartTime = Carbon::parse($this->params['starttime'])->timezone("Asia/Taipei")->toDateTimeString();
            $formatEndTime = Carbon::parse($this->params['endtime'])->timezone("Asia/Taipei")->toDateTimeString();

            $this->consoleOutput->writeln(join(
                PHP_EOL,
                [
                    "=====================================",
                    "  原生注單抓取程序啟動                  ",
                    "-------------------------------------",
                    "　　遊戲站: cq9_game                  ",
                    "　開始時間: {$formatStartTime}  ",
                    "　結束時間: {$formatEndTime}",
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

        $station = 'cq9_game';
        $action = 'order/view';
        $totalSize = 0;
        // ===
        //   抓取原生注單
        // ===
        try {
            $aResponseFormatData = ApiCaller::make($station)
                ->methodAction('GET', $action)
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

        // 取得注單資料並追加至原生注單收集器
        $aRawTickets = array_get($aResponseFormatData, 'response.data');
        // 如果無錯誤,但該時段無注單 $aRawTickets = null;
        // 如果無錯誤,該時段有注單 $aRawTickets = array_get($aRawTickets, 'Data');
        if (array_has($aRawTickets, 'Data') && array_has($aRawTickets, 'TotalSize')) {
            $totalSize = array_get($aRawTickets, 'TotalSize');
            $aRawTickets = array_get($aRawTickets, 'Data');
        }


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
                $oRawTicketModel = new Cq9GameTicket($aTicket);

                // 回傳套用原生注單模組後的資料 (會產生 uuid)
                $aTicket = $oRawTicketModel->toArray();
                $aTicket['uuid'] = $oRawTicketModel->uuid->__toString();

                $aTicket['detail'] = json_encode($aTicket['detail']);
                $aTicket['jackpotcontribution'] = json_encode($aTicket['jackpotcontribution']);
                $aTemp[] = $aTicket;
            }

            $this->rawTickets = array_merge($this->rawTickets, $aTemp);

            if (count($this->rawTickets) < $totalSize) {
                $this->params['page'] = $this->params['page'] + 1;
                $this->capture();
            }

            $this->consoleOutput->writeln(sprintf(
                "#%d 累積收集共 %d 筆，已無注單，停止程序" . PHP_EOL,
                $this->step,
                count($this->rawTickets)
            ));
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
            $lastTicketsSet = DB::table('raw_tickets_cq9_game')
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