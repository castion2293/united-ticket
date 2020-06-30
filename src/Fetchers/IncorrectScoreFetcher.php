<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Models\IncorrectScoreTicket;

class IncorrectScoreFetcher extends Fetcher
{
    /**
     * 收集抓到的原始注單
     *
     * @var array
     */
    private $rawTickets = [];

    /**
     * 抓注單 GetMemberReportV2 會用到的參數集
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
            'startTime' => '',
            'endTime' => '',
            'user' => '',
        ];
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
        $this->params['startTime'] = $sFromTime->toDateTimeString();
        $this->params['endTime'] = $sToTime->toDateTimeString();

        return $this;
    }

    /**
     * 自動撈單的時間區間
     */
    public function autoFetchTimeSpan(): Fetcher
    {
        $start = now()->subMinutes(30);
        $end = now();

        $this->params['startTime'] = $start->toDateTimeString();
        $this->params['endTime'] = $end->toDateTimeString();

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
                "　　遊戲站: 反波膽                 ",
                "　開始時間: {$this->params['startTime']}",
                "　結束時間: {$this->params['endTime']}",
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
     * @throws Exception
     */
    private function curl()
    {
        $this->step++;

        $station = 'incorrect_score';
        $action = 'GetMemberReportV2';
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
        $rawTickets = array_get($arrData, 'response.result');

        // 處理原生注單
        $this->dealWithRawTickets($rawTickets);
    }

    /**
     * 處理原生注單
     *
     * @param $rawTickets
     * @return mixed
     */
    public function dealWithRawTickets(array $rawTickets = [])
    {
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
            foreach ($rawTickets as $ticketSet) {
                $ticket = [
                    'ticketNo' => array_get($ticketSet, 'ticketNo'),
                    'user' => array_get($ticketSet, 'user'),
                    'sportType' => array_get($ticketSet, 'sportType'),
                    'orderTime' => array_get($ticketSet, 'orderTime'),
                    'betTime' => array_get($ticketSet, 'betTime'),
                    'cancelTime' => array_get($ticketSet, 'cancelTime'),
                    'betamount' => array_get($ticketSet, 'betamount'),
                    'validBetAmount' => array_get($ticketSet, 'validBetAmount'),
                    'handlingFee' => array_get($ticketSet, 'handlingFee'),
                    'currency' => array_get($ticketSet, 'currency'),
                    'profit' => array_get($ticketSet, 'profit'),
                    'realAmount' => array_get($ticketSet, 'realAmount'),
                    'winlose' => array_get($ticketSet, 'winlose'),
                    'isFinished' => array_get($ticketSet, 'isFinished'),
                    'statusType' => array_get($ticketSet, 'statusType'),
                    'wagerGrpId' => array_get($ticketSet, 'wagerGrpId'),
                    'betIp' => array_get($ticketSet, 'betIp'),
                    'cType' => array_get($ticketSet, 'cType'),
                    'device' => array_get($ticketSet, 'device'),
                    'accdate' => array_get($ticketSet, 'accdate'),
                    'acctId' => array_get($ticketSet, 'acctId'),
                    'refNo' => array_get($ticketSet, 'detail.0.refNo'),
                    'evtid' => array_get($ticketSet, 'detail.0.evtid'),
                    'league' => array_get($ticketSet, 'detail.0.league'),
                    'match' => array_get($ticketSet, 'detail.0.match'),
                    'betOption' => array_get($ticketSet, 'detail.0.betOption'),
                    'hdp' => array_get($ticketSet, 'detail.0.hdp'),
                    'odds' => array_get($ticketSet, 'detail.0.odds'),
                    'winlostTime' => array_get($ticketSet, 'detail.0.winlostTime'),
                    'scheduleTime' => array_get($ticketSet, 'detail.0.scheduleTime'),
                    'ftScore' => array_get($ticketSet, 'detail.0.ftScore'),
                    'curScore' => array_get($ticketSet, 'detail.0.curScore'),
                    'wagerTypeID' => array_get($ticketSet, 'detail.0.wagerTypeID'),
                    'cutline' => array_get($ticketSet, 'detail.0.cutline'),
                    'odddesc' => array_get($ticketSet, 'detail.0.odddesc'),
                ];

                $rawTicketModel = new IncorrectScoreTicket($ticket);

                // 回傳套用原生注單模組後的資料(會產生 uuid)
                $ticket = $rawTicketModel->toArray();
                $ticket['uuid'] = $rawTicketModel->uuid->__toString();
                array_push($this->rawTickets, $ticket);
            };
        }

        return $this;
    }

    /**
     * 回傳原生注單資訊 供單一錢包回戳使用
     *
     * @return array
     */
    public function getTickets()
    {
        // 回傳
        return [
            'tickets' => $this->rawTickets
        ];
    }
}