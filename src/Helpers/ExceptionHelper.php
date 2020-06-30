<?php

use Carbon\Carbon;

if (!function_exists('show_exception_message')) {

    /**
     * output exception message
     * todo 應該獨立出去 somewhere
     */
    function show_exception_message(\Exception $exception)
    {
        $console = new \Symfony\Component\Console\Output\ConsoleOutput();
        $console->write('exception code: ');
        $console->writeln($exception->getCode());
        $console->write('exception message: ');
        $console->writeln(json_encode($exception->getMessage(), JSON_PRETTY_PRINT));

        // 若有自定的 response method 就呼叫
        if (method_exists($exception, 'response')) {
            $console->write('exception response: ');
            $console->writeln(json_encode($exception->response(), JSON_PRETTY_PRINT));
            \Illuminate\Support\Facades\Log::error(json_encode($exception->response(), JSON_PRETTY_PRINT));
        }
    }
}

if (!function_exists('exception_log_format_ticket')) {
    /**
     * 寫入錯誤的 log
     *
     * @param Exception $exception
     * @param string $connector
     * @param string $action
     * @param array $params
     * @return string
     */
    function exception_log_format_ticket(
        \Exception $exception,
        string $connector,
        string $action = '',
        array $params = []
    ) {
        // 若 exception 屬於 \SuperPlatform\ApiCaller\Exceptions\ApiCallerException 可呼叫
        $responseText = '';
        $errorCode = '| 🔢 ' . $exception->getCode() . PHP_EOL ;
        $errorMessage = '| 🗣 ' . $exception->getMessage() . PHP_EOL;
        if (method_exists($exception, 'response')) {
            $response = $exception->response();
            foreach ($response as $key => $text) {
                $responseText .= '|   ' . $key . ' => ' . $text . PHP_EOL;
            }
            $errorCode = '| 🔢 ' . array_get($response, 'errorCode', $exception->getCode()) . PHP_EOL;
            $errorMessage = '| 🗣 ' . array_get($response, 'errorMsg', $exception->getMessage()) . PHP_EOL;
        }
        $paramText = '';
        if (!empty($params)) {
            foreach ($params as $key => $text) {
                if (is_string($text)) {
                    $paramText .= '|   ' . $key . ' => ' . $text . PHP_EOL;
                }
                if (is_array($text)) {
                    $paramText .= '|   ' . $key . ' => ' . json_encode($text, 64 | 128 | 256) . PHP_EOL;
                }
                if (is_numeric($text)) {
                    $paramText .= '|   ' . $key . ' => ' . strval($text) . PHP_EOL;
                }
                if ($key == 'Username' || $key == 'user' || $key == 'account') {
                    $paramText = '|   ' . $key . ' => ' . '#' . $text . PHP_EOL;
                }
            }
        } else {
            $paramText = 'null';
        }

        $link = config('app.domain', 'super-platform.test');
        if (strpos($link, 'http') === false) {
            $link = 'http://' . $link;
        }

        $message = PHP_EOL .
            '-----------------------------------------------------' . PHP_EOL .
            '| 📅 ' . Carbon::now()->toDateTimeString() . PHP_EOL .
            '| 🔗 ' . $link . PHP_EOL .
            '| 📍 ' . request()->ip() . PHP_EOL .
            '| 🧤 ' . '#' . $connector . PHP_EOL .
            $errorCode .
            $errorMessage .
            '| 🎬 ' . $action . PHP_EOL .
            '| ➡️ {' . PHP_EOL . $paramText .
            '| }' . PHP_EOL .
            '| ⬅️ {' . PHP_EOL . $responseText .
            '| }' .
            '-----------------------------------------------------' . PHP_EOL;

        return $message;
    }
}