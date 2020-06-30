<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Models\SaGamingTicket;

/**
 * 「沙龍」原生注單抓取器
 *
 * @package SuperPlatform\UnitedTicket\Fetchers
 */
class SaGamingFetcher extends Fetcher
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
            'Username' => '',
            'FromTime' => '',
            'ToTime' => '',
            'Offset' => 0
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
        $this->params['FromTime'] = $sFromTime->toDateTimeString();
        $this->params['ToTime'] = $sToTime->toDateTimeString();

        return $this;
    }

    /**
     * 自動撈單的時間區間
     */
    public function autoFetchTimeSpan(): Fetcher
    {
        $this->params['FromTime'] = now()->subHours(1)->toDateTimeString();
        $this->params['ToTime'] = now()->toDateTimeString();


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
            $this->params['Username'] = $this->username;

            $captureBegin = microtime();

            $this->consoleOutput->writeln(join(PHP_EOL, [
                "=====================================",
                "  原生注單抓取程序啟動                  ",
                "-------------------------------------",
                "　　遊戲站: sa_gaming                  ",
                "　　　帳號: {$this->params['Username']}",
                "　開始時間: {$this->params['FromTime']}  ",
                "　結束時間: {$this->params['ToTime']}",
                "--",
                ""
            ]));

            // 以遞迴方式取出回應內容
            $this->rawTickets = $this->curl();

            $this->rawTickets = collect($this->rawTickets)->map(function ($rawTicket) {
                $rawTicket['HostName'] = json_encode($rawTicket['HostName']);
                $rawTicket['GameResult'] = json_encode($rawTicket['GameResult']);
                $rawTicket['Detail'] = json_encode($rawTicket['Detail']);

                return $rawTicket;
            })->toArray();

            $this->consoleOutput->writeln("--");
            $this->consoleOutput->writeln("　共花費 " . $this->microTimeDiff($captureBegin, microtime()) . ' 秒');
            $this->consoleOutput->writeln("=====================================");
            $this->consoleOutput->writeln("");

            // 回傳
            return [
                'tickets' => $this->rawTickets
                // 如果遊戲站有兩種格式以上的注單，就再自定義索引名補第二個
                // 'slot_tickets' => [],
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
     */
    private function curl()
    {
        $this->step++;

        $station = 'sa_gaming';
        $action = 'GetUserBetItemDV';
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

        $arrData = array_get($response, 'response');

        // 取得注單資料並追加至原生注單收集器
        $rawTickets = array_get($response, 'response.UserBetItemList.UserBetItem', []);

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
                $rawTicketModel = new SaGamingTicket($ticket);
                // 特殊處理: 因為沙龍的 username 沒有注單列表的裡面，要追加進去
                $rawTicketModel->Username = $this->params['Username'];
                // 回傳套用原生注單模組後的資料(會產生 uuid)
                $ticket = $rawTicketModel->toArray();
                $ticket['uuid'] = $rawTicketModel->uuid->__toString();
            });

            $this->rawTickets = array_merge($this->rawTickets, $rawTickets);

            // 如果還有注單，遞迴方式連續請求
            if ((string)array_get($arrData, 'More') === 'true') {
                $this->params['Offset'] = array_get($arrData, 'Offset');
                $this->consoleOutput->writeln(
                    sprintf(
                        "#%d 累積收集共 %d 筆，尚有注單，繼續查詢 %s",
                        $this->step,
                        count($this->rawTickets),
                        $this->params['Offset']
                    )
                );
                return $this->curl();
            } else {
                $this->consoleOutput->writeln(
                    sprintf(
                        "#%d 累積收集共 %d 筆，已無注單，停止程序",
                        $this->step,
                        count($this->rawTickets)
                    )
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
            $lastTicketsSet = DB::table('raw_tickets_sa_gaming')
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