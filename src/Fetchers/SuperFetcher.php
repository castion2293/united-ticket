<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Models\SuperTicket;

class SuperFetcher extends Fetcher
{
    /**
     * 收集抓到的原始注單
     *
     * @var array
     */
    private $rawTickets = [];

    /**
     * 抓注單 GetUserBetItemDV 會用到的參數集
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
     * 記錄 Request 次數
     *
     * @var int
     */
    private $step = 0;

    /**
     * @var string
     * 用戶名
     */
    private $username = '';

    /**
     * SaGamingFetcher constructor.
     * @param null $secretKey
     */
    public function __construct(array $user)
    {
        parent::__construct();

        $this->username = array_get($user, 'username');

        // 準備預設的 API 參數
        $this->params = [
            'act' => 'detail',
            'account' => '',
            'level' => '1',
            's_date' => '',
            'e_date' => '',
            'start_time' => '',
            'end_time' => ''
        ];
//        $this->setTimeSpan();
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
     * 最多不能超過 7 天 (604800 秒)
     *
     * @param string $sFromTime
     * @param string $sToTime
     * @return Fetcher
     */
    public function setTimeSpan(string $sFromTime = '', string $sToTime = ''): Fetcher
    {
        // 建立查詢的時間範圍
        $dt = Carbon::now();

        $sToTime = empty($sToTime) ? $dt->copy() : $dt->copy()->parse($sToTime);
        if (empty($sFromTime)) {
            $sFromTime = $dt->copy()->subSeconds(config("ticket_fetcher.sa_gaming.fetch_time_range_seconds"));
        } else {
            $sFromTime = $dt->copy()->parse($sFromTime);
        }

        $timeDiff = $sFromTime->diff($sToTime);
        $this->timeSpan = sprintf(
            "%s 天 %s 時 %s 分 %s 秒之前",
            $timeDiff->d,
            $timeDiff->h,
            $timeDiff->m,
            $timeDiff->s
        );

        // 設定查詢的時間範圍
        $this->params['s_date'] = $sFromTime->toDateString();
        $this->params['e_date'] = $sToTime->toDateString();
        $this->params['start_time'] = $sFromTime->toTimeString();
        $this->params['end_time'] = $sToTime->toTimeString();

        return $this;
    }

    /**
     * 自動撈單的時間區間
     */
    public function autoFetchTimeSpan(): Fetcher
    {
        $start = now()->subDays(2);
        $end = now();

        $this->params['s_date'] = $start->toDateString();
        $this->params['e_date'] = $end->toDateString();
        $this->params['start_time'] = $start->toTimeString();
        $this->params['end_time'] = $end->toTimeString();

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
                "　　遊戲站: Super Sport                  ",
                "　　　帳號: {$this->params['account']}",
                "　開始時間: {$this->params['s_date']} {$this->params['start_time']}",
                "　結束時間: {$this->params['e_date']} {$this->params['end_time']}",
                "--",
                ""
            ]));

            // 以遞迴方式取出回應內容
            $this->rawTickets = $this->curl();

            $this->consoleOutput->writeln(sprintf("累積收集共 %d 筆，已無注單，停止程序" . PHP_EOL,
                    count($this->rawTickets))
            );

            $this->rawTickets = collect($this->rawTickets)->map(function ($rawTicket) {
                $rawTicket['detail'] = json_encode(array_get($rawTicket, 'detail'));

                return $rawTicket;
            })->toArray();

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
//            $this->showExceptionInfo($exc);
//            $this->consoleOutput->writeln(print_r($exc->response(), true));
            throw $exc;
        } catch (Exception $exc) {
            show_exception_message($exc);
//            $this->showExceptionInfo($exc);
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
        $this->step++;

        $station = 'super_sport';
        $action = 'report';
        try {
            $arrData = ApiCaller::make($station)
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

        // 取得注單資料並追加至原生注單收集器
        $rawTickets = array_get($arrData, 'response.data');

        if (filled($rawTickets)) {
            // 因為如果是單一一張注單，需把它加到一個陣列中的元素，避免錯誤
            $tempArray = $rawTickets;

            if (!is_array(array_shift($tempArray))) {
                $rawTickets = [$rawTickets];
            };

            // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            //   重要！！每個遊戲站都要為自己處理這一塊
            //
            //   一定要為原生注單生產生主要識別碼，如果已經有唯一識別碼
            //   就直接使用，沒有的話就要用聯合鍵來產生
            // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            array_walk($rawTickets, function (&$ticket, $key) {
                $rawTicketModel = new SuperTicket($ticket);

                // 回傳套用原生注單模組後的資料(會產生 uuid)
                $ticket = $rawTicketModel->toArray();
                $ticket['uuid'] = $rawTicketModel->uuid->__toString();
            });

            $this->rawTickets = array_merge($this->rawTickets, $rawTickets);
        }

        return $this->rawTickets;

        // 如果還有注單，遞迴方式連續請求
//        if ((string)array_get($arrData, 'More') === 'true') {
//            $this->params['Offset'] = array_get($arrData, 'Offset');
//            $this->consoleOutput->writeln(
//                sprintf(
//                    "#%d 累積收集共 %d 筆，尚有注單，繼續查詢 %s",
//                    $this->step,
//                    count($this->rawTickets),
//                    $this->params['Offset']
//                )
//            );
//            return $this->curl();
//        } else {
//            $this->consoleOutput->writeln(
//                sprintf(
//                    "#%d 累積收集共 %d 筆，已無注單，停止程序",
//                    $this->step,
//                    count($this->rawTickets)
//                )
//            );
//            return $this->rawTickets;
//        }
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
            $lastTicketsSet = DB::table('raw_tickets_super')
                ->select('uuid', 'status_note', 'end')
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

            // 與上一次end狀態不同的單需要做轉換
            $fetchRawTicketEnd = array_get($fetchRawTicket, 'end');
            $lastTicketEnd = data_get($lastTicket, 'end');
            if ($fetchRawTicketEnd !== $lastTicketEnd) {
                return true;
            }

            // 與上一次status_node狀態不同的單需要做轉換
            $fetchRawTicketStatus = array_get($fetchRawTicket, 'status_note');
            $lastTicketStatus = data_get($lastTicket, 'status_note');
            if ($fetchRawTicketStatus !== $lastTicketStatus) {
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