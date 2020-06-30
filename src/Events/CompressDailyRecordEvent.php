<?php

namespace SuperPlatform\UnitedTicket\Events;

class CompressDailyRecordEvent
{
    /**
     * @var array 壓每日結算的資料
     */
    public $compressData = [];

    public function __construct(array $compressDate)
    {
        $this->compressData = $compressDate;
    }
}