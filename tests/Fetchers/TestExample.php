<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

interface TestExample
{
    /**
     * 測試產生的 uuid 主鍵是否正確
     */
    public function testRawTicketUuid(): void;

    /**
     * 測試注單抓取器 capture 方法是否正確
     */
    public function testSuccessCapture(): void;
}