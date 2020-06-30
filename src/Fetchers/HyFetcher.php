<?php

namespace SuperPlatform\UnitedTicket\Fetchers;

use SuperPlatform\ApiCaller\Exceptions\ApiCallerException;

class HyFetcher extends Fetcher
{
    /**
     * 設定查詢的區間
     *
     * @param string $sFromTime
     * @param string $sToTime
     * @return Fetcher
     */
    public function setTimeSpan(string $sFromTime = '', string $sToTime = ''): Fetcher
    {
        // TODO: Implement setTimeSpan() method.
    }

    /**
     * @return array
     * @throws ApiCallerException
     */
    public function capture(): array
    {
        // TODO: Implement capture() method.
    }
}