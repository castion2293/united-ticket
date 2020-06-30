<?php

namespace SuperPlatform\UnitedTicket\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\ConsoleOutput;
use Exception;

class AllBetFetchEGameTicketJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 遊戲站識別碼
     *
     * @var string
     */
    protected $station;

    /**
     * 開始時間
     *
     * @var string
     */
    protected $startTime;

    /**
     * 結束時間
     *
     * @var string
     */
    protected $endTime;

    public function __construct(String $station, String $startTime, String $endTime)
    {
        $this->station = $station;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

    /**
     * @return void
     */
    public function handle()
    {
        try {
            Artisan::call('all_bet:ticket-fetch', [
                '--startTime' => $this->startTime,
                '--endTime' => $this->endTime,
            ]);
        } catch (Exception $exception) {
            resolve(ConsoleOutput::class)->writeln($exception->getMessage());
        }
    }
}