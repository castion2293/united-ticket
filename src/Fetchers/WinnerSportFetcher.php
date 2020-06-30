<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Models\WinnerSportTicket;

class WinnerSportFetcher extends Fetcher
{
    private $sStation = 'winner_sport';
    private $aParams = [];
    private $aRawTickets = [];
    private $iStep = 0;
    private $sStartKey = 'sdate';
    private $sEndKey = 'edate';
    private $sGetTicketsMethod = 'POST';
    private $sGetTicketsAction = 'Find_Tix2';
    private $sResponseKey = 'wgs';
    private $iCurrentPage = 1;
    private $iMaxPage = 1;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 設定查詢的區間
     * 最多不能超過 7 天
     *
     * @param string $sFromTime
     * @param string $sToTime
     * @return Fetcher
     */
    public function setTimeSpan(string $sFromTime = '', string $sToTime = ''): Fetcher
    {
        if (empty($sToTime)) {
            $oSecondTime = Carbon::now();
        } else {
            $oSecondTime = Carbon::parse($sToTime);
        }

        $oFirstTime = Carbon::parse($sFromTime);

        if ($oFirstTime->between($oSecondTime, $oSecondTime->copy()->subDays(7))) {
            $this->aParams[$this->sStartKey] = $oFirstTime->toDateTimeString();
            $this->aParams[$this->sEndKey] = $oSecondTime->toDateTimeString();
        } else {
            $this->aParams[$this->sStartKey] = $oSecondTime->copy()->subDays(7)->toDateTimeString();
            $this->aParams[$this->sEndKey] = $oSecondTime->toDateTimeString();
        }

        return $this;
    }

    public function autoFetchTimeSpan(): Fetcher
    {
        $this->aParams[$this->sStartKey] = now()->subDays(3)->toDateTimeString();
        $this->aParams[$this->sEndKey] = now()->toDateTimeString();

        return $this;
    }

    /**
     * @return array
     * @throws ApiCallerException
     * @throws Exception
     */
    public function capture(): array
    {
        try {
            $captureBegin = microtime();

            $this->consoleOutput->writeln(join(
                PHP_EOL,
                [
                    '=====================================',
                    '  原生注單抓取程序啟動                  ',
                    '-------------------------------------',
                    "　　遊戲站: {$this->sStation}",
                    "　開始時間: {$this->aParams[$this->sStartKey]}",
                    "　結束時間: {$this->aParams[$this->sEndKey]}",
                    '--',
                    ''
                ]
            ));

            $this->curl();

            $this->consoleOutput->writeln(join(
                PHP_EOL,
                [
                    '--',
                    "　共花費 {$this->microTimeDiff($captureBegin, microtime())} 秒",
                    '=====================================',
                    '',
                ]
            ));

            // 回傳
            return [
                'tickets' => $this->aRawTickets,
            ];

        } catch (ApiCallerException $e) {
            show_exception_message($e);
            throw $e;
        } catch (Exception $e) {
            show_exception_message($e);
            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    private function curl(): void
    {
        $this->iStep++;
        $this->aParams['agent'] = '';
        $this->aParams['page'] = $this->iCurrentPage;

        try {
            $aResponseFormatData = ApiCaller::make($this->sStation)
                ->methodAction($this->sGetTicketsMethod, $this->sGetTicketsAction, [])
                ->params($this->aParams)
                ->submit();
        } catch (Exception $e) {
            event(new FetcherExceptionOccurred(
                $e,
                $this->sStation,
                $this->sGetTicketsAction,
                $this->aParams
            ));
            throw $e;
        }

        // 取得注單資料並追加至原生注單收集器
        $aRawTickets = array_get($aResponseFormatData, "response.{$this->sResponseKey}");

        // 檢查 $aRawTickets 是否為空
        if (filled($aRawTickets)) {
            /**
             * TODO: 重要!!! 每個遊戲站都要為自己處理這一塊!!!
             *
             * 一定要為原生注單生產生主要識別碼
             * 如果已經有唯一識別碼就直接使用
             * 沒有的話就要用聯合鍵來產生
             */
            $aTemp = [];

            foreach ($aRawTickets as $aTicket) {
                $bIsPr = array_get($aTicket, 'pr') == 1;

                if ($bIsPr) {
                    $aTicket['detail'] = $this->resetSub(array_get($aTicket, 'subw'));
                } else {
                    $aTicket['detail'] = sprintf("%s => %s", array_get($aTicket, 'bet_txt_1'), array_get($aTicket, 'bet_txt_2'));
                }

                $oRawTicketModel = new WinnerSportTicket($aTicket);
                // 回傳套用原生注單模組後的資料 (會產生 uuid)
                $aTicket = $oRawTicketModel->toArray();
                $aTicket['uuid'] = $oRawTicketModel->uuid->__toString();

                $aTemp[] = $aTicket;
            }

            $this->aRawTickets = array_merge($this->aRawTickets, $aTemp);

            $this->consoleOutput->writeln(sprintf(
                "#%d 累積收集共 %s 筆，已無注單，停止程序%s",
                $this->iStep,
                count($this->aRawTickets),
                PHP_EOL
            ));
        }

        // 如果還有沒抓完的頁數 繼續抓回
        $this->iMaxPage = array_get($aResponseFormatData, "response.maxpage", 1);
        $this->iCurrentPage++;

        if ($this->iCurrentPage - 1 < $this->iMaxPage) $this->curl();
    }

    private function resetSub(array $array): string
    {
        $aRes = [];

        foreach ($array as $k => $v) {
            $aRes[] = sprintf("%s [ 結果: %s , 內容 : %s => %s ]", array_get($v, 'sub'), array_get($v, 'result'), array_get($v, 'bet_txt_1'), array_get($v, 'bet_txt_2'));
        }

        return implode('; ', $aRes);
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
            $lastTicketsSet = DB::table('raw_tickets_winner_sport')
                ->select('uuid', 'result', 'status')
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

            // 與上一次result狀態不同的單需要做轉換
            $fetchRawTicketResult = array_get($fetchRawTicket, 'result');
            $lastTicketResult = data_get($lastTicket, 'result');
            if ($fetchRawTicketResult !== $lastTicketResult) {
                return true;
            }

            // 與上一次status狀態不同的單需要做轉換
            $fetchRawTicketStatus = array_get($fetchRawTicket, 'status');
            $lastTicketStatus = data_get($lastTicket, 'status');
            if ($fetchRawTicketStatus != $lastTicketStatus) {
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