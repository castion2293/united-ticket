<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;

/**
 * 第三方原生注單抓取器
 *
 * Interface Fetcher
 * @package SuperPlatform\UnitedTicket\Fetchers
 */
interface FetcherInterface
{
    /**
     * 設定查詢的區間
     *
     * @param string $fromTime
     * @param string $toTime
     * @return Fetcher
     */
    public function setTimeSpan(string $fromTime = '', string $toTime = ''): Fetcher;

    /**
     *  自動撈單的時間設定
     *
     * @return Fetcher
     */
    public function autoFetchTimeSpan(): Fetcher;

    /**
     * @return array
     * @throws ApiCallerException
     */
    public function capture(): array;

    /**
     * 找出需轉換的注單
     *
     * @param array $fetchTickets
     * @return array
     */
//    public function compare(array $fetchRawTickets): array;
}