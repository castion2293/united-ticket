<?php

use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Console\Output\ConsoleOutput;

class BaseTestCase extends TestCase
{
    /**
     * 終端器輸出器
     *
     * @var ConsoleOutput
     */
    protected $console;

    /**
     * 假資料產生器
     *
     * @var \Faker\Factory
     */
    protected $faker;

    /**
     * 測試時的主機位置
     *
     * @var string
     */
    protected $appUrl;

    /**
     * BaseTestCase constructor.
     *
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->console = new ConsoleOutput();
        $this->faker = \Faker\Factory::create();
    }


    /**
     * 測試時的 Package Providers 設定
     *
     *  ( 等同於原 laravel 設定 config/app.php 的 Autoloaded Service Providers )
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            \Orchestra\Database\ConsoleServiceProvider::class,
            SuperPlatform\UnitedTicket\UnitedTicketServiceProvider::class,
        ];
    }

    /**
     * 測試時的 Class Aliases 設定
     *
     * ( 等同於原 laravel 中設定 config/app.php 的 Class Aliases )
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [

        ];
    }

    /**
     * 測試時的時區設定
     *
     * ( 等同於原 laravel 中設定 config/app.php 的 Application Timezone )
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return string|null
     */
    protected function getApplicationTimezone($app)
    {
        return 'Asia/Taipei';
    }

    /**
     * 測試時使用的 HTTP Kernel
     *
     * ( 等同於原 laravel 中 app/HTTP/kernel.php )
     * ( 若需要用自訂時，把 Orchestra\Testbench\Http\Kernel 改成自己的 )
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function resolveApplicationHttpKernel($app)
    {
        $app->singleton(
            'Illuminate\Contracts\Http\Kernel',
            'Orchestra\Testbench\Http\Kernel'
        );
    }

    /**
     * 測試時使用的 Console Kernel
     *
     * ( 等同於原 laravel 中 app/Console/kernel.php )
     * ( 若需要用自訂時，把 Orchestra\Testbench\Console\Kernel 改成自己的 )
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function resolveApplicationConsoleKernel($app)
    {
        $app->singleton(
            'Illuminate\Contracts\Console\Kernel',
            'Orchestra\Testbench\Console\Kernel'
        );
    }

    /**
     * 測試時的環境設定
     *
     * @param \Illuminate\Foundation\Application $app
     * @throws \Exception
     */
    protected function getEnvironmentSetUp($app)
    {
        // 若有環境變數檔案，嘗試著讀取使用
        if (file_exists(dirname(__DIR__) . '/.env')) {
            $dotenv = new Dotenv\Dotenv(dirname(__DIR__));
            $dotenv->load();
        }

        // 定義測試時使用的資料庫
        // 擴充一個測試專用的 "testing" 連線設定
        // 並將測試的連線切至 "testing"
        $app['config']->set('database.connections.testing', [
            'driver' => env('TEST_DB_DRIVER', 'sqlite'),
            'read' => [
                'host' => env('TEST_DB_HOST_READ', 'localhost'),
            ],
            'write' => [
                'host' => env('TEST_DB_HOST_WRITE', 'localhost'),
            ],
            'host' => env('TEST_DB_HOST', 'localhost'),
            'database' => env('TEST_DB_DATABASE', ':memory:'),
            'port' => env('TEST_DB_PORT'),
            'username' => env('TEST_DB_USERNAME'),
            'password' => env('TEST_DB_PASSWORD'),
            'unix_socket' => env('TEST_DB_SOCKET', ''),
            'prefix' => env('TEST_DB_PREFIX'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'strict' => true,
            'engine' => null,
            'modes' => [
                //'ONLY_FULL_GROUP_BY', // Disable this to allow grouping by one column
                'STRICT_TRANS_TABLES',
                'NO_ZERO_IN_DATE',
                'NO_ZERO_DATE',
                'ERROR_FOR_DIVISION_BY_ZERO',
                'NO_AUTO_CREATE_USER',
                'NO_ENGINE_SUBSTITUTION'
            ],
        ]);
        $app['config']->set('database.default', 'testing');

        // 檢查目前測試的資料庫連線
        $databaseDefault = config("database.default");
        $driver = config("database.connections.{$databaseDefault}.driver");

        // 若 driver 是 sqlite，檢查 pdo_sqlite 的 extension
        if ($driver === 'sqlite' && !extension_loaded('pdo_sqlite')) {
            throw new \Exception("Please install pdo_sqlite extension.");
        }

        // 若 driver 是 mysql，避免 MySQL 版本過舊產生的問題
        if ($driver === 'mysql') {
            Schema::defaultStringLength(191);
            // 如果 migration 有定義約束，暫時不理會
            Schema::disableForeignKeyConstraints();
        }

    }

    /**
     * 全域測試初始設置
     */
    public function setUp()
    {
        parent::setUp();

        // 只有套件的 database/migrations 資料夾存在時
        // 才會載入套件的 migrations 檔案
        if (file_exists(__DIR__ . '/../database/migrations')) {
            $this->loadMigrationsFrom([
                '--database' => 'testing',
                '--realpath' => realpath(__DIR__ . '/../database/migrations'),
            ]);
        }

        // 只有套件的 database/factories 資料夾存在時
        // 才會載入輔助資料產生的工廠類別
        if (file_exists(__DIR__ . '/../database/factories')) {
            $this->withFactories(__DIR__ . '/../database/factories');
        }

        // 如果有寫到路由註冊之類的 RouteRegistrarAll
        // 在測試時就需要定義好對外 URL (例如使用了 ngrok 之類的)
        $this->appUrl = env("TEST_APP_URL", env('APP_URL'));
    }

    /**
     * 初始化 mock 物件
     *
     * (換句話說就是跟 app 說，等一下如果有用到某個 class 的話，都用我提供的 $mock 這個版本)
     *
     * @param $class
     * @return Mockery\MockInterface
     */
    public function initMock($class)
    {
        $mock = Mockery::mock($class);
        app()->instance($class, $mock);
        return $mock;
    }

}
