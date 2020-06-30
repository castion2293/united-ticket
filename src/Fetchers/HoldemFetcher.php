<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use Carbon\Carbon;
use Exception;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\UnitedTicket\Models\HoldemTicket;
use SuperPlatform\UnitedTicket\Models\HoldemSlotTicket;

/**
 * 「德州」原生注單抓取器
 *
 * @package SuperPlatform\UnitedTicket\Fetchers
 */
class HoldemFetcher extends Fetcher
{
    /**
     * 收集抓到的原始注單 rawTickets
     * 收集抓到的彩金拉霸紀錄 slotTickets
     *
     * @var array
     */
    private $rawTickets = [];
    private $slotTickets = [];

    /**
     * 抓注單 WinLose、PrizeLog 會用到的參數集
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
     * HoldemFetcher constructor.
     * @param null $secretKey
     */
    public function __construct($secretKey = null)
    {
        parent::__construct();

        // 準備預設的 API 參數
        $this->params = [
            'BeginTime' => '',
            'EndTime' => '',
        ];
        $this->setTimeSpan();
    }

    /**
     * 設定查詢的區間（秒）
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
            $sFromTime = $dt->copy()->subSeconds(config("ticket_fetcher.holdem.fetch_time_range_seconds"));
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
        $this->params['BeginTime'] = $sFromTime->toDateTimeString();
        $this->params['EndTime'] = $sToTime->toDateTimeString();

        return $this;
    }

    /**
     * 抓取注單
     *
     * @return array
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
                "　　遊戲站: holdem                  ",
                "　開始時間: {$this->params['BeginTime']}  ",
                "　結束時間: {$this->params['EndTime']}",
                "　時間區間: {$this->timeSpan}",
                "--",
                ""
            ]));

            $this->rawTickets = $this->curl('WinLose');     //抓取注單資料
            $this->slotTickets = $this->curl('PrizeLog');   //抓取彩金資料

            $this->consoleOutput->writeln("--");
            $this->consoleOutput->writeln("　共花費 " . $this->microTimeDiff($captureBegin, microtime()) . ' 秒');
            $this->consoleOutput->writeln("=====================================");
            $this->consoleOutput->writeln("");

            // 回傳
            return [
                'tickets' => $this->rawTickets,
                'slot_tickets' => $this->slotTickets,
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
     *
     * CURL 抓取
     * @param $action
     * @return array
     *
     */
    private function curl($action)
    {
        $this->step++;

        $arrData = ApiCaller::make('holdem')
            ->methodAction('POST', $action)
            ->params($this->params)
            ->submit();

        // 取得注單資料並追加至原生注單收集器
        $rawTickets = array_get($arrData, 'Data', []);

        array_walk($rawTickets, function (&$ticket, $key, $action) {
            $rawTicketModel = ($action == 'WinLose') ? new HoldemTicket($ticket) : new HoldemSlotTicket($ticket);
            // 回傳套用原生注單模組後的資料(會產生 uuid)
            $ticket = $rawTicketModel->toArray();
            $ticket['uuid'] = $rawTicketModel->uuid->__toString();
        }, $action);

        $ticketType = ($action == 'WinLose') ? $this->rawTickets : $this->slotTickets;

        $ticketType = array_merge($ticketType, $rawTickets);

        $this->consoleOutput->writeln(sprintf("#%d 累積收集共 %d 筆",
            $this->step,
            count($ticketType)
        ));
        return $ticketType;
    }

}