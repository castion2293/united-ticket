<?php

namespace SuperPlatform\UnitedTicket\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\ConsoleOutput;

class AutoFetchTicketJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 遊戲站識別碼
     *
     * @var string
     */
    protected $station;

    /**
     * 帳號識別碼列表
     *
     * @var array
     */
    protected $users = [];

    /**
     * AuthFetchTicketJob constructor.
     * @param String $station
     * @param array $users
     */
    public function __construct(String $station, array $users)
    {
        $this->station = $station;

        if (is_array($users)) {
            array_push($this->users, $users);
        } else if (is_string($users)) {
            array_push($this->users, $users);
        }
    }

    /**
     * @return void
     */
    public function handle()
    {
        foreach ($this->users as $user) {
            try {
                Artisan::call('auto-fetch:ticket', [
                    'station' => $this->station,
                    'username' => array_get($user, 'username'),
                    'user_id' => array_get($user, 'user_id'),
                    '--password' => array_get($user, 'password'),
                ]);
            } catch (Exception $exception) {
//                throw $exception;
                resolve(ConsoleOutput::class)->writeln($exception->getMessage());
            }
        }
    }
}