<?php

namespace Krisell\LarvelSessionMigrator\Tests;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Encryption\Encrypter;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Support\Str;
use Krisell\LaravelSessionMigrator\LaravelSessionMigratorServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LaravelSessionMigratorDriverTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [LaravelSessionMigratorServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.key' => 'base64:'.base64_encode(Encrypter::generateKey('aes-256-cbc'))
        ]);
    }

    private function sessionData($data) {
        return json_encode([
            'data' => serialize($data),
            'expires' => time() + 100,
        ]);
    }

    /** @test */
    public function switching_drivers_looses_session_by_default()
    {
        config(['session.driver' => 'file']);
        session(['some' => 'data']);
        config(['session.driver' => 'cookie']);

        $this->assertEmpty(session()->all());
    }

    /** @test */
    public function session_data_can_be_migrated_from_cookie_to_file_driver()
    {
        config([
            'session.driver' => 'file',
            'session.migrate.driver' => 'cookie',
        ]);
        
        Route::middleware('web')->get('/session', function () {
            $this->assertEquals('data', session('some'));
        });
        
        // This sends session data as a cookie, but the app is set to read from the file driver
        $this->withCookies([
            'laravel_session' => ($name = Str::random(40)),
            $name => $this->sessionData(['some' => 'data']),
        ])->get('/session')->assertOk();
    }

    /** @test */
    public function session_data_can_be_migrated_from_file_to_cookie_driver()
    {
        config([
            'session.driver' => 'file',
        ]);

        session(['some' => 'data']);
        session()->driver()->save();
        $id = session()->getId();
        
        config([
            'session.driver' => 'cookie',
            'session.migrate.driver' => 'file',
        ]);
        
        Route::middleware('web')->get('/session', function () {
            $this->assertEquals('data', session('some'));
        });
        
        $this->withCookies([
            'laravel_session' => $id,
        ])->get('/session')->assertOk();
    }
}