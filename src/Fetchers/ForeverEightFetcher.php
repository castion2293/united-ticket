<?php


namespace SuperPlatform\UnitedTicket\Fetchers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Models\ForeverEightTicket;

class ForeverEightFetcher extends Fetcher
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

    protected $totalPage;

    /**
     * Forever Eight constructor.
     * @param null $secretKey
     */
    public function __construct(array $params)
    {
        parent::__construct();

        // 準備預設的 API 參數
        $this->params = [
            'Start' => '',
            'End' => '',
            'PageNum' => 1,
        ];

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
            $this->params['Start'] = Carbon::parse($toTime)->subHour(1)->toDateTimeString();
            $this->params['End'] = Carbon::parse($toTime)->toDateTimeString();
            return $this;
        }

        $this->params['Start'] = Carbon::parse($fromTime)->toDateTimeString();
        $this->params['End'] = Carbon::parse($toTime)->toDateTimeString();

        return $this;
    }

    /**
     *  自動撈單的時間設定
     *
     * @return Fetcher
     */
    public function autoFetchTimeSpan(): Fetcher
    {
        $this->params['Start'] = now()->subMinutes(30)->toDateTimeString();
        // 因AV電子的「退款單」需延遲一分鐘才會正確
        $this->params['End'] = now()->subMinutes(1)->toDateTimeString();

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
                "　　遊戲站: Forever Eight              ",
                "　開始時間: {$this->params['Start']}",
                "　結束時間: {$this->params['End']}",
                "--",
                ""
            ]));

            // 尋找頁數
            try {
                $DataPage = ApiCaller::make('forever_eight')->methodAction('post', 'GET_PAGES_DETAIL_WITH_DATE')
                    // 路由參數這邊設定
                    ->params($this->params)
                    ->submit();
                $this->totalPage = array_get($DataPage, 'response.Data.pages');

            } catch (\Exception $exception) {
                event(new FetcherExceptionOccurred(
                    $exception,
                    'forever_eight',
                    'GET_PAGES_DETAIL_WITH_DATE',
                    $this->params
                ));
                throw $exception;
            }

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
     * @throws \Exception
     */
    private function curl()
    {
        $station = 'forever_eight';
        $action = 'GET_RECORDS_WITH_DATE_ON_PAGE';
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

        $tickets = array_get($arrData, 'response.Data');

        if (filled($tickets)) {
            // 因為如果是單一一張注單，需把它加到一個陣列中的元素，避免錯誤
            $tempArray = $tickets;

            if(!is_array(array_shift($tempArray))) {
                $tickets = [$tickets];
            };

            foreach ($tickets as $ticketSet) {
                $rawTicketModel = new ForeverEightTicket($ticketSet);

                // 回傳套用原生注單模組後的資料(會產生 uuid)
                $rawTickets = $rawTicketModel->toArray();
                $rawTickets['uuid'] = $rawTicketModel->uuid->__toString();

                array_push($this->rawTickets, $rawTickets);
            }
        }

        // 如果還有注單，遞迴方式連續請求

        // API返回的頁數 > 1
        $isMoreThanOnePage = ($this->totalPage > 1);
        // 目前帶入參數頁數 <= API返回的頁數
        $isLessThanLastPast = ($this->params['PageNum'] <= $this->totalPage);

        if ($isMoreThanOnePage && $isLessThanLastPast) {
            $this->consoleOutput->writeln(
                sprintf(
                    "#%d 累積收集共 %d 筆，尚有注單，繼續查詢",
                    $this->params['PageNum'],
                    count($this->rawTickets)
                )
            );

            $this->params['PageNum'] += 1;
            sleep(5);
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
            $lastTicketsSet = DB::table('raw_tickets_forever_eight')
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