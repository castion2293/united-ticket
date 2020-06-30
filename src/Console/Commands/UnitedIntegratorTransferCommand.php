<?php

namespace SuperPlatform\UnitedTicket\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use SebastianBergmann\CodeCoverage\Report\PHP;
use SuperPlatform\UnitedTicket\Events\CompressDailyRecordEvent;
use SuperPlatform\UnitedTicket\Models\AllBetTicket;
use SuperPlatform\UnitedTicket\Models\UnitedTicket;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use Symfony\Component\Console\Output\ConsoleOutput;

class UnitedIntegratorTransferCommand extends Command
{
    protected $console;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ticket-integrator:transfer
        {station : 遊戲站識別碼}
        {user_id : 同步的目標ID}
        {username : 同步的目標帳號}
        {--password : 同步的目標密碼}
        {--startTime : 指定開始時間}
        {--endTime : 指定結束時間}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步指定遊戲站帳號的原生注單';

    /**
     * 遊戲站名
     *
     * @var string
     */
    protected $station = '';

    /**
     * 使用者id
     *
     * @var string
     */
    protected $user_id = '';

    /**
     * 使用者帳號
     *
     * @var string
     */
    protected $username = '';

    /**
     * 使用者密碼
     *
     * @var string
     */
    protected $password = '';

    /**
     * 開始時間
     *
     * @var string
     */
    protected $startTime = '';

    /**
     * 結束時間
     *
     * @var string
     */
    protected $endTime = '';

    /**
     * @var
     */
    protected $stationConfig;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->console = new ConsoleOutput();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->station = $this->argument('station');
        $this->user_id = $this->argument('user_id');
        $this->username = $this->argument('username');
        $this->password = $this->option('password');
        $this->startTime = $this->option('startTime');
        $this->endTime = $this->option('endTime');
        $this->stationConfig = config("united_ticket.{$this->station}");

        $user = [
            'username' => $this->username,
            'password' => $this->password,
        ];

        $captureBegin = microtime();

        // LOG queue 開始執行任務
        Log::channel('ticket-fetcher')->info(
            '[' . $this->station . ']' . '站開始在佇列執行抓取注單' . PHP_EOL
            . '會員ACCOUNT: ' . $this->username . PHP_EOL
            . '會員ID: ' . $this->user_id . PHP_EOL
            . '開始時間: ' . $this->startTime . PHP_EOL
            . '結束時間: ' . $this->endTime . PHP_EOL
        );

        $this->curl($user);

        $this->console->writeln("全部流程共花費 " . $this->microTimeDiff($captureBegin, microtime()) . ' 秒');

        // LOG queue 確定有執行
        Log::channel('ticket-fetcher')->info(
            '[' . $this->station . ']' . '站已經在佇列執行抓取注單' . PHP_EOL
            . '會員ACCOUNT: ' . $this->username . PHP_EOL
            . '會員ID: ' . $this->user_id . PHP_EOL
            . '開始時間: ' . $this->startTime . PHP_EOL
            . '結束時間: ' . $this->endTime . PHP_EOL
        );

    }

    /**
     * 抓取注單及轉換
     *
     * @param $user
     */
    private function curl($user)
    {
        // 建立遊戲站原生注單抓取器
        $fetcher = new $this->stationConfig['fetcher']($user);

        // 實際API資料抓取，保留
        $rawTickets = $fetcher->setTimeSpan($this->startTime, $this->endTime)->capture();

        if (count($rawTickets['tickets']) > 0) {

            try {
                // 儲存原生注單(透過 REPLACE INTO 方式)
                call_user_func([$this->stationConfig['ticket'], 'replace'], $rawTickets['tickets']);

                // 建立遊戲站原生注單轉換器
                $converter = \App::make($this->stationConfig['converter']);
                $unitedTickets = $converter->transform($rawTickets, $this->user_id);

                // Dream game 如果是1000張 就在遞迴一次檢查還有沒有更多的注單
                if ($this->station === 'dream_game' and count($unitedTickets) == 1000) {
                    sleep(10);
                    $this->curl($user);
                }

                // super 彩球需要再撈取總帳退水值
                if ($this->station == 'super_lottery') {
                    $rawRakes = $fetcher->setRakeDate($this->startTime, $this->endTime)->rakeCapture();
                    $converter->rakeConverter($rawRakes, ['user_identify' => $this->user_id]);
                }

                // UFA 體育 如果是100張 就在遞迴一次檢查還有沒有更多的注單
                if ($this->station === 'ufa_sport' and count($unitedTickets) == 100) {
                    sleep(10);
                    $this->curl($user);
                }

                // 放到Redis，等待TicketBlock每15分鐘從這取出資料
//            foreach ($unitedTickets as $unitedTicket) {
//                Redis::command('lpush', ['tickets', json_encode($unitedTicket)]);
//            }

//            // 儲存整合注單
//            UnitedTicket::replace($unitedTickets);

                // 如果撈取範圍超過三前，需要重壓每日結算注單
                $this->compressDailyTicketRecord($unitedTickets);

                $this->info('fetch_success ');
            } catch (\Exception $exception) {
                $this->console->writeln($exception->getMessage());

                event(new FetcherExceptionOccurred(
                    $exception,
                    $this->station,
                    'converter',
                    []
                ));
            }

        }
    }

    /**
     * 輔助函式: 取得兩個時間的毫秒差
     *
     * @param $start
     * @param null $end
     * @return float
     */
    protected function microTimeDiff($start, $end = null)
    {
        if (!$end) {
            $end = microtime();
        }
        list($start_usec, $start_sec) = explode(" ", $start);
        list($end_usec, $end_sec) = explode(" ", $end);
        $diff_sec = intval($end_sec) - intval($start_sec);
        $diff_usec = floatval($end_usec) - floatval($start_usec);
        return floatval($diff_sec) + $diff_usec;
    }

    /**
     * 重壓三天的每日結算注單
     *
     * @param array $unitedTickets
     */
    private function compressDailyTicketRecord(array $unitedTickets)
    {
        $compressData = collect($unitedTickets)->filter(function ($unitedTicket) {
            $betAt = Carbon::parse(array_get($unitedTicket, 'bet_at'));

            $isLessThanNow = $betAt->lessThan(now());
            $isLessThanThreeDays = $betAt->diffInDays(now()) > 2;

            return $isLessThanNow && $isLessThanThreeDays;
        })
        ->groupBy(function ($unitedTicket) {
            $betAt = array_get($unitedTicket, 'bet_at');

            return Carbon::parse($betAt)->toDateString();
        })
        ->map(function($items) {
            return $items->pluck('user_identify')->unique();
        })
        ->toArray();

        if (!empty($compressData)) {
            event(new CompressDailyRecordEvent($compressData));
        }
    }
}
