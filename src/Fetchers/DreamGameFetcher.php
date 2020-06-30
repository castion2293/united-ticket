<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use Carbon\Carbon;
use Exception;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Models\DreamGameTicket;

class DreamGameFetcher extends Fetcher
{
    /**
     * 收集抓到的原始注單
     *
     * @var array
     */
    private $rawTickets = [];

    /**
     * 記錄 Request 次數
     *
     * @var int
     */
    private $step = 0;

    /**
     * 抓注單 GetUserBetItemDV 會用到的參數集
     *
     * @var array
     */
    private $params;

    /**
     * 撈單的方法
     *
     * @var string
     */
    private $action = '';

    /**
     * 路由參數
     *
     * @var array
     */
    private $routeParams = [];

    /**
     * 一般參數
     *
     * @var array
     */
    private $formParams = [];

    /**
     * SaGamingFetcher constructor.
     * @param null $secretKey
     */
    public function __construct(array $user)
    {
        parent::__construct();

        $this->username = array_get($user, 'username');
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
        $this->action = 'game/getReport/';

        if (empty($sFromTime) && empty($sToTime)) {
            $this->formParams['beginTime'] = Carbon::parse($sFromTime)->subHour(1)->toDateTimeString();
            $this->formParams['endTime'] = Carbon::parse($sToTime)->toDateTimeString();
            return $this;
        }

        $this->formParams['beginTime'] = Carbon::parse($sFromTime)->toDateTimeString();
        $this->formParams['endTime'] = Carbon::parse($sToTime)->toDateTimeString();

        return $this;
    }

    /**
     * 自動撈單的時間區間
     */
    public function autoFetchTimeSpan(): Fetcher
    {
        $this->action = 'game/getReport/{agent}';

        $this->routeParams = [
            'agent' => config('api_caller.dream_game.config.api_agent'),
        ];

        return $this;
    }

    public function setPageIndex($pageIndex)
    {
        $this->pageIndex = $pageIndex;
        return $this->pageIndex;
    }

    public function setPageSize($pageSize)
    {
        $this->pageSize = $pageSize;
        return $this->pageSize;
    }

    public function capture(): array
    {
        try {
            $captureBegin = microtime();

            $startTime= array_get($this->formParams, 'beginTime', '');
            $endTime= array_get($this->formParams, 'endTime', '');

            $this->consoleOutput->writeln(
                join(PHP_EOL, [
                    "=====================================",
                    "  原生注單抓取程序啟動                  ",
                    "-------------------------------------",
                    "　　遊戲站: Dream Game                    ",
                    "　  開始時間: {$startTime}",
                    "　  結束時間: {$endTime}",
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
                'tickets' => $this->rawTickets,
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
//            $this->showExceptionInfo($exc);
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

        $station = 'dream_game';

        try {
            $arrData = ApiCaller::make('dream_game')
                ->methodAction('POST', $this->action, $this->routeParams)
                ->params($this->formParams)
                ->submit();
        } catch (Exception $exception) {
            $errorCode = '0';
            if (method_exists($exception, 'response')) {
                $errorCode = array_get($exception->response(), 'errorCode');
            }
            // 405 訪問太頻繁不需要噴出錯誤
            $isNotFrequencyError = ($errorCode !== '405');
            if ($isNotFrequencyError) {
                event(new FetcherExceptionOccurred(
                    $exception,
                    $station,
                    $this->action,
                    array_merge($this->formParams, $this->routeParams)
                ));
            }
//            $this->showExceptionInfo($exception);
            throw $exception;
        }

        // 取得注單資料並追加至原生注單收集器
        if ($this->action === 'game/getReport/{agent}') {
            $rawTickets = array_get($arrData, 'response.list');
        } else {
            $rawTickets = array_get($arrData, 'response.data.records');
        }

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

//                // 如果有改單把parentBetId 改成 id
//                if (array_has($ticket, 'parentBetId')) {
//                    $ticket['id'] = $ticket['parentBetId'];
//                    array_forget($ticket, 'parentBetId');
//                }

                $rawTicketModel = new DreamGameTicket($ticket);
                // 回傳套用原生注單模組後的資料(會產生 uuid)
                $ticket = $rawTicketModel->toArray();
                $ticket['uuid'] = $rawTicketModel->uuid->__toString();
            });

            $this->rawTickets = array_merge($this->rawTickets, $rawTickets);

            // 如果還有注單，遞迴方式連續請求
//            if (($lowerLimit + $datasCount) < $allDatasCount) {
//                $this->params['Offset'] = array_get($arrData, 'Offset');
//                $this->consoleOutput->writeln(sprintf("#%d 累積收集共 %d 筆，尚有注單，繼續查詢 %s" . PHP_EOL,
//                        $this->step,
//                        count($this->rawTickets),
//                        $this->params['Offset'])
//                );
//                return $this->curl();
//            } else {
//                $this->consoleOutput->writeln(sprintf("#%d 累積收集共 %d 筆，已無注單，停止程序" . PHP_EOL,
//                        $this->step,
//                        count($this->rawTickets))
//                );
//            }

            if (count($this->rawTickets) == 1000) {
                $this->consoleOutput->writeln(sprintf("#%d 收集共 %d 筆，尚有注單" . PHP_EOL,
                        $this->step,
                        count($this->rawTickets))
                );
            } else {
                $this->consoleOutput->writeln(sprintf("#%d 收集共 %d 筆，已無注單，停止程序" . PHP_EOL,
                        $this->step,
                        count($this->rawTickets))
                );
            }
        }

        return $this->rawTickets;
    }

    /**
     * 找出需轉換的注單，DG每次都會撈最新的單，所以不需要做比較
     *
     * @param array $fetchTickets
     * @return array
     */
    public function compare(array $fetchRawTickets): array
    {
        $this->consoleOutput->writeln(sprintf("真正需轉換的筆數 %d 筆" . PHP_EOL,
                count($fetchRawTickets))
        );

        return [
            'tickets' => $fetchRawTickets
        ];
    }
}