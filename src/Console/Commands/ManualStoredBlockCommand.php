<?php

namespace SuperPlatform\UnitedTicket\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use SuperPlatform\UnitedTicket\Facades\BlockUnitedTicket as BUT;
use Symfony\Component\Console\Output\ConsoleOutput;

class ManualStoredBlockCommand extends Command
{
    /**
     * The name and signature of the console Console.
     *
     * @var string
     */
    protected $signature = 'united-ticket:manual-stored-block
        {--station : 遊戲站識別碼}
        {start : 開始時間}
        {end : 結束時間}';
    /**
     * The console Console description.
     *
     * @var string
     */
    protected $description = '輸入「遊戲名稱」,「開始時間」,「結束時間」  輸出「這個區間」的預查結果';

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
        $start = $this->argument('start');
        $end = $this->argument('end');

        $start_point = Carbon::parse($start->copy()->subMinutes($start->minute % 15)->format('Y-m-d H:i:00'));
        $end_point = Carbon::parse($end->copy()->subMinutes($end->minute % 15)->addMinutes(15)->format('Y-m-d H:i:00'));

        $sections = [];

        while($start_point->lessThan($end_point)) {
            $sections[] = [
                $start_point->format('Y-m-d H:i:00'),
                $start_point->addMinutes(15)->format('Y-m-d H:i:00')
            ];
        }

        foreach($sections as $section) {
            list($start, $end) = $section;
            BUT::storeBlock($station, $start, $end);
        }
    }
}
