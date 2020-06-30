<?php
//
//namespace SuperPlatform\UnitedTicket\Models;
//
///**
// * Class HongChowTicket
// * @package SuperPlatform\UnitedTicket\Models
// */
//class HongChowTicket extends RawTicket
//{
//    protected $table = 'raw_tickets_hong_chow';
//
//    protected $fillable = [
//        'bet_id',
//        'user_name',
//        'bettype',
//        'betamount',
//        'refundamount',
//        'returnamount',
//        'bettime',
//        'betip',
//        'betsrc',
//        'part_id',
//        'part_name',
//        'part_odds',
//        'game_id',
//        'game_name',
//        'match_name',
//        'race_name',
//        'han_id',
//        'han_name',
//        'team1_name',
//        'team2_name',
//        'round',
//        'result',
//        'reckondate',
//        'status2',
//        'sync_version',
//    ];
//
//    public function getUuidAttribute()
//    {
//        return $this->uniqueToUuid([
//            $this->user_name,
//            $this->bet_id
//        ]);
//    }
//}