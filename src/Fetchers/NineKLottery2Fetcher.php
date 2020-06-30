<?php

namespace SuperPlatform\UnitedTicket\Fetchers;


use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Models\NineKLottery2Ticket;

class NineKLottery2Fetcher extends Fetcher
{
    /**
     * 收集抓到的原始注單
     *
     * @var array
     */
    private $rawTickets = [];

    /**
     * 上層代理
     *
     * @var string
     */
    private $agentId = '';

    /**
     * api 參數
     *
     * @var array
     */
    private $params = [];

    /**
     * NineKLottery constructor.
     * @param null $secretKey
     */
    public function __construct(array $user)
    {
        parent::__construct();

        $this->agentId = config('api_caller.nine_k_lottery_2.config.agent_id');

        // 準備預設的 API 參數
        $this->params = [
            'BossID' => $this->agentId,
            'Page' => 1
        ];
    }

    public function __destruct()
    {
        unset($this->params);
        unset($this->agentId);
        unset($this->rawTickets);
    }

    /**
     * 設定查詢的區間（秒）
     *
     * 最多不能超過 7 天 (604800 秒)
     *
     * @param int $seconds
     * @return static
     */
    public function setTimeSpan(string $fromTime = '', string $toTime = ''): Fetcher
    {
        if (empty($fromTime) && empty($toTime)) {
            $this->params['StartTime'] = Carbon::parse($toTime)->subHour(1)->toDateTimeString();
            $this->params['EndTime'] = Carbon::parse($toTime)->toDateTimeString();
            return $this;
        }

        $this->params['StartTime'] = Carbon::parse($fromTime)->toDateTimeString();
        $this->params['EndTime'] = Carbon::parse($toTime)->toDateTimeString();

        return $this;
    }

    /**
     * 自動撈單的時間區間，結束時間需往前30分鐘才有辦法撈到未派彩的注單
     */
    public function autoFetchTimeSpan(): Fetcher
    {
        $this->params['StartTime'] = now()->subMinutes(30)->toDateTimeString();
        $this->params['EndTime'] = now()->addMinutes(30)->toDateTimeString();

        return $this;
    }

    /**
     * 抓取帳號注單
     *
     * @param string $username
     * @return array
     * @throws ApiCallerException
     * @throws \Exception
     */
    public function capture(): array
    {
        try {
            $captureBegin = microtime();

            $this->consoleOutput->writeln(join(PHP_EOL, [
                "=====================================",
                "  原生注單抓取程序啟動                  ",
                "-------------------------------------",
                "　　遊戲站: Nine K Lottery2            ",
                "　　　帳號: {$this->params['BossID']}",
                "　開始時間: {$this->params['StartTime']}",
                "　結束時間: {$this->params['EndTime']}",
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
        } catch (\Exception $exc) {
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
        $station = 'nine_k_lottery_2';
        $action = 'BetList';
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

        $tickets = array_get($arrData, 'response.data.BetList');
        $pageInfo = array_get($arrData, 'response.data.PageInfo');

        if (filled($tickets)) {
            // 因為如果是單一一張注單，需把它加到一個陣列中的元素，避免錯誤
            $tempArray = $tickets;

            if(!is_array(array_shift($tempArray))) {
                $tickets = [$tickets];
            };

            foreach ($tickets as $ticketSet) {
                $rawTicketModel = new NineKLottery2Ticket($ticketSet);

                // 回傳套用原生注單模組後的資料(會產生 uuid)
                $rawTickets = $rawTicketModel->toArray();
                $rawTickets['uuid'] = $rawTicketModel->uuid->__toString();

                array_push($this->rawTickets, $rawTickets);
            }
        }

        // 如果還有注單，遞迴方式連續請求
        $thisPage =  array_get($pageInfo, 'ThisPage');
        $totalPage =  array_get($pageInfo, 'TotalPage');

        if ($thisPage < $totalPage) {
            $this->consoleOutput->writeln(
                sprintf(
                    "#%d 累積收集共 %d 筆，尚有注單，繼續查詢",
                    $this->params['Page'],
                    count($this->rawTickets)
                )
            );

            $this->params['Page'] += 1;
            $this->curl();
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
            $lastTicketsSet = DB::table('raw_tickets_nine_k_lottery_2')
                ->select('uuid', 'Result')
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
            $fetchRawTicketState = array_get($fetchRawTicket, 'Result');
            $lastTicketState = data_get($lastTicket, 'Result');

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