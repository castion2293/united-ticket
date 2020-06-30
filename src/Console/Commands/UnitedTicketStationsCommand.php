<?php

namespace SuperPlatform\UnitedTicket\Console\Commands;

use Illuminate\Console\Command;

class UnitedTicketStationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'united-ticket:stations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '列出目前可以進行同步的遊戲站識別碼';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $rows = [];
        foreach (config('united_ticket') as $key => $config) {
            $rows[] = [
                array_get($config, 'label', $key),
                $key
            ];
        }
        $this->table(['遊戲站名稱', '遊戲站識別碼'], $rows);
        return array_keys(config('united_ticket'));
    }
}
