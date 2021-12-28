<?php

namespace Pharaoh\GameHub\Tests;

use Dotenv\Dotenv;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Console\Output\ConsoleOutput;
use Faker\Factory as FakerFactory;
use Mockery;

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
     * @var FakerFactory
     */
    protected $faker;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->console = new ConsoleOutput();
        $this->faker = FakerFactory::create();
    }

    /**
     * 測試時的 Package Providers 設定
     *
     *  ( 等同於原 laravel 設定 config/app.php 的 Autoloaded Service Providers )
     *
     * @param App $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            'Pharaoh\GameHub\GameHubServiceProvider',
            'Pharaoh\Logger\LoggerServiceProvider'
        ];
    }

    /**
     * 測試時的 Class Aliases 設定
     *
     * ( 等同於原 laravel 中設定 config/app.php 的 Class Aliases )
     *
     * @param App $app
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
     * @param App $app
     * @return string|null
     */
    protected function getApplicationTimezone($app)
    {
        return env('APP_TIMEZONE');
    }

    /**
     * 測試時使用的 HTTP Kernel
     *
     * ( 等同於原 laravel 中 app/HTTP/kernel.php )
     * ( 若需要用自訂時，把 Orchestra\Testbench\Http\Kernel 改成自己的 )
     *
     * @param App $app
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
     * @param App $app
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
     * @param App $app
     * @throws Exception
     */
    protected function getEnvironmentSetUp($app)
    {
        // 擴充一個測試專用的 "testing" 連線設定
        // 並將測試的連線切至 "testing"
        $app['config']->set('database.connections.testing', [
            'driver' => env('TEST_DB_DRIVER', 'mysql'),
            'read' => [
                'host' => env('TEST_DB_HOST_READ', '127.0.0.1'),
            ],
            'write' => [
                'host' => env('TEST_DB_HOST_WRITE', '127.0.0.1'),
            ],
            'host' => env('TEST_DB_HOST', '127.0.0.1'),
            'database' => env('TEST_DB_DATABASE', 'testing'),
            'port' => env('TEST_DB_PORT', 3306),
            'username' => env('TEST_DB_USERNAME', 'root'),
            'password' => env('TEST_DB_PASSWORD', 'root'),
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

        // 擴充一個測試專用的 "cache" store設定
        $app['config']->set('cache.stores', [
            'redis' => [
                'driver' => 'redis',
                'connection' => 'cache',
            ],
        ]);

        $app['config']->set('cache.default', 'redis');

        // 設定讀取env
        if (file_exists(dirname(__DIR__) . '/.env')) {
            $dotenv = Dotenv::createImmutable(dirname(__DIR__));
            $dotenv->load();
        }
    }

    /**
     * 全域測試初始設置
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 只有套件的 database/migrations 資料夾存在時
        // 才會載入套件的 migrations 檔案
        if (file_exists(__DIR__ . '/../database/migrations')) {
            $this->loadMigrationsFrom(
                ['--database' => 'testing',
                    '--path' => realpath(__DIR__ . '/../database/migrations'),
                    '--realpath' => true,
                ]
            );
        }

        // 載入 tests/Database/Migrations
        if (file_exists(__DIR__ . '/Database/Migrations')) {
            $this->loadMigrationsFrom(
                ['--database' => 'testing',
                    '--path' => realpath(__DIR__ . '/Database/Migrations'),
                    '--realpath' => true,
                ]
            );
        }

        // 只有套件的 database/factories 資料夾存在時
        // 才會載入輔助資料產生的工廠類別
        if (file_exists(__DIR__ . '/../database/factories')) {
            $this->withFactories(__DIR__ . '/../database/factories');
        }

        // 改變 storage_path 路徑至 /tests/storage/
        app()->bind('path.storage', function () {
            return __DIR__ . '/storage';
        });
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
