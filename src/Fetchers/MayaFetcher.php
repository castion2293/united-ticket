<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Models\MayaTicket;

/**
 * 「碼雅」原生注單抓取器
 *
 * @package SuperPlatform\UnitedTicket\Fetchers
 */
class MayaFetcher extends Fetcher
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
     * @var int
     * 已抓取的注單數量
     */
    private $fetch_count = 0;

    /**
     * @var string
     * 用戶名稱
     */
    private $username = '';

    /**
     * @var array
     * 遊戲列表
     */
    private $GameIds = [];

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
            // 一般參數這邊設定
            'VenderNo' => config('united_ticket.maya.vendorId'),
            'GameMemberID' => $this->gameMemberId(),
            'GameID' => '',
            'StartDateTime' => '',
            'EndDateTime' => '',
            'PageSize' => 500,
            'CurrentPage' => 1,
            'LanguageNo' => 'zh_tw'
        ];

        $this->GameIds = array_keys(config('united_ticket.maya.game_type'));
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
        $this->params['StartDateTime'] = $sFromTime->format('YmdHis');
        $this->params['EndDateTime'] = $sToTime->format('YmdHis');

        return $this;
    }

    /**
     * 自動撈單的時間區間
     */
    public function autoFetchTimeSpan(): Fetcher
    {
        $this->params['StartDateTime'] = now()->subDays(1)->format('YmdHis');
        $this->params['EndDateTime'] = now()->format('YmdHis');

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
            $captureBegin = microtime();

            $this->consoleOutput->writeln(join(PHP_EOL, [
                "=====================================",
                "  原生注單抓取程序啟動                  ",
                "-------------------------------------",
                "　　遊戲站: maya                  ",
                "　　　帳號: {$this->username}",
                "　開始時間: {$this->params['StartDateTime']}  ",
                "　結束時間: {$this->params['EndDateTime']}",
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
//        foreach($this->GameIds as $gameId) {
        $this->fetching(0);
//        }
    }

    private function fetching($gameId)
    {
        $this->params['GameID'] = $gameId;

        $station = 'maya';
        $action = 'GetGameDetailForMember';
        try {
            $arrData = ApiCaller::make($station)
                ->methodAction('get', $action)
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
        $rawTickets = array_get($arrData, 'response.GameDetailList', []);

        // 取得每一筆的總注單數
        $ticket_count = array_get($arrData, 'response.CountData.Count');

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
                $rawTicketModel = new MayaTicket($ticket);
                // 特殊處理: 因為沙龍的 username 沒有注單列表的裡面，要追加進去
                $rawTicketModel->GameType = array_get($ticket, 'GameID');
                $rawTicketModel->Username = $this->username;
                // 回傳套用原生注單模組後的資料(會產生 uuid)
                $ticket = $rawTicketModel->toArray();
                $ticket['uuid'] = $rawTicketModel->uuid->__toString();
            });

            $this->rawTickets = array_merge($this->rawTickets, $rawTickets);

            $this->fetch_count += count($rawTickets);

            $this->consoleOutput->writeln(sprintf("會員: %s 遊戲名稱: %s 本頁有 %d 筆 累積收集共 %d 筆",
                $this->username,
                $this->params['GameID'],
                count($rawTickets),
                count($this->rawTickets)
            ));

            // 如果還有其他頁的注單，就再進行下一頁的注單抓取
            if ($this->fetch_count < $ticket_count) {
                $this->params['CurrentPage'] = ++$this->params['CurrentPage'];

                $this->consoleOutput->writeln(sprintf("尚有注單，繼續查詢 第%s頁",
                    $this->params['CurrentPage']
                ));
//
                $this->fetching($gameId);
            } else {
                $this->fetch_count = 0;
                $this->params['CurrentPage'] = 1;
            }
        }

        return $this->rawTickets;
    }

    /**
     * @param $username
     * @return mixed
     * 抓住單API須先取得GameMemberId
     */
    private function gameMemberId()
    {
        $station = 'maya';
        $action = 'GetGameMemberID';
        $params = [
            // 一般參數這邊設定
            'VenderNo' => config('united_ticket.maya.vendorId'),
            'VenderMemberID' => $this->username,
        ];

        try {
            $memberId = ApiCaller::make($station)->methodAction('get', $action, [
                // 路由參數這邊設定
            ])->params($params)->submit()['response']['GameMemberID'];
        } catch (Exception $exception) {
            event(new FetcherExceptionOccurred(
                $exception,
                $station,
                $action,
                $params
            ));
            throw $exception;
        }

        return $memberId;
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
            $lastTicketsSet = DB::table('raw_tickets_maya')
                ->select('uuid', 'State')
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
            $fetchRawTicketState = array_get($fetchRawTicket, 'State');
            $lastTicketState = data_get($lastTicket, 'State');

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