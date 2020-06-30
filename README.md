# 整合注單模組

整合注單模組主要處理三個工作

+ 原生注單抓取 (fetcher)： 將第三方遊戲的原生注單抓回至本機儲存
+ 原生注單轉換 (converter)： 轉換抓回來原生注單為整合式注單
+ 整合注單查詢： 為整合式注單提供方便的查詢功能

## 安裝
因為是私有版控庫，安裝此 package 的專案必需在自己的 composer.json 先定義版控庫來源

    "repositories": [
        {
            "type": "git",
            "url": "git@git.sp168.cc:super-platform/united-ticket.git"
        },
    ],

接著就可以透過下列指令進行安裝

    composer require super-platform/united-ticket

如果 Laravel 版本在 5.4 以下，你必需手動追加 ServerProvider

    // config/app.php
    'providers' => [
        ...
        SuperPlatform\UnitedTicket\UnitedTicketServiceProvider::class,
    ],

如果不確定，就老實的使用手動追加最保險的方式

然後必須手動下 `php artisan vendor:publish` 選擇此專案進行發佈載入 migration

## 使用方法

### 原生注單同步(抓取 & 轉換)

#### 可用的 artisan 指令

    // 列出目前可以進行同步的遊戲站識別碼
    php artisan united-ticket:stations 

    // 同步指定{遊戲站}-{帳號}-{指定開始時間}-{指定結束時間} 的原生注單
    php artisan ticket-integrator:transfer {station} {username} {--startTime} {--endTime}

#### 使用排程定時同步

    // app/Console/Kernel.php

    use SuperPlatform\StationWallet\StationLoginRecord;
    use SuperPlatform\UnitedTicket\Jobs\RawTicketSyncJob;

    protected function schedule(Schedule $schedule)
    { 
        /* 注單抓取範圍 */
        $all_bet_range_days = 14; 
        $sa_gaming_range_days = 7;
        
        // 各遊戲站當日有登入過的會員
        $users = StationLoginRecord::getRecord(['status' => 'clicked'])->groupBy('station');
        
        $schedule->call(function () use ($users) {
            // 同步歐博的帳號注單
            $usersInAllBet = array_get($users, 'all_bet', []);
            $usersInGame->each(function ($item) {
                    $userInGame = [
                        'username' => $item->account,
                        'user_id' => $item->user_id
                    ];
        
                    dispatch(new RawTicketSyncJob('all_bet', $userInGame, now()->subDay(14)->toDateTimeString(), now()->toDateTimeString()))
                        ->onQueue('united-ticket');
                });
                
            // 同步沙龍的帳號注單
            $usersInSaGaming = array_get($users, 'sa_gaming', []);
            $usersInGame->each(function ($item) {
                    $userInGame = [
                        'username' => $item->account,
                        'user_id' => $item->user_id
                    ];
        
                    dispatch(new RawTicketSyncJob('sa_gaming', $userInGame, now()->subDay(7)->toDateTimeString(), now()->toDateTimeString()))
                        ->onQueue('united-ticket');
                });
            
            
        })
        // 每 1 分鐘執行這個任務
        ->everyMinute();
    }
    
#### 使用手動同步

    use SuperPlatform\UnitedTicket\Jobs\RawTicketSyncJob;
    
    dispatch(new RawTicketSyncJob($station, $username, $startTime, $endTime)
                    ->onQueue('united-ticket'));
    
### 整合注單查詢

#### 可用的 artisan 指令

    // 建立 {遊戲站} {--指定時間點} 此時間點的「上一個刻鐘區段」的預查結果
    php artisan united-ticket:stored-block {--station} {--start} {--end}
    
#### 使用排程定時建立預查結果(不指定遊戲站就會選全部)

    // app/Console/Kernel.php

    use SuperPlatform\UnitedTicket\Jobs\StoredBlockJob;

    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () use ($stations) {
            dispatch((new StoredBlockJob())->init())->onQueue('united-ticket-block');
        })
        // 每 15 分鐘執行這個任務
        ->everyFifteenMinutes()
        // 在執行之前戳一下
        ->pingBefore($url)
        // 任務完成後戳一下
        ->thenPing($url);
    }
        
#### 查詢範例

查詢遊戲站 sa_gaming，帳號 hero 在下列時段

+ `2017-08-01 12:00:00 ~ 2017-08-01 12:15:00`

的贏輸結果
    
出來結果範例

| user_identify              | station   | game_type | bet_type | sum_raw_bet | sum_valid_bet | sum_rolling | sum_winnings | time_span_begin     | time_span_end       | depth_1_identify |depth_1_ratio|
|----------------------------|-----------|-----------|----------|-------------|---------------|-------------|--------------|---------------------|---------------------|------------------|-------------|
| 000zmep5qb9asdktz10f60xmm2 | sa_gaming | sicbo     | big      | 340         | 340           | 340         | 680          | 2017-08-01 12:13:14 | 2017-08-02 22:23:23 | root_id          |0.97         |   
| 000zmep5qb9asdktz10f60xmm2 | sa_gaming | sicbo     | big      | 520         | 520           | 520         | 1040         | 2017-08-02 22:23:24 | 2017-08-03 04:44:32 | user_1_id        |0.98         | 
| 000zmep5qb9asdktz10f60xmm2 | sa_gaming | slot      | spin     | 246         | 246           | 246         | 442          | 2017-08-01 12:13:14 | 2017-08-02 22:23:23 | user_2_id        |0.99         |
| 000zmep5qb9asdktz10f60xmm2 | sa_gaming | slot      | spin     | 333         | 333           | 333         | -333         | 2017-08-02 22:23:24 | 2017-08-03 04:44:32 | user_3_id        |0.98         |
 
 ### 整合注單更新
 
 ### 可用的 artisan 指令
    // 每日更新today_tickets和yesterday_tickets redis資料庫資料
    php artisan united-ticket:daily-refresh
    
    // 每週更新this_week_tickets和last_week_tickets redis資料庫資料
    php artisan united-ticket:weekly-refresh
    
    // 每月更新this_month_tickets和last_month_tickets redis資料庫資料
    php artisan united-ticket:monthly-refresh
    
#### 使用排程定時更新資料

    // app/Console/Kernel.php

    use SuperPlatform\UnitedTicket\Jobs\DailyRefreshBlockJob;
    use SuperPlatform\UnitedTicket\Jobs\WeeklyRefreshBlockJob;
    use SuperPlatform\UnitedTicket\Jobs\MonthlyRefreshBlockJob;

    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            dispatch(new DailyRefreshBlockJob())->onQueue('daily-refresh-block'));
        })
        // 每日00:00做更新
        ->dailyAt('00:00')
        // 在執行之前戳一下
        ->pingBefore($url)
        // 任務完成後戳一下
        ->thenPing($url);
        
        $schedule->call(function () {
            dispatch(new WeeklyRefreshBlockJob())->onQueue('weekly-refresh-block'));
        })
        // 每週一 00:00做更新
        ->weeklyOn(0, '00:00')
        // 在執行之前戳一下
        ->pingBefore($url)
        // 任務完成後戳一下
        ->thenPing($url);
        
        $schedule->call(function () {
            dispatch(new MonthlyRefreshBlockJob())->onQueue('monthly-refresh-block'));
        })
        // 每月1號 00:00做更新
        ->monthlyOn(1, '00:00')
        // 在執行之前戳一下
        ->pingBefore($url)
        // 任務完成後戳一下
        ->thenPing($url);
    }
