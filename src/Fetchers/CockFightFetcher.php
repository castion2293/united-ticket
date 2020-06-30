<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use Carbon\Carbon;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\ApiCaller\Facades\ApiCaller;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Models\CockFightTicket;

class CockFightFetcher extends Fetcher
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
     * 撈單的方法
     *
     * @var string
     */
    private $payoutTicketAction = '';

    public function __construct(array $params)
    {
        parent::__construct();
    }

    public function __destruct()
    {
        unset($this->params);
        unset($this->rawTickets);
    }

    /**
     * 設定查詢的區間 S128限定查詢區間為30分鐘
     *
     * @param string $fromTime
     * @param string $toTime
     * @return Fetcher
     */
    public function setTimeSpan(string $fromTime = '', string $toTime = ''): Fetcher
    {
        $this->payoutTicketAction = 'get_cockfight_processed_ticket_by_bet_time';

        // 設定查詢的時間範圍
        // 沒有限制但建議每次拉取紀錄都撈30分鐘
        if (empty($fromTime) && empty($toTime)) {
            $this->params['start_datetime'] = now()->subMinutes(30)->toDateTimeString();
            $this->params['end_datetime'] = now()->toDateTimeString();
            return $this;
        }

        $this->params['start_datetime'] = Carbon::parse($fromTime)->toDateTimeString();
        $this->params['end_datetime'] = Carbon::parse($toTime)->toDateTimeString();

        return $this;
    }

    /**
     *  自動撈單的時間設定 S128限定查詢區間為30分鐘
     *
     * @return Fetcher
     */
    public function autoFetchTimeSpan(): Fetcher
    {
        $this->payoutTicketAction = 'get_cockfight_processed_ticket_2';

        $this->params['start_datetime'] = now()->subMinutes(30)->toDateTimeString();
        $this->params['end_datetime'] = now()->toDateTimeString();

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

            $this->consoleOutput->writeln(join(PHP_EOL, [
                "=====================================",
                "  原生注單抓取程序啟動                  ",
                "-------------------------------------",
                "　　遊戲站: S128鬥雞                    ",
                "　 開始時間: {$this->params['start_datetime']}",
                "　 結束時間: {$this->params['end_datetime']}",
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

    private function curl(): void
    {
        $station = 'cock_fight';

        $tickets = [];

        // 撈取未結算的注單
        try {
            $openTicketAction = 'get_cockfight_open_ticket_2';
            $openTicketData = ApiCaller::make($station)
                ->methodAction('POST', $openTicketAction)
                ->params([])
                ->submit();
        } catch (\Exception $exception) {
            event(new FetcherExceptionOccurred(
                $exception,
                $station,
                $openTicketAction,
                $this->params
            ));
            throw $exception;
        }

        $tickets = array_merge($tickets, array_get($openTicketData, 'response.data'));

        // 撈取派彩的注單
        try {
            $payoutTicketData = ApiCaller::make($station)
                ->methodAction('POST', $this->payoutTicketAction)
                ->params($this->params)
                ->submit();
        } catch (\Exception $exception) {
            event(new FetcherExceptionOccurred(
                $exception,
                $station,
                $openTicketAction,
                $this->params
            ));
            throw $exception;
        }

        $tickets = array_merge($tickets, array_get($payoutTicketData, 'response.data'));

        if (!empty($tickets)) {
            // 因為如果是單一一張注單，需把它加到一個陣列中的元素，避免錯誤
            $tempArray = $tickets;

            if(!is_array(array_shift($tempArray))) {
                $tickets = [$tickets];
            };

            foreach ($tickets as $ticket) {
                $rawTicket = [];

                $rawTicket['ticket_id'] = intval(array_get($ticket, 0));
                $rawTicket['login_id'] = array_get($ticket, 1);
                $rawTicket['arena_code'] = array_get($ticket, 2);
                $rawTicket['arena_name_cn'] = array_get($ticket, 3);
                $rawTicket['match_no'] = array_get($ticket, 4);
                $rawTicket['match_type'] = array_get($ticket, 5);
                $rawTicket['match_date'] = array_get($ticket, 6);
                $rawTicket['fight_no'] = intval(array_get($ticket, 7));
                $rawTicket['fight_datetime'] = array_get($ticket, 8);
                $rawTicket['meron_cock'] = array_get($ticket, 9);
                $rawTicket['meron_cock_cn'] = array_get($ticket, 10);
                $rawTicket['wala_cock'] = array_get($ticket, 11);
                $rawTicket['wala_cock_cn'] = array_get($ticket, 12);
                $rawTicket['bet_on'] = array_get($ticket, 13);
                $rawTicket['odds_type'] = array_get($ticket, 14);
                $rawTicket['odds_asked'] = floatval(array_get($ticket, 15));
                $rawTicket['odds_given'] = floatval(array_get($ticket, 16));
                $rawTicket['stake'] = intval(array_get($ticket, 17));
                $rawTicket['stake_money'] = floatval(array_get($ticket, 18));
                $rawTicket['balance_open'] = floatval(array_get($ticket, 19));
                $rawTicket['balance_close'] = floatval(array_get($ticket, 20));
                $rawTicket['created_datetime'] = array_get($ticket, 21);
                $rawTicket['fight_result'] = array_get($ticket, 22);
                $rawTicket['status'] = array_get($ticket, 23);
                $rawTicket['winloss'] = floatval(array_get($ticket, 24));
                $rawTicket['comm_earned'] = floatval(array_get($ticket, 25));
                $rawTicket['payout'] = floatval(array_get($ticket, 26));
                $rawTicket['balance_open1'] = floatval(array_get($ticket, 27));
                $rawTicket['balance_close1'] = floatval(array_get($ticket, 28));
                $rawTicket['processed_datetime'] = array_get($ticket, 29);

                $rawTicketModel = new CockFightTicket($rawTicket);
                // 回傳套用原生注單模組後的資料(會產生 uuid)
                $rawTicket = $rawTicketModel->toArray();
                $rawTicket['uuid'] = $rawTicketModel->uuid->__toString();

                array_push($this->rawTickets, $rawTicket);
            }
        }
    }
}