<?php
//
//namespace SuperPlatform\UnitedTicket\Fetchers;
//
//use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
//use SuperPlatform\ApiCaller\Facades\ApiCaller;
//use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
//use SuperPlatform\UnitedTicket\Models\HongChowTicket;
//
///**
// * Class HongChowFetcher
// * @package SuperPlatform\UnitedTicket\Fetchers
// */
//class HongChowFetcher extends Fetcher
//{
//    /**
//     * 收集抓到的原始注單
//     * @var array
//     */
//    private $rawTickets = [];
//
//    /**
//     * 抓注單 GetUserBetItemDV 會用到的參數集
//     * @var array
//     */
//    private $params = [];
//
//    /**
//     * 記錄 Request 次數
//     * @var int
//     */
//    private $step = 0;
//
//    /**
//     * @var string
//     * 用戶名
//     */
//    private $username = '';
//
//    private $syncVersion = null;
//
//    /**
//     * HongChowFetcher constructor.
//     * @param array $user
//     */
//    public function __construct(array $user)
//    {
//        parent::__construct();
//
//        $this->username = array_get($user, 'username');
//
//        // 取得 sync version
//        $this->syncVersion = session('hong_chow_sync_version', 1);
//
//        // 準備預設的 API 參數
//        $this->params = [
//            'start_sync_version' => $this->syncVersion,
//        ];
//    }
//
//    /**
//     * 設定查詢的區間
//     * @param string $fromTime
//     * @param string $toTime
//     * @return HongChowFetcher
//     */
//    public function setTimeSpan($fromTime = '', $toTime = '')
//    {
//        return $this;
//    }
//
//    /**
//     * 抓取帳號注單
//     * @return array
//     * @throws ApiCallerException
//     * @throws \Exception
//     */
//    public function capture()
//    {
//        try {
//            $captureBegin = microtime();
//
//            $this->consoleOutput->writeln(
//                join(PHP_EOL, [
//                    "=====================================",
//                    "  原生注單抓取程序啟動                  ",
//                    "-------------------------------------",
//                    "　　遊戲站: Hong Chow                  ",
//                    "--",
//                    ""
//                ]));
//
//            $this->curl();
//
//            $this->consoleOutput->writeln(
//                join(PHP_EOL, [
//                    "--",
//                    "　共花費 " . $this->microTimeDiff($captureBegin, microtime()) . ' 秒',
//                    "=====================================",
//                    '',
//                ]));
//
//            // 回傳
//            return [
//                'tickets' => $this->rawTickets,
//                'sync_version' => $this->syncVersion,
//            ];
//
//        } catch (ApiCallerException $exc) {
//            show_exception_message($exc);
//            throw $exc;
//        } catch (\Exception $exc) {
//            show_exception_message($exc);
//            throw $exc;
//        }
//    }
//
//    /**
//     * CURL 請求
//     * @throws \Exception
//     */
//    private function curl()
//    {
//        $this->step++;
//
//        $station = 'hong_chow';
//        $action = 'bets';
//
//        try {
//            $aReturnData = ApiCaller::make($station)
//                ->methodAction('POST', $action, [])
//                ->params($this->params)
//                ->submit();
//        } catch (\Exception $exception) {
//            event(new FetcherExceptionOccurred(
//                $exception,
//                $station,
//                $action,
//                $this->params
//            ));
//            throw $exception;
//        }
//
//        // 取得注單資料並追加至原生注單收集器
//        $aRawTickets = array_get($aReturnData, 'response.data.data');
//
//        // 檢查 $aRawTickets 是否為空
//        if (filled($aRawTickets)) {
//            /**
//             * TODO: 重要!!! 每個遊戲站都要為自己處理這一塊!!!
//             *
//             * 一定要為原生注單生產生主要識別碼
//             * 如果已經有唯一識別碼就直接使用
//             * 沒有的話就要用聯合鍵來產生
//             */
//            $aTemp = [];
//
//            foreach ($aRawTickets as $aTicket) {
//                if (!array_has($aTicket, 'details')) {
//                    $oRawTicketModel = new HongChowTicket($aTicket);
//                    // 回傳套用原生注單模組後的資料 (會產生 uuid)
//                    $aTicket = $oRawTicketModel->toArray();
//                    $aTicket['uuid'] = $oRawTicketModel->uuid->__toString();
//
//                    $aTemp[] = $aTicket;
//                } else {
//                    $aTicketDetails = array_get($aTicket, 'details');
//                    array_forget($aTicket, 'details');
//
//                    foreach ($aTicketDetails as $aDetail) {
//                        $aDetail['part_id'] = null;
//                        $aSubTicket = array_merge($aTicket, $aDetail);
//                        $oRawTicketModel = new HongChowTicket($aSubTicket);
//                        $aSubTicket = $oRawTicketModel->toArray();
//                        $aSubTicket['uuid'] = $oRawTicketModel->uuid->__toString();
//
//                        $aTemp[] = $aSubTicket;
//                    }
//                }
//            }
//
//            $this->rawTickets = array_merge($this->rawTickets, $aTemp);
//            $this->syncVersion = array_get($aReturnData, 'response.data.next_sync_version');
//
//            $this->consoleOutput->writeln(sprintf("#%d 累積收集共 %d 筆，已無注單，停止程序" . PHP_EOL,
//                $this->step,
//                count($this->rawTickets)));
//        }
//    }
//}