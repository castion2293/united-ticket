<?php

namespace SuperPlatform\UnitedTicket\Events;

/**
 * Class FetcherExceptionOccurred
 *
 * 當抓取器呼叫動作發生例外時，觸發此事件
 *
 * @package SuperPlatform\UnitedTicket\Src\Events
 */
class FetcherExceptionOccurred
{
    /**
     * @var \Exception 例外的實體物件
     */
    public $exception;

    /**
     * @var string 連結器名稱（遊戲站）
     */
    public $station;

    /**
     * @var string 觸發動作
     */
    public $action;

    /**
     * @var array 使用參數包
     */
    public $formParams;

    /**
     * @var string 格式化例外訊息（排個版）
     */
    public $formatLog;

    /**
     * @param \Exception $exception
     * @param string $station
     * @param string $action
     * @param array $formParams
     */
    public function __construct(\Exception $exception, string $station, string $action, array $formParams)
    {
        $this->exception = $exception;
        $this->station = $station;
        $this->action = $action;
        $this->formParams = $formParams;
        $this->formatLog = exception_log_format_ticket(
            $exception,
            $station,
            $action,
            $formParams
        );
    }
}