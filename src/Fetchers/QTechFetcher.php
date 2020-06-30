<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Models\QTechTicket;

class QTechFetcher extends Fetcher
{
    /**
     * 收集抓到的原始注單
     *
     * @var array
     */
    private $rawTickets = [];

    /**
     * api 參數
     *
     * @var array
     */
    private $params = [];

    /**
     * QTech constructor.
     * @param null $secretKey
     */
    public function __construct(array $params)
    {
        parent::__construct();

        // 準備預設的 API 參數
        $this->params = [
            'size' => 1000,
        ];

        // 採用派彩時間執行方式，每天01:00執行一次
        if(!empty(array_get($params, 'rangeFilter'))) {
            $this->params['rangeFilter'] = array_get($params, 'rangeFilter');
        }
    }

    public function __destruct()
    {
        unset($this->params);
        unset($this->rawTickets);
    }

    /**
     * 設定查詢的區間
     *
     * @param string $sFromTime
     * @param string $sToTime
     * @return Fetcher
     */
    public function setTimeSpan(string $fromTime = '', string $toTime = ''): Fetcher
    {
        if (empty($fromTime) && empty($toTime)) {
            $this->params['from'] = Carbon::parse($toTime)->subHour(1)->format('Y-m-d\TH:i:s');
            $this->params['to'] = Carbon::parse($toTime)->format('Y-m-d\TH:i:s');
            return $this;
        }

        $this->params['from'] = Carbon::parse($fromTime)->format('Y-m-d\TH:i:s');
        $this->params['to'] = Carbon::parse($toTime)->format('Y-m-d\TH:i:s');

        return $this;
    }

    /**
     *  自動撈單的時間設定
     *
     * @return Fetcher
     */
    public function autoFetchTimeSpan(): Fetcher
    {
        $this->params['from'] = now()->subHours(2)->format('Y-m-d\TH:i:s');
        $this->params['to'] = now()->format('Y-m-d\TH:i:s');

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
                "　　遊戲站: Q Tech                    ",
                "　開始時間: {$this->params['from']}",
                "　結束時間: {$this->params['to']}",
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
        $station = 'q_tech';
        $action = 'game-rounds';
        try {
            $arrData = ApiCaller::make($station)
                ->methodAction('GET', $action)
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

        $tickets = array_get($arrData, 'response.items');

        if (filled($tickets)) {
            // 因為如果是單一一張注單，需把它加到一個陣列中的元素，避免錯誤
            $tempArray = $tickets;

            if(!is_array(array_shift($tempArray))) {
                $tickets = [$tickets];
            };

            foreach ($tickets as $ticketSet) {
                $rawTicketModel = new QTechTicket($ticketSet);

                // 回傳套用原生注單模組後的資料(會產生 uuid)
                $rawTickets = $rawTicketModel->toArray();
                $rawTickets['uuid'] = $rawTicketModel->uuid->__toString();

                array_push($this->rawTickets, $rawTickets);
            }
        }

        // 如果還有注單，遞迴方式連續請求
        $links = array_get($arrData, 'response.links');

        if (!empty($links)) {
            $this->consoleOutput->writeln(
                sprintf(
                    "累積收集共 %d 筆，尚有注單，繼續查詢",
                    count($this->rawTickets)
                )
            );

            $href = array_get(array_first($links), 'href');
            $query = array_get(explode('?', $href), 1);
            parse_str($query, $queryArray);

            $this->params['cursor'] = array_get($queryArray, 'cursor');
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
            $lastTicketsSet = DB::table('raw_tickets_q_tech')
                ->select('uuid', 'status')
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
            $fetchRawTicketState = array_get($fetchRawTicket, 'status');
            $lastTicketState = data_get($lastTicket, 'status');

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