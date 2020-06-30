<?php

namespace SuperPlatform\UnitedTicket\Converters;

use App\Models\Agent;
use SuperPlatform\NodeTree\NodeTree;
use SuperPlatform\AllotTable\AllotTable;
use SuperPlatform\AllotTable\RebateTable;

/**
 * 第三方原生注單轉換器
 *
 * @package SuperPlatform\UnitedTicket\Converters
 */
abstract class Converter implements ConverterInterface
{
    /**
     * @var NodeTree
     */
    private $nodeTree;

    /**
     * @var AllotTable
     */
    private $allotTable;

    /**
     * @var
     */
    private $rebateTable;

    public function __construct()
    {
        $this->agent =  app()->make(Agent::class);
        $this->nodeTree = app()->make(NodeTree::class);
        $this->allotTable = app()->make(AllotTable::class);
        $this->rebateTable = app()->make(RebateTable::class);
    }

    /**
     * 查找各階輸贏佔成
     *
     * @param string $userIdentify
     * @param string $station
     * @param string $gameScope
     * @param string $betAt
     * @return array
     */
    protected function findAllotment(string $userIdentify, string $station, string $gameScope, string $betAt)
    {
        $userTreeNode = $this->nodeTree->findNodeByDateTime($userIdentify, $betAt);

        $allotments = [];

        if (is_string($userTreeNode['ancestor_ids'])) {
            $userTreeNode['ancestor_ids'] = explode(',', $userTreeNode['ancestor_ids']);
        }

        foreach ($userTreeNode['ancestor_ids'] as $index => $ancestor_id) {
            $allotTable = $this->allotTable->getAllotmentByIdStationScope(
                $ancestor_id, // 代理識別碼
                $station, // 遊戲站
                $gameScope, // 類型
                $betAt // 查詢的時間點
            );
            $allotments[$index] = [
                'level' => $index, // 層級深度
                'username' => $this->agent->find($ancestor_id)->username, // 代理帳號
                'ratio' => array_get($allotTable, 'allotment', 0), // 代理佔成
                'allot_start' => array_get($allotTable, 'temporal_start', ''),
                'allot_end' => array_get($allotTable, 'temporal_end', ''),
            ];
        }

        // 先把占程表做成 1 個Array，然後再跟 rawTicket Array merge
        $i = 0;
        $allotTableArray = [];
        foreach ($allotments as $table) {
            $allotTableArray["depth_{$i}_identify"] = $table['username'];
            $allotTableArray["depth_{$i}_ratio"] = $table['ratio'];
            $allotTableArray["depth_{$i}_allot_start"] = $table['allot_start'];
            $allotTableArray["depth_{$i}_allot_end"] = $table['allot_end'];
            $i++;
        }

        return $allotTableArray;
    }

    /**
     * 查找各階水倍差佔成
     *
     * @param string $userIdentify
     * @param string $station
     * @param string $game_scope
     * @param string $betAt
     * @return array
     */
    protected function findRebate(string $userIdentify, string $station, string $game_scope, string $betAt)
    {
        $userTreeNode = $this->nodeTree->findNodeByDateTime($userIdentify, $betAt);

        $rebates = [];

        if (is_string($userTreeNode['ancestor_ids'])) {
            $userTreeNode['ancestor_ids'] = explode(',', $userTreeNode['ancestor_ids']);
        }

        foreach ($userTreeNode['ancestor_ids'] as $index => $ancestor_id) {
            $rebateTable = $this->rebateTable->getRebateByIdStationScope(
                $ancestor_id, // 代理識別碼
                $station, // 遊戲站
                $game_scope, // 類型
                $betAt // 查詢的時間點
            );
            $rebates[$index] = [
                'level' => $index, // 層級深度
                'username' => $this->agent->find($ancestor_id)->username, // 代理帳號
                'rake' => array_get($rebateTable, 'rebate', 0), // 代理佔成
            ];
        }

        // 先把占程表做成 1 個Array，然後再跟 rawTicket Array merge
        $i = 0;
        $rakesTableArray = [];
        foreach ($rebates as $table) {
            $rakesTableArray["depth_{$i}_identify"] = $table['username'];
            $rakesTableArray["depth_{$i}_rake"] = $table['rake'];
            $i++;
        }

        return $rakesTableArray;
    }
}