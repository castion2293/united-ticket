<?php

namespace SuperPlatform\UnitedTicket\Models;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

/**
 * Class RawTicket
 * @package SuperPlatform\UnitedTicket\Models
 */
abstract class RawTicket extends Model implements RawTicketInterface
{
    use ReplaceIntoTrait;

    protected $primaryKey = 'uuid';

    /**
     * @param array $uniqueFieldsData
     * @return string
     */
    public function uniqueToUuid($uniqueFieldsData = [])
    {
        sort($uniqueFieldsData);
        $uniqueDataString = join('-', $uniqueFieldsData);
        return Uuid::uuid3(Uuid::NAMESPACE_DNS, $uniqueDataString . '@' . class_basename($this));
    }
}