<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Models\AllBetTicket;

class AllBetFetcher extends Fetcher
{
    /**
     * 分頁資料數量
     * @var int
     */
    public $pageSize = 100;

    /**
     * 分頁索引
     * @var int
     */
    public $pageIndex = 1;

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
     * 代理帳號
     * @var string
     */
    private $agent = '';

    /**
     * 撈單的方法
     *
     * @var string
     */
    private $action = '';

    /**
     * SaGamingFetcher constructor.
     * @param null $secretKey
     */
    public function __construct(array $user)
    {
        parent::__construct();

        $this->username = array_get($user, 'username');

        // 單錢包與多錢包的接口不同
        $this->action = (env('APP_IS_SINGLE_BALANCE_SITE') === 'yes') ? 'query_client_betlog' : 'client_betlog_query';

        // 準備預設的 API 參數
        $this->params = [
            'client' => $this->username,
            'pageIndex' => 1,
            'pageSize' => 100
        ];

        $eGameType = array_get($user, 'e_game_type');
        if (!empty($eGameType)) {
            $this->agent = config('api_caller.all_bet.config.agent');
            $this->action = 'egame_betlog_histories';

            $this->params = [
                'random' => mt_rand(),
                'agent' => $this->agent,
                'egameType' => $eGameType,
                'pageIndex' => 1,
                'pageSize' => 1000,
            ];
        }
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
            $sFromTime = $dt->copy()->subMinute(1);
            $sToTime = $dt->copy();
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
        $this->params['startTime'] = $sFromTime->toDateTimeString();
        $this->params['endTime'] = $sToTime->toDateTimeString();

        return $this;
    }

    /**
     * 自動撈單的時間區間
     */
    public function autoFetchTimeSpan(): Fetcher
    {
        // 設定查詢的時間範圍
        $this->params['startTime'] = now()->subDays(1)->toDateTimeString();
        $this->params['endTime'] = now()->toDateTimeString();

        return $this;
    }

    public function capture(): array
    {
        try {
            $captureBegin = microtime();

            $client = array_get($this->params, 'client');
            $startTime = array_get($this->params, 'startTime');
            $endTime = array_get($this->params, 'endTime');

            $this->consoleOutput->writeln(
                join(PHP_EOL, [
                    "=====================================",
                    "  原生注單抓取程序啟動                  ",
                    "-------------------------------------",
                    "　　遊戲站: all_bet                    ",
                    "　　　帳號: {$client}",
                    "　開始時間: {$startTime}",
                    "　結束時間: {$endTime}",
                    "--",
                    ""
                ]));

            // 以遞迴方式取出回應內容
            $this->rawTickets = $this->curl($this->username);

            $this->consoleOutput->writeln(
                join(PHP_EOL, [
                    "--",
                    "　共花費 " . $this->microTimeDiff($captureBegin, microtime()) . ' 秒',
                    "=====================================",
                    '',
                ]));

            // 回傳
            return [
                'tickets' => $this->rawTickets
                // 如果遊戲站有兩種格式以上的注單，就再自定義索引名補第二個
                // 'slot_tickets' => [],
            ];

        } catch (ApiCallerException $exc) {
            show_exception_message($exc);
//            $this->showExceptionInfo($exc);
//            $this->consoleOutput->writeln(print_r($exc->response(), true));
            throw $exc;
        } catch (Exception $exc) {
            show_exception_message($exc);
            throw $exc;
        }
    }

    /**
     * 遞迴式 CURL 請求
     * @return array|mixed
     * @throws Exception
     */
    private function curl($username)
    {
        $this->step++;

        $station = 'all_bet';

        try {
            $response = ApiCaller::make($station)
                ->methodAction('POST', $this->action)
                ->params($this->params)
                ->submit();
        } catch (Exception $exception) {
            event(new FetcherExceptionOccurred(
                $exception,
                $station,
                $this->action,
                $this->params
            ));
//            $this->showExceptionInfo($exception);
            throw $exception;
        }

        // 取得注單資料並追加至原生注單收集器
        $rawTickets = array_get($response, 'response.page.datas');

        $arrData = array_get($response, 'response');

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
            array_walk($rawTickets, function (&$ticket, $key) use ($username) {

                // 捕魚遊戲
                if ($this->action === 'egame_betlog_histories') {
                    $client = array_get($ticket, 'client');
                    $gameRound = array_get($ticket, 'gameround');
                    $betNum = $gameRound . Carbon::parse(array_get($ticket, 'betTime'))->format('YmdHis');

                    $ticketSet = [
                        'client' => $client,
                        'betNum' => $betNum,
                        'gameRoundId' => $gameRound,
                        'gameType' => array_get($ticket, 'gameType'),
                        'betTime' => array_get($ticket, 'betTime'),
                        'betAmount' => array_get($ticket, 'betAmount'),
                        'validAmount' => array_get($ticket, 'validAmount'),
                        'winOrLoss' => array_get($ticket, 'winOrLoss'),
                        'state' => 0,
                    ];
                } else {
                    $ticketSet = [
                        'client' => array_get($ticket, 'client'),
                        'betNum' => array_get($ticket, 'betNum'),
                        'gameRoundId' => array_get($ticket, 'gameRoundId'),
                        'gameType' => array_get($ticket, 'gameType'),
                        'betTime' => array_get($ticket, 'betTime'),
                        'betAmount' => array_get($ticket, 'betAmount'),
                        'validAmount' => array_get($ticket, 'validAmount'),
                        'winOrLoss' => array_get($ticket, 'winOrLoss'),
                        'state' => array_get($ticket, 'state'),
                        'betType' => array_get($ticket, 'betType'),
                        'gameResult' => array_get($ticket, 'gameResult'),
                        'gameRoundEndTime' => array_get($ticket, 'gameRoundEndTime'),
                        'gameRoundStartTime' => array_get($ticket, 'gameRoundStartTime'),
                        'tableName' => array_get($ticket, 'tableName'),
                        'commission' => array_get($ticket, 'commission'),
                    ];
                }

                $rawTicketModel = new AllBetTicket($ticketSet);
                // 回傳套用原生注單模組後的資料(會產生 uuid)
                $ticket = $rawTicketModel->toArray();
                $ticket['uuid'] = $rawTicketModel->uuid->__toString();
                $ticket['username'] = $username ?? '';
            });

            $this->rawTickets = array_merge($this->rawTickets, $rawTickets);

            $allDatasCount = (int)array_get($arrData, 'page.count');
            $datasCount = (int)count((array)array_get($arrData, 'page.datas'));
            $lowerLimit = (int)(($this->pageIndex - 1) * $this->pageSize);

            // 如果還有注單，遞迴方式連續請求
            if (($lowerLimit + $datasCount) < $allDatasCount) {
                $this->params['Offset'] = array_get($arrData, 'Offset');
                $this->consoleOutput->writeln(sprintf("#%d 累積收集共 %d 筆，尚有注單，繼續查詢 %s" . PHP_EOL,
                        $this->step,
                        count($this->rawTickets),
                        $this->params['Offset'])
                );
                $this->params['pageIndex']++;

                return $this->curl($username);
            } else {
                $this->consoleOutput->writeln(sprintf("#%d 累積收集共 %d 筆，已無注單，停止程序" . PHP_EOL,
                        $this->step,
                        count($this->rawTickets))
                );
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
            $lastTicketsSet = DB::table('raw_tickets_all_bet')
                ->select('uuid', 'state')
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
            $fetchRawTicketState = array_get($fetchRawTicket, 'state');
            $lastTicketState = data_get($lastTicket, 'state');

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