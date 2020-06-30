<?php

namespace SuperPlatform\UnitedTicket\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use SuperPlatform\UnitedTicket\Facades\BlockUnitedTicket as BUT;
use Symfony\Component\Console\Output\ConsoleOutput;

class UnitedTicketStoredBlockCommand extends Command
{
    /**
     * The name and signature of the console Console.
     *
     * @var string
     */
    protected $signature = 'united-ticket:stored-block
        {--station : 遊戲站識別碼}
        {--start : 指定時間開始點}
        {--end : 指定時間結束點}';
    /**
     * The console Console description.
     *
     * @var string
     */
    protected $description = '輸入「遊戲名稱」,「時間點」 輸出「上一個刻鐘區段」的預查結果 預設時間點是目前時間的前一刻鐘';

    /**
     * Create a new Console instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->consoleOutput = new ConsoleOutput();
    }

    /**
     * Execute the console Console.
     *
     * @return mixed
     */
    public function handle()
    {
        $station = $this->option('station');
        $start = $this->option('start');
        $end = $this->option('end');

        BUT::storeBlock($station, $start, $end);
    }
}
