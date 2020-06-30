<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use Symfony\Component\Console\Output\ConsoleOutput;


/**
 * 第三方原生注單轉換器
 *
 * @package SuperPlatform\UnitedTicket\Converters
 */
abstract class Fetcher implements FetcherInterface
{
    /**
     * @var ConsoleOutput
     */
    protected $consoleOutput;

    /**
     * Fetcher constructor.
     */
    public function __construct()
    {
        $this->consoleOutput = new ConsoleOutput();
    }

    /**
     * 顯示例外資訊
     *
     * @param $exc
     */
    protected function showExceptionInfo($exc)
    {
        $exceptionName = get_class($exc);
        $this->consoleOutput->writeln(join(PHP_EOL, [
            "!!!!!!!!!!!!!!!!!!!",
            "!  Exception Info                     ",
            "!!!!!!!!!!!!!!!!!!!",
            "    Message: {$exceptionName}: {$exc->getMessage()}",
            "  File Line: {$exc->getFile()}:{$exc->getLine()}",
            "Stack trace: ",
            $exc->getTraceAsString(),
            "",
            ""
        ]));
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

}