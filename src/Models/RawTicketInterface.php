<?php

namespace SuperPlatform\UnitedTicket\Models;

/**
 * 原生注單介面
 *
 * Interface RawTicket
 * @package SuperPlatform\UnitedTicket\Models
 */
interface RawTicketInterface
{
    /**
     * 取得唯一的識別碼
     */
    public function getUuidAttribute();
}