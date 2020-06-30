<?php

namespace SuperPlatform\UnitedTicket\Models;

use Illuminate\Support\Arr;
use Illuminate\Database\Query\Expression;
use SuperPlatform\UnitedTicket\Events\ConvertExceptionOccurred;
use SuperPlatform\UnitedTicket\Events\FetcherExceptionOccurred;

trait ReplaceIntoTrait
{

    /**
     * REPLACE INTO 支援 (MySQL 限定)
     *
     * @param $data
     *    [
     *         'colume' => 'value'
     *    ]
     * @return mixed
     */
    public function replaceInto($data)
    {
        $database = $this->getConnection();

        foreach ($data as $item) {
            try {
                $build = $this->newBaseQueryBuilder();
                $build->from($this->getTable());
                $sql = $build->getGrammar()->compileInsert($build, $item);
                $sql = str_replace('insert into', 'replace into', $sql);

                $item = array_values(array_filter(Arr::flatten($item, 1), function ($item) {
                    return !$item instanceof Expression;
                }));

                $database->statement($sql, $item);
            } catch (\Exception $exception) {
                event(new ConvertExceptionOccurred(
                    $exception,
                    '',
                    'replace_into',
                    []
                ));
                throw $exception;
            }
        }

        return true;

//        $build = $this->newBaseQueryBuilder();
//        $build->from($this->getTable());
//        $sql = $build->getGrammar()->compileInsert($build, $data);
//        $sql = str_replace('insert into', 'replace into', $sql);
//
//        $data = array_values(array_filter(Arr::flatten($data, 1), function ($data) {
//            return !$data instanceof Expression;
//        }));
//
//        return $this->getConnection()->statement($sql, $data);
    }

    /**
     * REPLACE INTO 支援 (MySQL 限定)
     *
     * @param $data
     * @return mixed
     */
    public static function replace($data)
    {
        $instance = new static;
        return $instance->replaceInto($data);
    }

}