<?php

namespace SuperPlatform\UnitedTicket\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 整合式注單
 *
 * @package SuperPlatform\UnitedTicket\Models
 */
class UnitedTicket extends Model
{
    use ReplaceIntoTrait;

    public $incrementing = false;

    protected $casts = [
        'id' => 'string',
    ];

    protected $fillable = [
        'user_identify',
        'station',
        'game_scope',
        'bet_type',
        'raw_bet',
        'valid_bet',
        'rolling',
        'winnings',
        'bet_at',
        'payout_at',
    ];
}
