<?php


namespace SuperPlatform\UnitedTicket\Fetchers;

use Exception;
use Illuminate\Support\Carbon;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Models\HabaneroTicket;

/**
 * 「HB電子」原生注單抓取器
 *
 * @package SuperPlatform\UnitedTicket\Fetchers
 */
class HabaneroFetcher extends Fetcher
{
    /**
     * 收集抓到的原始注單
     *
     * @var array
     */
    private $rawTickets = [];

    /**
     * 抓注單 GetPlayerGameResults 會用到的參數集
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

//    /**
//     * @var string
//     * 用戶名
//     */
//    private $username = '';

    /**
     * SaGamingFetcher constructor.
     * @param null $secretKey
     */
    public function __construct(array $user)
    {
        parent::__construct();

//        $this->username = array_get($user, 'username');

        // 準備預設的 API 參數
        $this->params = [
            'BrandId' => config('api_caller.habanero.config.brand_ID'),
            'DtStartUTC' => '',
            'DtEndUTC' => '',
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
     * @param string $sDtStartUTC
     * @param string $sDtEndUTC
     * @return Fetcher
     */
    public function setTimeSpan(string $sDtStartUTC = '', string $sDtEndUTC = ''): Fetcher
    {
        // 設定查詢的時間範圍
        if (empty($sDtStartUTC) && empty($sDtEndUTC)) {
            $this->params['DtStartUTC'] = Carbon::parse($sDtStartUTC)->timezone('Europe/London')->subMinutes(30)->format('YmdHis');
            $this->params['DtEndUTC'] = Carbon::parse($sDtEndUTC)->timezone('Europe/London')->format('YmdHis');
            return $this;
        }

        $this->params['DtStartUTC'] = Carbon::parse($sDtStartUTC)->timezone('Europe/London')->format('YmdHis');
        $this->params['DtEndUTC'] = Carbon::parse($sDtEndUTC)->timezone('Europe/London')->format('YmdHis');

        return $this;
    }

    /**
     * 自動撈單的時間區間
     */
    public function autoFetchTimeSpan(): Fetcher
    {
        $this->params['DtStartUTC'] = now()->timezone('Europe/London')->subMinutes(90)->format('YmdHis');
        $this->params['DtEndUTC'] = now()->timezone('Europe/London')->format('YmdHis');


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
//            $this->params['Username'] = $this->username;

            $captureBegin = microtime();

            $startTime = Carbon::parse($this->params['DtStartUTC'])->addHours(8)->toDateTimeString();
            $endTime = Carbon::parse($this->params['DtEndUTC'])->addHours(8)->toDateTimeString();
            $this->consoleOutput->writeln(join(PHP_EOL, [
                "=====================================",
                "  原生注單抓取程序啟動                  ",
                "-------------------------------------",
                "　　遊戲站: HB電子                  ",
                "　開始時間: {$startTime}  ",
                "　結束時間: {$endTime}",
                "--",
                ""
            ]));

            // 以遞迴方式取出回應內容
            $this->rawTickets = $this->curl();

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
     * @throws Exception
     */
    private function curl()
    {
        $this->step++;

        $station = 'habanero';
        $action = 'GetBrandCompletedGameResultsV2';
        try {
            $response = ApiCaller::make($station)
                ->methodAction('post', $action)
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
        $rawTickets = array_get($response, 'response', []);

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
                $rawTicketModel = new HabaneroTicket($ticket);
                // 回傳套用原生注單模組後的資料(會產生 uuid)
                $ticket = $rawTicketModel->toArray();
                $ticket['uuid'] = $rawTicketModel->uuid->__toString();
            });

            $this->rawTickets = array_merge($this->rawTickets, $rawTickets);
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
            $lastTicketsSet = DB::table('raw_tickets_habanero')
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