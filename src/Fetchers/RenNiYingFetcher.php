<?php


namespace SuperPlatform\UnitedTicket\Fetchers;


use Exception;
use Illuminate\Support\Facades\DB;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Models\RenNiYingTicket;
use function Symfony\Component\Debug\Tests\testHeader;

class RenNiYingFetcher extends Fetcher
{
    /**
     * 收集抓到的原始注單
     *
     * @var array
     */
    private $rawTickets = [];

    /**
     * 抓注單 LedgerQuery 會用到的參數集
     *
     * @var array
     */
    private $params;

    /**
     * 上層代理
     *
     * @var string
     */
    private $agentId = '';

    /**
     * 注單時間間隔
     *
     * @var int
     */
    private $range = 0;

    /**
     * 注單撈取時間區間
     *
     * @var string
     */
    private $start = '';
    private $end = '';

    /**
     * RenNiYing constructor.
     * @param null $secretKey
     */
    public function __construct(array $user)
    {
        parent::__construct();

        $this->agentId = config('api_caller.ren_ni_ying.config.agent_id');
        $this->range = config('api_caller.ren_ni_ying.config.ticket_time_range');

        // 準備預設的 API 參數
        $this->params = [
            'pageIdx' => 1,
            'pageSize' => 1000,
            'status' => 9,
            'queryUserId' => $this->agentId,
        ];
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
        // 如果有指定時間，就使用指定範圍時間(使用在手動撈注單)
        if (!empty($sFromTime) && !empty($sToTime)) {
            $this->params['startDate'] = $this->start = $sFromTime;
            $this->params['endDate'] = $this->end = $sToTime;

            return $this;
        }

        // 如果沒有就使用recent 最近幾分鐘 (使用在自動撈注單)
        $this->params['recent'] = $this->range;

        $this->start = now()->subMinutes($this->range)->toDateTimeString();
        $this->end = now()->toDateTimeString();

        return $this;
    }

    public function autoFetchTimeSpan(): Fetcher
    {
        $this->params['recent'] = $this->range;

        $this->start = now()->subMinutes($this->range)->toDateTimeString();
        $this->end = now()->toDateTimeString();

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
                "　　遊戲站: Ren Ni Ying            ",
                "　　　帳號: {$this->agentId}",
                "　開始時間: {$this->start}",
                "　結束時間: {$this->end}",
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
        $station = 'ren_ni_ying';
        $action = 'ledgerQuery';
        try {
            $arrData = ApiCaller::make($station)
                ->methodAction('GET', $action)
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

        $tickets = array_get($arrData, 'response.data.items');
        $pageIdx = array_get($arrData, 'response.data.pageIdx');
        $pageCount = array_get($arrData, 'response.data.pageCount');

        if (filled($tickets)) {
            // 因為如果是單一一張注單，需把它加到一個陣列中的元素，避免錯誤
            $tempArray = $tickets;

            if (!is_array(array_shift($tempArray))) {
                $tickets = [$tickets];
            };

            $rawTickets = [];

            foreach ($tickets as $ticketSet) {
                $rawTicketSet = [];

                $rawTicketSet['id'] = array_get($ticketSet, 'id');
                $rawTicketSet['status'] = array_get($ticketSet, 'status');
                $rawTicketSet['userId'] = array_get($ticketSet, 'userId');
                $rawTicketSet['created'] = array_get($ticketSet, 'created');
                $rawTicketSet['gameId'] = array_get($ticketSet, 'gameId');
                $rawTicketSet['roundId'] = array_get($ticketSet, 'roundId');
                $rawTicketSet['place'] = array_get($ticketSet, 'slot.place');
                $rawTicketSet['guess'] = array_get($ticketSet, 'slot.guess');
                $rawTicketSet['odds'] = array_get($ticketSet, 'slot.odds');
                $rawTicketSet['money'] = array_get($ticketSet, 'settlement.money');
                $rawTicketSet['result'] = array_get($ticketSet, 'settlement.result');
                $rawTicketSet['playerRebate'] = array_get($ticketSet, 'settlement.playerRebate');

                $rawTicketModel = new RenNiYingTicket($rawTicketSet);

                // 回傳套用原生注單模組後的資料(會產生 uuid)
                $rawTickets = $rawTicketModel->toArray();
                $rawTickets['uuid'] = $rawTicketModel->uuid->__toString();

                array_push($this->rawTickets, $rawTickets);
            }
        }

        // 如果還有注單，遞迴方式連續請求
        if ($pageIdx < $pageCount) {
            $this->consoleOutput->writeln(
                sprintf(
                    "#%d 累積收集共 %d 筆，尚有注單，繼續查詢",
                    $this->params['pageIdx'],
                    count($this->rawTickets)
                )
            );

            $this->params['pageIdx'] += 1;
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
            $lastTicketsSet = DB::table('raw_tickets_ren_ni_ying')
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