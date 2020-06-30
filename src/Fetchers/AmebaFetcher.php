<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Models\AmebaTicket;

class AmebaFetcher extends Fetcher
{
    /**
     * 收集抓到的原始注單
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
     * @var string
     * 用戶名
     */
    private $username = '';

    /**
     * AmebaFetcher constructor.
     * @param array $user
     */
    public function __construct(array $user)
    {
        parent::__construct();

        $this->username = array_get($user, 'username');

        // 準備預設的 API 參數
        $this->params = [];
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
     * 設定查詢的區間
     * 最多不能超過15分鐘
     * @param string $sFromTime
     * @param string $sToTime
     * @return Fetcher
     */
    public function setTimeSpan(string $sFromTime = '', string $sToTime = ''): Fetcher
    {
        // 設定查詢的時間範圍
        $this->params['from_time'] = Carbon::parse($sToTime)->subMinutes(15)->toIso8601String();
        $this->params['to_time'] = Carbon::parse($sToTime)->toIso8601String();

        return $this;
    }

    /**
     * 自動撈單的時間區間
     */
    public function autoFetchTimeSpan(): Fetcher
    {
        $this->params['from_time'] = now()->subMinutes(15)->toIso8601String();
        $this->params['to_time'] = now()->toIso8601String();

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

            $this->consoleOutput->writeln(join(
                PHP_EOL,
                [
                    '=====================================',
                    '  原生注單抓取程序啟動                  ',
                    '-------------------------------------',
                    '　　遊戲站: Ameba                  ',
                    "　開始時間: {$this->params['from_time']}  ",
                    "　結束時間: {$this->params['to_time']}",
                    '--',
                    ''
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

        $station = 'ameba';
        $action = 'get_bet_histories';

        try {
            $aResponseFormatData = ApiCaller::make($station)
                ->methodAction('POST', $action, [])
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
        $aRawTickets = array_get($aResponseFormatData, 'response.bet_histories');

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
                $oRawTicketModel = new AmebaTicket($aTicket);
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
            $lastTicketsSet = DB::table('raw_tickets_ameba')
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