<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use Carbon\Carbon;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Models\UfaSportTicket;

class UfaSportFetcher extends Fetcher
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
     * SaGamingFetcher constructor.
     * @param null $secretKey
     */
    public function __construct(array $user)
    {
        parent::__construct();

        $this->username = array_get($user, 'username');

        // 準備預設的 API 參數
        $this->params = [
            'secret' => config("api_caller.ufa_sport.config.secret_code"),
            'agent' => config("api_caller.ufa_sport.config.agent"),
        ];
        //$this->setTimeSpan();
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
     * @param string $fromTime
     * @param string $toTime
     * @return $this
     */
    public function setTimeSpan(string $fromTime = '', string $toTime = ''): Fetcher
    {
        return $this;
    }

    /**
     * 自動撈單的時間區間
     */
    public function autoFetchTimeSpan(): Fetcher
    {
        return $this;
    }

    /**
     * @return array
     * @throws ApiCallerException
     */
    public function capture(): array
    {
        try {
            $captureBegin = microtime();

            $this->consoleOutput->writeln(
                join(PHP_EOL, [
                    "=====================================",
                    "  原生注單抓取程序啟動                  ",
                    "-------------------------------------",
                    "　　遊戲站: UFA Sport                  ",
                    "--",
                    ""
                ]));

            // 以遞迴方式取出回應內容
            $this->curl();

            $this->consoleOutput->writeln(sprintf("累積收集共 %d 筆，已無注單，停止程序" . PHP_EOL,
                    count($this->rawTickets))
            );

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
        } catch (\Exception $exc) {
            show_exception_message($exc);
//            $this->showExceptionInfo($exc);
            throw $exc;
        }
    }

    /**
     * 遞迴式 CURL 請求
     * @return array|mixed
     * @throws \Exception
     */
    private function curl()
    {
        $this->step++;

        $station = 'ufa_sport';
        $action = 'ticket';

        try {
            $arrData = ApiCaller::make('ufa_sport')
                ->methodAction('get', 'fetch', $this->params)
                ->params([
                ])
                ->submit();
        } catch (\Exception $exception) {
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
        $originalTickets = $tickets = array_get($arrData, 'response.result.ticket');
        if (!empty($originalTickets)) {

            // 因為如果是單一一張注單，需把它加到一個陣列中的元素，避免錯誤
            $isOnlyOneTicket = array_has($originalTickets, 'id');
            if ($isOnlyOneTicket) {
                $tickets = [$originalTickets];
            }

            // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            //   重要！！每個遊戲站都要為自己處理這一塊
            //
            //   一定要為原生注單生產生主要識別碼，如果已經有唯一識別碼
            //   就直接使用，沒有的話就要用聯合鍵來產生
            // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

            foreach ($tickets as $aTicket) {
                $oRawTicketModel = new UfaSportTicket($aTicket);

                $aTicketArray = [];
                // 回傳套用原生注單模組後的資料 (會產生 uuid)
                $aTicketArray = $oRawTicketModel->toArray();
                $aTicketArray['uuid'] = $oRawTicketModel->uuid->__toString();

                // runscore 進行中的比分
                $aTicketArray['runscore'] = json_encode(array_get($aTicket, 'runscore'));

                // score 比賽分數 (赛事结束了才会显示)
                $aTicketArray['score'] = json_encode(array_get($aTicket, 'score'));

                // 注單是 “Half time score” + 赛事完毕 = <htscore> 不會是空的
                $aTicketArray['htscore'] = json_encode(array_get($aTicket, 'htscore'));


                // 注單是 “First/last goal” + 赛事完毕 = <flg> 不會是空的
                $aTicketArray['flg'] = json_encode(array_get($aTicket, 'flg'));

                $aTicketArray['info'] = json_encode(array_get($aTicket, 'info'));

                $aTicketArray['side'] = json_encode(array_get($aTicket, 'side'));

                // 注單是 “盤口” + 赛事完毕 = <oodstype> 不會是空的
                $aTicketArray['oddstype'] = json_encode(array_get($aTicket, 'oddstype'));

                array_push($this->rawTickets, $aTicketArray);
            }

        }
        return $this->rawTickets;
    }

    /**
     * 找出需轉換的注單，UFA每次都會撈最新的單，所以不需要做比較
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