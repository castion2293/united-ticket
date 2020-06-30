<?php

namespace SuperPlatform\UnitedTicket\Listeners;

use SuperPlatform\UnitedTicket\Events\ConvertExceptionOccurred;

class TicketConvertListener
{
    /**
     * @var
     */
    protected $stationConfig;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object $event
     * @return void
     */
    public function handle($event)
    {
        $station = array_get($event->params, 'station');
        $tickets = array_get($event->params, 'tickets');

        $this->stationConfig = config("united_ticket.{$station}");

        // 建立遊戲站原生注單抓取器
        $fetcher = new $this->stationConfig['fetcher']([]);

        // 傳入原生注單資料並回傳結果
        $rawTickets = $fetcher->dealWithRawTickets($tickets)->getTickets();

        if (count($rawTickets['tickets']) > 0) {
            try {
                // 儲存原生注單(透過 REPLACE INTO 方式)
                call_user_func([$this->stationConfig['ticket'], 'replace'], $rawTickets['tickets']);

                // 建立遊戲站原生注單轉換器
                $converter = \App::make($this->stationConfig['converter']);
                $unitedTickets = $converter->transform($rawTickets, '');
            } catch (\Exception $exception) {
                event(
                    new ConvertExceptionOccurred(
                        $exception,
                        $station,
                        'converter',
                        []
                    )
                );
            }
        }
    }
}