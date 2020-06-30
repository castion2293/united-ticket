<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Models\SlotFactoryTicket;

class SlotFactoryFetcher extends Fetcher
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
     * 原始的起訖時間
     *
     * @var array
     */
    private $inputDateTimeRange = [];

    public function __construct(array $params)
    {
        parent::__construct();

        // 準備預設的 API 參數
        $this->params = [
            'ReportType' => 'LicenseeReport',
//            'AccountID' => array_get($params, 'username'),
            'LicenseeName' => config('api_caller.slot_factory.config.customer_name'),
        ];
    }

    public function __destruct()
    {
        unset($this->params);
        unset($this->rawTickets);
        unset($this->inputDateTimeRange);
    }

    /**
     * 設定查詢的區間，SF電子使用的是UTC時間
     *
     * @param string $sFromTime
     * @param string $sToTime
     * @return Fetcher
     */
    public function setTimeSpan(string $fromTime = '', string $toTime = ''): Fetcher
    {
        $this->inputDateTimeRange = [
            'from' => $fromTime,
            'to' => $toTime
        ];

        // 設定查詢的時間範圍
        // 沒有限制但建議每次拉取紀錄都撈30分鐘
        if (empty($fromTime) && empty($toTime)) {
            $this->params['From'] = now()->subMinutes(30)->timezone('Europe/London')->toDateTimeString();
            $this->params['To'] = now()->timezone('Europe/London')->toDateTimeString();
            return $this;
        }

        $this->params['From'] = Carbon::parse($fromTime)->timezone('Europe/London')->toDateTimeString();
        $this->params['To'] = Carbon::parse($toTime)->timezone('Europe/London')->toDateTimeString();

        return $this;
    }

    /**
     *  自動撈單的時間設定，SF電子使用的是UTC時間
     *
     * @return Fetcher
     */
    public function autoFetchTimeSpan(): Fetcher
    {
        $this->params['From'] = now()->timezone('Europe/London')->subMinutes(90)->toDateTimeString();
        $this->params['To'] = now()->timezone('Europe/London')->toDateTimeString();

        $this->inputDateTimeRange = [
            'from' => now()->subMinutes(30)->toDateTimeString(),
            'to' => now()->toDateTimeString(),
        ];

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

            $accountId = array_get($this->params, 'AccountID');
            $inputFrom = array_get($this->inputDateTimeRange, 'from');
            $inputTo = array_get($this->inputDateTimeRange, 'to');

            $this->consoleOutput->writeln(join(PHP_EOL, [
                "=====================================",
                "  原生注單抓取程序啟動                  ",
                "-------------------------------------",
                "　　遊戲站: SF電子                    ",
                "　　會員帳號: {$accountId}                    ",
                "　 開始時間: {$inputFrom}",
                "　 結束時間: {$inputTo}",
                "--",
                ""
            ]));

            $this->curl();

            $this->consoleOutput->writeln(sprintf("累積收集共 %d 筆，已無注單，停止程序" . PHP_EOL,
                    count($this->rawTickets))
            );

            $this->consoleOutput->writeln(join(
                PHP_EOL,
                [
                    '--',
                    '　共花費 ' . $this->microTimeDiff($captureBegin, microtime()) . ' 秒',
                    '=====================================',
                    '',
                ]
            ));

            // 回傳
            return [
                'tickets' => $this->rawTickets,
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
     * @throws \Exception
     */
    private function curl(): void
    {
        $station = 'slot_factory';
        $action = 'playreport';
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

        $response = array_get($arrData, 'response');
        $spinReports = array_get($response, 'SpinReport');
        $bonusReports = array_get($response, 'BonusReport');

        if (!empty($spinReports)) {
            // 因為如果是單一一張注單，需把它加到一個陣列中的元素，避免錯誤
            $tempArray = $spinReports;

            if(!is_array(array_shift($tempArray))) {
                $tickets = [$spinReports];
            };

            foreach ($spinReports as $spinReport) {
                $rawTicket = [];

                $rawTicket['TransactionID'] = array_get($spinReport, 'TransactionID');
                $rawTicket['AccountID'] = array_get($spinReport, 'AccountID');
                $rawTicket['RoundID'] = array_get($spinReport, 'RoundID');
                $rawTicket['GameName'] = array_get($spinReport, 'GameName');
                $rawTicket['SpinDate'] = array_get($spinReport, 'SpinDate');
                $rawTicket['Currency'] = array_get($spinReport, 'Currency');
                $rawTicket['Lines'] = array_get($spinReport, 'Lines');
                $rawTicket['LineBet'] = array_get($spinReport, 'LineBet');
                $rawTicket['TotalBet'] = array_get($spinReport, 'TotalBet');
                $rawTicket['CashWon'] = array_get($spinReport, 'CashWon');
                $rawTicket['GambleGames'] = array_get($spinReport, 'GambleGames');
                $rawTicket['FreeGames'] = array_get($spinReport, 'FreeGames');
                $rawTicket['FreeGamePlayed'] = array_get($spinReport, 'FreeGamePlayed');
                $rawTicket['FreeGameRemaining'] = array_get($spinReport, 'FreeGameRemaining');

                $rawTicketModel = new SlotFactoryTicket($rawTicket);

                // 回傳套用原生注單模組後的資料(會產生 uuid)
                $rawTicket = $rawTicketModel->toArray();
                $rawTicket['uuid'] = $rawTicketModel->uuid->__toString();

                array_push($this->rawTickets, $rawTicket);
            }
        }

        if (!empty($bonusReports)) {
            // 因為如果是單一一張注單，需把它加到一個陣列中的元素，避免錯誤
            $tempArray = $bonusReports;

            if(!is_array(array_shift($tempArray))) {
                $bonusReports = [$bonusReports];
            };

            foreach ($bonusReports as $bonusReport) {
                $rawTicket = [];

                $rawTicket['TransactionID'] = array_get($bonusReport, 'TransactionID');
                $rawTicket['AccountID'] = array_get($bonusReport, 'AccountID');
                $rawTicket['RoundID'] = array_get($bonusReport, 'RoundID');
                $rawTicket['GameName'] = array_get($bonusReport, 'GameName');
                $rawTicket['SpinDate'] = array_get($bonusReport, 'BonusDate');
                $rawTicket['Currency'] = array_get($bonusReport, 'Currency');
                $rawTicket['Lines'] = 0;
                $rawTicket['LineBet'] = '0';
                $rawTicket['TotalBet'] = '0';
                $rawTicket['CashWon'] = array_get($bonusReport, 'CashWon');
                $rawTicket['FreeGames'] = true;
                $rawTicket['FreeGamePlayed'] = 0;
                $rawTicket['FreeGameRemaining'] = 0;
                $rawTicket['Type'] = array_get($bonusReport, 'Type');

                $rawTicketModel = new SlotFactoryTicket($rawTicket);
                // 回傳套用原生注單模組後的資料(會產生 uuid)
                $rawTicket = $rawTicketModel->toArray();
                $rawTicket['uuid'] = $rawTicketModel->uuid->__toString();

                array_push($this->rawTickets, $rawTicket);
            }
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
            $lastTicketsSet = DB::table('raw_tickets_slot_factory')
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