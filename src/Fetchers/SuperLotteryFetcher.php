<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Models\SuperLotteryTicket;

class SuperLotteryFetcher extends Fetcher
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
    private $account = '';

    /**
     * @var string
     * 用戶密碼
     */
    private $passwd = '';

    /**
     * @var array
     * 遊戲列表
     */
    private $gameIDs = [];

    /**
     * SuperLotteryFetcher constructor.
     * @param null $secretKey
     */
    public function __construct(array $user)
    {
        parent::__construct();

        // 準備預設的 API 參數
        $this->params = [
//            'account' => config('api_caller.super_lottery.config.up_account'),
//            'passwd' => config('api_caller.super_lottery.config.up_password'),
            'account' => array_get($user, 'username'),
            'passwd' => array_get($user, 'password'),
        ];

        $this->gameIDs = array_keys(config('united_ticket.super_lottery.game_scopes'));
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
        if (empty($sToTime)) {
            $this->params['date'] = Carbon::now()->toDateString();

            return $this;
        }

        $this->params['date'] = Carbon::parse($sToTime)->toDateString();

        return $this;
    }

    /**
     * 自動撈單的時間區間
     */
    public function autoFetchTimeSpan(): Fetcher
    {
        $this->params['date'] = Carbon::now()->toDateString();

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
                "　　遊戲站: Super Lottery            ",
                "　　　帳號: {$this->params['account']}",
                "     日期: {$this->params['date']}",
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
            throw $exc;
        } catch (Exception $exc) {
            show_exception_message($exc);
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
        foreach ($this->gameIDs as $gameID) {
            $this->fetching($gameID);
        }

        return $this->rawTickets;
    }

    private function fetching($gameId)
    {
        $this->params['gameID'] = $gameId;
        $this->params['flags'] = 1;

        $station = 'super_lottery';
        $action = 'reportItem';
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
        $state = array_get($arrData, 'response.State');
        $tickets = array_get($arrData, 'response.Data');
        $name = array_get($arrData, 'response.Name');
        $lottery = array_get($arrData, 'response.Lottery');

        if (filled($tickets)) {
            // 因為如果是單一一張注單，需把它加到一個陣列中的元素，避免錯誤
            $tempArray = $tickets;

            if (!is_array(array_shift($tempArray))) {
                $tickets = [$tickets];
            };


            // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            //   重要！！每個遊戲站都要為自己處理這一塊
            //
            //   一定要為原生注單生產生主要識別碼，如果已經有唯一識別碼
            //   就直接使用，沒有的話就要用聯合鍵來產生
            // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            $rawTickets = [];

            foreach ($tickets as $ticketSet) {
                foreach (array_get($ticketSet, '5') as $ticket) {
                    $rawLotteryTicket = [];

                    $rawLotteryTicket['state'] = $state;
                    $rawLotteryTicket['name'] = $name;
                    $rawLotteryTicket['lottery'] = $lottery;
                    $rawLotteryTicket['bet_no'] = array_get($ticketSet, '0');
                    $rawLotteryTicket['bet_time'] = array_get($ticketSet, '1');
                    $rawLotteryTicket['account'] = array_get($ticketSet, '2');
                    $rawLotteryTicket['game_id'] = $gameId;
                    $rawLotteryTicket['game_type'] = array_get($ticketSet, '3');
                    $rawLotteryTicket['bet_type'] = array_get($ticketSet, '4');
                    $rawLotteryTicket['detail'] = array_get($ticket, '0');
                    $rawLotteryTicket['cmount'] = array_get($ticket, '1');
                    $rawLotteryTicket['gold'] = array_get($ticket, '2');
                    $rawLotteryTicket['odds'] = array_get($ticket, '3');
                    $rawLotteryTicket['retake'] = array_get($ticket, '4');
                    $rawLotteryTicket['status'] = array_get($ticket, '5');
                    $rawLotteryTicket['count_date'] = $this->params['date'];

                    $rawTicketModel = new SuperLotteryTicket($rawLotteryTicket);

                    // 回傳套用原生注單模組後的資料(會產生 uuid)
                    $rawTickets = $rawTicketModel->toArray();
                    $rawTickets['uuid'] = $rawTicketModel->uuid->__toString();

                    array_push($this->rawTickets, $rawTickets);
                }
            }
        }
    }

    /**
     * 設定會員總帳的起迄時間，只限制一天
     *
     * @param string $fromTime
     * @param string $toTime
     * @return $this
     */
    public function setRakeDate($fromTime = '', $toTime = '')
    {
        if (empty($toTime)) {
            $this->params['start_date'] = Carbon::now()->toDateString();
            $this->params['end_date'] = Carbon::now()->toDateString();

            return $this;
        }

        $this->params['start_date'] = Carbon::parse($toTime)->toDateString();
        $this->params['end_date'] = Carbon::parse($toTime)->toDateString();

        return $this;
    }

    public function rakeCapture()
    {
        try {
            $captureBegin = microtime();

            $this->consoleOutput->writeln(join(PHP_EOL, [
                "=====================================",
                "  原生總帳退水資料抓取程序啟動                  ",
                "-------------------------------------",
                "　　遊戲站: Super Lottery            ",
                "　　　帳號: {$this->params['account']}",
                "     日期: {$this->params['start_date']}",
                "--",
                ""
            ]));

            $station = 'super_lottery';
            $action = 'report';

            try {
                $arrData = ApiCaller::make($station)
                    ->methodAction('POST', $action)
                    ->params($this->params)
                    ->submit();
            } catch (\Exception $exception) {
                event(new FetcherExceptionOccurred(
                    $exception,
                    $station,
                    $action,
                    $this->params
                ));
                throw $exception;
            }

            // 取得原生資料
            $rakes = array_get($arrData, 'response.data');

            // 因為如果是單一一筆資料，需把它加到一個陣列中的元素，避免錯誤
            $tempArray = $rakes;

            if(!is_array(array_shift($tempArray))) {
                $rakes = [$rakes];
            };

            $this->consoleOutput->writeln(sprintf("累積收集共 %d 筆，停止程序" . PHP_EOL,
                    count($rakes))
            );

            $this->consoleOutput->writeln("--");
            $this->consoleOutput->writeln("　共花費 " . $this->microTimeDiff($captureBegin, microtime()) . ' 秒');
            $this->consoleOutput->writeln("=====================================");
            $this->consoleOutput->writeln("");

            return [
                'rakes' => $rakes
            ];

        } catch (ApiCallerException $exc) {
            show_exception_message($exc);
            throw $exc;
        } catch (\Exception $exc) {
            show_exception_message($exc);
            throw $exc;
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
            $lastTicketsSet = DB::table('raw_tickets_super_lottery')
                ->select('uuid', 'status', 'state')
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

            // 與上一次status狀態不同的單需要做轉換
            $fetchRawTicketStatus = array_get($fetchRawTicket, 'status');
            $lastTicketStatus = data_get($lastTicket, 'status');

            if ($fetchRawTicketStatus !== $lastTicketStatus) {
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