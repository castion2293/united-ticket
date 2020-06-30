<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Models\CmdSportTicket;
use SuperPlatform\UnitedTicket\Models\RoyalGameTicket;


/**
 * 「CMD體育」原生注單抓取器
 *
 * @package SuperPlatform\UnitedTicket\Fetchers
 */
class CmdSportFetcher extends Fetcher
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
     * 查詢開始的注單
     *
     * @var string
     */
    protected $beginTicket;

    /**
     * 設定查詢的區間
     *
     * @param string $sFromTime
     * @param string $sToTime
     * @return Fetcher
     */
    public function setTimeSpan(string $sFromTime = null, string $sToTime = null): Fetcher
    {
        $this->begin = $sFromTime;
        $this->end = $sToTime;
        // 找出指定開始時間前最新一筆注單
        $this->beginTicket = DB::table("raw_tickets_cmd_sport")
            ->select("Id")
            ->where('TransDate', '<', $sFromTime)
            ->max("Id");

        return $this;
    }

    public function autoFetchTimeSpan(): Fetcher
    {
        $this->begin = now()->toDateTimeString();
        $this->end = now()->toDateTimeString();
        // 找出當前最新一筆注單
        $this->beginTicket = DB::table("raw_tickets_cmd_sport")
            ->select("Id")
            ->where('TransDate', '<', now()->toDateTimeString())
            ->max("Id");

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
                    "　　遊戲站: cmd_sport                  ",
                    "　開始時間: {$this->begin}  ",
                    "　結束時間: {$this->end}",
                    "-------------------------------------",
                    ""
                ]
            ));

            // -------------------------------------------------------------------------------
            // 請求執行指令：抓取CMD體育注單
            // -------------------------------------------------------------------------------
            // 例如：
            //   - 2019-05-01 00:00:00 到 2019-05-02 00:00:00
            //   - 取得 DB 中 raw ticket ids 為 `19041200` 到 `19041500`
            //   - 則指定要給皇家的 API 參數 {maxId: $beginId} 為 `19041200` 開始抓取到最後指定的結束時間
            //

            // 時間區間內無任何注單時，$beginId = 0
            if (empty($this->beginTicket)) {
                $beginId = 0;
            } else {
                $beginId = $this->beginTicket;
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
     * @param $beginId
     *
     * @throws Exception
     */
    private function curl($beginId): void
    {
        $this->step++;

        $station = 'cmd_sport';
        $action = 'betrecord';
        $allRawTicketCount = 0;
        // 抓到所有的資料
        $getAllRawTicket = [];
        // 限制最大執行「次數/每分鐘」為 6 次，因為CMD體育建議訪問頻率為 10 秒一次
        $maxIteration = 6;
        // 延遲時間，避免超過CMD體育限制訪問頻率
        $delaySeconds = 10;


        while ($maxIteration > 0) {
            try {
                $maxIteration = $maxIteration - 1;
                $aResponseFormatData = ApiCaller::make($station)
                    ->methodAction('GET', $action)
                    ->params([
                        'Version' => (int)$beginId,
                    ])
                    ->submit();
            } catch (Exception $exception) {
                event(new FetcherExceptionOccurred(
                    $exception,
                    $station,
                    $action,
                    [
                        'Version' => $beginId,
                    ]
                ));
                throw $exception;
            }
            // 取得原生注單資料
            $aRawTickets = array_get($aResponseFormatData, 'response.Data');
            // 此次撈單取得筆數
            $onceCount = count($aRawTickets);

            // 如果第一次未撈到任何注單,則直接結束
            if (empty($aRawTickets) && $this->step == 1) {
                $this->consoleOutput->writeln(sprintf(
                    "#%d 累積收集共 %d 筆，已無注單，停止程序" . PHP_EOL,
                    $this->step,
                    $onceCount
                ));
                break;
            }
            //  - 將每次抓到的資料全部合併
            $getAllRawTicket = array_merge($getAllRawTicket, $aRawTickets);

            // 目前撈到的總筆數
            $allRawTicketCount += $onceCount;

            // 若此次撈取小於1000筆跳出，等於1000筆則延遲10秒後繼續抓取注單
            if ($onceCount < 1000) {
                $this->consoleOutput->writeln(sprintf(
                    '總共' . $allRawTicketCount . '筆注單已抓取完成' . PHP_EOL
                ));
                break;
            } else {
                // 下一次撈單的起始注單id
                $nextBeginTicketId = array_get(array_last($aRawTickets), "Id");
                $beginId = $nextBeginTicketId;
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

                // 將時間格式轉成datetime
                $aTicket['TransDate'] = $this->ticksToTime(array_get($aTicket, "TransDate"));
                $aTicket['MatchDate'] = $this->ticksToTime(array_get($aTicket, "MatchDate"));
                $aTicket['StateUpdateTs'] = $this->ticksToTime(array_get($aTicket, "StateUpdateTs"));
                $aTicket['WorkingDate'] = $this->dateNumberToDate(array_get($aTicket, "WorkingDate"));


                $oRawTicketModel = new CmdSportTicket($aTicket);
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
     * 將API時間格式轉換成datetime
     *
     * @param $ticks
     * @return string
     */
    public function ticksToTime($ticks)
    {
        $date = ($ticks - 621355968000000000)/10000000;
        $dateTime = Carbon::createFromTimestamp($date)->subHours(8)->toDateTimeString();
        return $dateTime;
    }

    /**
     * 將時間格式(20191120)轉換成datetime
     *
     * @param $number
     * @return string
     */
    public function dateNumberToDate($number)
    {
        $date = DateTime::createFromFormat("Ymd", $number)->format('Y-m-d');
        return $date;
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