<?php

namespace SuperPlatform\UnitedTicket\Models;

use Illuminate\Database\Eloquent\Model;

class BlockUnitedTicket extends Model
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
        'sum_raw_bet',
        'sum_valid_bet',
        'sum_rolling',
        'sum_winnings',
        'time_span_begin',
        'time_span_end',
    ];
}
