<?php

namespace SuperPlatform\UnitedTicket\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;
use SuperPlatform\UnitedTicket\Converters\AllBetConverter;
use SuperPlatform\UnitedTicket\Events\CompressDailyRecordEvent;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;
use SuperPlatform\UnitedTicket\Fetchers\AllBetFetcher;
use SuperPlatform\UnitedTicket\Models\AllBetTicket;
use Symfony\Component\Console\Output\ConsoleOutput;

class AllBetEGameFetchTicketCommand extends Command
{
    protected $console;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'all_bet:ticket-fetch
        {--startTime= : 指定開始時間}
        {--endTime= : 指定結束時間}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步歐博的電子遊戲注單';

    /**
     * 使用者id
     *
     * @var string
     */
    protected $user_id = '';

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
     * @throws ApiCallerException
     */
    public function handle()
    {
        $this->startTime = $this->option('startTime');
        $this->endTime = $this->option('endTime');

        $captureBegin = microtime();

        // LOG queue 開始執行任務
        Log::channel('ticket-fetcher')->info(
            '[all_bet]' . '站開始在佇列執行抓取注單' . PHP_EOL
            . '開始時間: ' . $this->startTime . PHP_EOL
            . '結束時間: ' . $this->endTime . PHP_EOL
        );

        $this->curl();

        $this->console->writeln("全部流程共花費 " . $this->microTimeDiff($captureBegin, microtime()) . ' 秒');

        // LOG queue 確定有執行
        Log::channel('ticket-fetcher')->info(
            '[all_bet]' . '站已經在佇列執行抓取注單' . PHP_EOL
            . '開始時間: ' . $this->startTime . PHP_EOL
            . '結束時間: ' . $this->endTime . PHP_EOL
        );
    }

    /**
     * 抓取注單及轉換
     *
     * @throws ApiCallerException
     */
    private function curl()
    {
        // 建立遊戲站原生注單抓取器
        $fetcher = new AllBetFetcher(['e_game_type' => 'af']);

        // 實際API資料抓取
        $rawTickets = $fetcher->setTimeSpan($this->startTime, $this->endTime)->capture();

        if (count($rawTickets['tickets']) > 0) {
            try {
                // 儲存原生注單(透過 REPLACE INTO 方式)
                call_user_func([AllBetTicket::class, 'replace'], $rawTickets['tickets']);

                // 建立遊戲站原生注單轉換器
                $converter = new AllBetConverter();
                $unitedTickets = $converter->transformEGameTickets($rawTickets, $this->user_id);

                // 如果撈取範圍超過三前，需要重壓每日結算注單
                $this->compressDailyTicketRecord($unitedTickets);

                $this->info('fetch_success ');
            } catch (\Exception $exception) {
                $this->console->writeln($exception->getMessage());

                event(new FetcherExceptionOccurred(
                    $exception,
                    'all_bet',
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
            $isLessThanThreeDays = $betAt->diffInDays(now()) > 3;

            return $isLessThanNow && $isLessThanThreeDays;
        })
            ->groupBy(function ($unitedTicket) {
                $betAt = array_get($unitedTicket, 'bet_at');

                return Carbon::parse($betAt)->toDateString();
            })
            ->map(function ($items) {
                return $items->pluck('user_identify')->unique();
            })
            ->toArray();

        if (!empty($compressData)) {
            event(new CompressDailyRecordEvent($compressData));
        }
    }
}