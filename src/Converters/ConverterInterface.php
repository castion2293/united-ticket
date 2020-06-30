<?php

namespace SuperPlatform\UnitedTicket\Converters;

/**
 * 第三方原生注單轉換器
 *
 * @package SuperPlatform\UnitedTicket\Converters
 */
interface ConverterInterface
{
    /**
     * 轉換原生注單為整合注單
     *
     * @param array $rawTickets
     * @param string $userId
     * @return array
     */
    public function transform(array $rawTickets = [], string $userId = ''): array;

}