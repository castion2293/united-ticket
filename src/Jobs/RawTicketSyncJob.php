<?php

namespace SuperPlatform\UnitedTicket\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Output\ConsoleOutput;

class RawTicketSyncJob implements ShouldQueue
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
     * 指定開始時間
     *
     * @var array
     */
    protected $startTime;

    /**
     * 指定結束時間
     *
     * @var array
     */
    protected $endTime;

    /**
     * @param string $station
     * @param string|array $username
     * @param string $startTime
     * @param string $endTime
     */
    public function __construct(String $station, array $users, String $startTime = '', String $endTime = '')
    {
        $this->station = $station;

        if (is_array($users)) {
            array_push($this->users, $users);
        } else if (is_string($users)) {
            array_push($this->users, $users);
        }

        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

    /**
     * @return void
     */
    public function handle()
    {
        foreach ($this->users as $user) {
            try {
                Artisan::call('ticket-integrator:transfer', [
                    'station' => $this->station,
                    'username' => array_get($user, 'username'),
                    'user_id' => array_get($user, 'user_id'),
                    '--password' => array_get($user, 'password'),
                    '--startTime' => $this->startTime,
                    '--endTime' => $this-> endTime,
                ]);
            } catch (Exception $exception) {
//                throw $exception;
                resolve(ConsoleOutput::class)->writeln($exception->getMessage());
            }
        }
    }

    /**
     * The job failed to process.
     *
     * @param  Exception  $exception
     * @return void
     */
    public function failed(Exception $exception)
    {

    }
}
