<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Models\RoyalGameTicket;


/**
 * 「RG」原生注單抓取器
 *
 * @package SuperPlatform\UnitedTicket\Fetchers
 */
class RoyalGameFetcher extends Fetcher
{
    /**
     * 收集抓到的原始注單
     *
     * @var array
     */
    private $rawTickets = [];

    /**
     * 記錄 Request 次數
     *
     * @var int
     */
    private $step = 0;

    /**
     * 查詢的時間區間起點
     *
     * @var string
     */
    protected $begin;

    /**
     * 查詢的時間區間終點
     *
     * @var string
     */
    protected $end;

    /**
     * 設定查詢的區間
     *
     * @param string $sFromTime
     * @param string $sToTime
     * @return Fetcher
     */
    public function setTimeSpan(string $sFromTime = null, string $sToTime = null): Fetcher
    {
        // 當情境為「排程 (queue jobs) 執行指令時
        // 建議每次拉取前 30 分鐘到現在產生的注單，避免爆量問題發生 (執行時間過長，導致 timeout)
        $this->begin = (empty($sFromTime) && empty($sToTime))
            ? Carbon::parse($sFromTime)->subMinutes(30)->toDateTimeString()
            : Carbon::parse($sFromTime)->toDateTimeString();
        $this->end = Carbon::parse($sToTime)->toDateTimeString();

        return $this;
    }

    public function autoFetchTimeSpan(): Fetcher
    {
        $this->begin = now()->subMinutes(30)->toDateTimeString();
        $this->end = now()->toDateTimeString();

        return $this;
    }

    /**
     * RoyalGameFetcher constructor
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
            $this->consoleOutput->writeln(join(
                PHP_EOL,
                [
                    "=====================================",
                    "  原生注單抓取程序啟動                  ",
                    "-------------------------------------",
                    "　　遊戲站: royal_game                  ",
                    "　開始時間: {$this->begin}  ",
                    "　結束時間: {$this->end}",
                    "-------------------------------------",
                    ""
                ]
            ));

            // -------------------------------------------------------------------------------
            // 請求執行指令：抓取皇家注單
            // -------------------------------------------------------------------------------
            // 例如：
            //   - 2019-05-01 00:00:00 到 2019-05-02 00:00:00
            //   - 取得 DB 中 raw ticket ids 為 `19041200` 到 `19041500`
            //   - 則指定要給皇家的 API 參數 {maxId: $beginId} 為 `19041200` 開始抓取到最後指定的結束時間
            //
            $beginId = DB::table('raw_tickets_royal_game')
                ->whereBetween('BetTime', [$this->begin, $this->end])
                ->min('ID');

            // 時間區間內無任何注單時，往前查找最大的注單 ID 作為給皇家 API 參數 {maxId: $beginId}
            if (empty($beginId)) {
                $beginId = DB::table('raw_tickets_royal_game')
                    ->where('BetTime', '<', $this->begin)
                    ->max('ID');
            }

            // 無任何注單時，指定要給皇家的 API 參數 {maxId: $beginId} 為最小 ID 值
            if (empty($beginId)) {
                $beginId = 1;
            }

            $this->curl($beginId);

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
     * 抓取原生注單
     *
     * @throws Exception
     */
    private function curl($beginId): void
    {
        $this->step++;

        $station = 'royal_game';
        $action = 'VPSStakeDetail2';
        $allRawTicketCount = 0;
        // 抓到所有的資料
        $getAllRawTicket = [];
        // 限制最大執行「次數/每分鐘」為 6 次，因為皇家建議訪問頻率為 10 秒一次
        $maxIteration = 6;
        // 延遲時間，避免超過皇家限制訪問頻率
        $delaySeconds = 10;

        while ($maxIteration > 0) {
            try {
                $maxIteration = $maxIteration - 1;
                $aResponseFormatData = ApiCaller::make($station)
                    ->methodAction('POST', $action)
                    ->params([
                        'RequestID' => str_random(32),
                        'BucketID' => config("api_caller.royal_game.config.bucket_id"),
                        'MaxID' => (int)$beginId,
                        'GameType' => 1,
                        'ProviderID' => 'Royal'
                    ])
                    ->submit();
            } catch (Exception $exception) {
                event(new FetcherExceptionOccurred(
                    $exception,
                    $station,
                    $action,
                    [
                        'RequestID' => str_random(32),
                        'BucketID' => config("api_caller.royal_game.config.bucket_id"),
                        'MaxID' => (int)$beginId,
                        'GameType' => 1,
                        'ProviderID' => 'Royal'
                    ]
                ));
                throw $exception;
            }

            // 取得原生注單資料
            $response = array_get($aResponseFormatData, 'response');

            //   - 並追加至原生注單收集器
            $aRawTicketCount = 0;

            if (!empty($response)) {
                $aRawTickets = array_get($aResponseFormatData, 'response.Result');

                //  - 將每次抓到的資料全部合併
                $getAllRawTicket = array_merge($getAllRawTicket, $aRawTickets);

                //  - 原生注單筆數
                $aRawTicketCount = collect($aRawTickets)->count();
            }

            $aRawTicketsID = collect($aRawTickets)->pluck('ID')->toArray();

            // 取得 next begin ID = 最後一筆注單的 ID + 1
            if ($aRawTicketCount !== 0 && !empty($aRawTickets[$aRawTicketCount - 1])) {
                $beginId = $aRawTicketsID[$aRawTicketCount - 1] + 1;
            }

            // 目前撈到的總筆數
            $allRawTicketCount += $aRawTicketCount;

            // 若此次撈取小於100筆跳出，等於100筆則延遲5秒後繼續抓取注單
            if ($aRawTicketCount < 100) {
                $this->consoleOutput->writeln(sprintf(
                    '總共' . $allRawTicketCount . '筆注單已抓取完成' . PHP_EOL
                ));
                break;
            } else {
                $this->consoleOutput->writeln(sprintf(
                    '已抓取' . $allRawTicketCount . '筆注單並繼續抓取' . PHP_EOL
                ));
                sleep($delaySeconds);
            }
        }

        // 檢查 $aRawTickets 是否為空
        if (filled($getAllRawTicket)) {
            /**
             * TODO: 重要!!! 每個遊戲站都要為自己處理這一塊!!!
             *
             * 一定要為原生注單生產生主要識別碼
             * 如果已經有唯一識別碼就直接使用
             * 沒有的話就要用聯合鍵來產生
             */
            $aTemp = [];

            foreach ($getAllRawTicket as $aTicket) {
                $oRawTicketModel = new RoyalGameTicket(collect($aTicket)->toArray());
                // 回傳套用原生注單模組後的資料 (會產生 uuid)
                $aTicket = $oRawTicketModel->toArray();
                $aTicket['uuid'] = $oRawTicketModel->uuid->__toString();

                $aTemp[] = $aTicket;
            }

            $this->rawTickets = array_merge($this->rawTickets, $aTemp);

            $this->consoleOutput->writeln(sprintf(
                "#%d 累積收集共 %d 筆，已無注單，停止程序" . PHP_EOL,
                $this->step,
                $allRawTicketCount
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
            $lastTicketsSet = DB::table('raw_tickets_royal_game')
                ->select('uuid', 'BetStatus')
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
            $fetchRawTicketState = array_get($fetchRawTicket, 'BetStatus');
            $lastTicketState = data_get($lastTicket, 'BetStatus');

            if ($fetchRawTicketState != $lastTicketState) {
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