<?php

namespace SuperPlatform\UnitedTicket\Models;

/**
 * 「好盈」原生注單
 *
 * @package SuperPlatform\UnitedTicket\Models
 */
class HyTicket extends Model
{
    protected $table = 'raw_tickets_hy';

    public function getUuidAttribute()
    {
        // TODO: Implement setUuidAttribute() method.
    }
}

