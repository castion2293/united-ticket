<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use Carbon\Carbon;
use Exception;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Models\BingoTicket;


/**
 * 「沙龍」原生注單抓取器
 *
 * @package SuperPlatform\UnitedTicket\Fetchers
 */
class BingoFetcher extends Fetcher
{
    /**
     * 收集抓到的原始注單
     *
     * @var array
     */
    private $rawTickets = [];

    /**
     * 抓注單會用到的參數集
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
            'player_identify' => '',
            'created_at_begin' => '',
            'created_at_end' => '',
            'page_size' => 100,
            'page' => 1
        ];
//        $this->setTimeSpan();
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
        $this->params['created_at_begin'] = $sFromTime->toDateTimeString();
        $this->params['created_at_end'] = $sToTime->toDateTimeString();

        return $this;
    }

    /**
     * 自動撈單的時間區間
     */
    public function autoFetchTimeSpan(): Fetcher
    {
        $this->params['created_at_begin'] = now()->subDays(1)->toDateTimeString();
        $this->params['created_at_end'] = now()->toDateTimeString();

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
            $this->params['player_identify'] = $this->username;

            $captureBegin = microtime();

            $this->consoleOutput->writeln(join(PHP_EOL, [
                "=====================================",
                "  原生注單抓取程序啟動                  ",
                "-------------------------------------",
                "　　遊戲站: Bingo                  ",
                "　　　帳號: {$this->params['player_identify']}",
                "　開始時間: {$this->params['created_at_begin']}  ",
                "　結束時間: {$this->params['created_at_end']}",
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

            $this->rawTickets = collect($this->rawTickets)->map(function ($rawTicket) {
                $rawTicket['player'] = json_encode($rawTicket['player']);
                $rawTicket['results'] = json_encode($rawTicket['results']);
                $rawTicket['history'] = json_encode($rawTicket['history']);

                return $rawTicket;
            })->toArray();

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

        array_forget($this->params, 'FromTime');
        array_forget($this->params, 'ToTime');

        $station = 'bingo';
        $action = 'tickets';

        try {
            $arrData = ApiCaller::make($station)->methodAction('get', $action
            // 路由參數這邊設定
            )->params(
            // 一般參數這邊設定
                $this->params
            )->submit();
        } catch (Exception $exception) {
            event(new FetcherExceptionOccurred(
                $exception,
                $station,
                $action,
                $this->params
            ));
//            $this->showExceptionInfo($exception);
            throw $exception;
        }

        // 取得注單資料並追加至原生注單收集器
        $rawTickets = array_get($arrData, 'response.data', []);

        $currentPage = array_get($arrData, 'response.current_page');
        $lastPage = array_get($arrData, 'response.last_page');

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
                $rawTicketModel = new BingoTicket($ticket);
                // 特殊處理: 因為賓果的 account 沒有注單列表的裡面，要追加進去
                $rawTicketModel->account = $this->params['player_identify'];
                $rawTicketModel->bet_at = $ticket['created_at'];
                $rawTicketModel->adjust_at = $ticket['updated_at'];
                // 回傳套用原生注單模組後的資料(會產生 uuid)
                $ticket = $rawTicketModel->toArray();
                $ticket['uuid'] = $rawTicketModel->uuid->__toString();
            });

            $this->rawTickets = array_merge($this->rawTickets, $rawTickets);

            // 如果還有注單，遞迴方式連續請求
            if ($currentPage < $lastPage) {
                $this->params['page'] = ++$this->params['page'];
                $this->consoleOutput->writeln(sprintf("#%d 累積收集共 %d 筆，尚有注單，繼續查詢 第%s頁" . PHP_EOL,
                        $this->step,
                        count($this->rawTickets),
                        $this->params['page'])
                );
                $this->curl();
            }
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

}