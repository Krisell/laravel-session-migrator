<?php

namespace Krisell\LarvelSessionMigrator\Tests;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Encryption\Encrypter;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Support\Str;
use Krisell\LaravelSessionMigrator\LaravelSessionMigratorServiceProvider;

class LaravelSessionMigratorSerializationTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [LaravelSessionMigratorServiceProvider::class];
    }

    private function sessionData($data, $serialization) {
        return json_encode([
            'data' => $serialization === 'php' ? serialize($data) : json_encode($data),
            'expires' => time() + 100,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'session.driver' => 'cookie',
            'app.key' => 'base64:'.base64_encode(Encrypter::generateKey('aes-256-cbc'))
        ]);
    }

    /** @test */
    public function the_session_works_as_normal()
    {
        session(['some' => 'data']);

        Route::get('/session', fn () => session()->all());

        $this->assertEquals('data', json_decode($this->get('/session')->getContent())->some);
    }

    /** @test */
    public function session_data_is_php_serialized_by_default()
    {
        session(['a' => 'b']);

        session()->driver()->save();

        $sessionData = cookie()->getQueuedCookies()[0]->getValue();

        try {
            $data = unserialize(json_decode($sessionData)->data);
        } catch (\ErrorException) {
            $this->fail('PHP serialized session data could not be decoded');
        }

        $this->assertEquals('b', $data['a']);
    }

    /** @test */
    public function session_data_can_be_json_serialized()
    {
        config(['session.serialization' => 'json']);
        session(['a' => 'b']);
        session()->driver()->save();

        $sessionData = cookie()->getQueuedCookies()[0]->getValue();

        try {
            $data = json_decode(json_decode($sessionData)->data, true);
        } catch (\ErrorException) {
            $this->fail('JSON serialized session data could not be decoded');
        }

        $this->assertEquals('b', $data['a']);
    }

    /** @test */
    public function default_serialization_does_not_accept_json()
    {
        Route::middleware('web')->get('/correct', function () {
            $this->assertTrue(session('php'));
            session(['performed' => 'correct']);
        });

        $this->withCookies([
            'laravel_session' => ($name = Str::random(40)),
            $name => $this->sessionData(['php' => true], 'php'),
        ])->get('/correct');

        $this->assertEquals('correct', session('performed'));

        Route::middleware('web')->get('/incorrect', function () {
            $this->assertNull(session('json'));
            session(['performed' => 'incorrect']);
        });

        $this->withCookies([
            'laravel_session' => ($name = Str::random(40)),
            $name => $this->sessionData(['json' => true], 'json'),
        ])->get('/incorrect');     
        
        $this->assertEquals('incorrect', session('performed'));
    }

    /** @test */
    public function json_serialization_does_not_accept_php()
    {
        config(['session.serialization' => 'json']);

        Route::middleware('web')->get('/correct', function () {
            $this->assertTrue(session('json'));
            session(['performed' => 'correct']);
        });

        $this->withCookies([
            'laravel_session' => ($name = Str::random(40)),
            $name => $this->sessionData(['json' => true], 'json'),
        ])->get('/correct');

        $this->assertEquals('correct', session('performed'));

        Route::middleware('web')->get('/incorrect', function () {
            $this->assertNull(session('php'));
            session(['performed' => 'incorrect']);
        });

        $this->withCookies([
            'laravel_session' => ($name = Str::random(40)),
            $name => $this->sessionData(['php' => true], 'php'),
        ])->get('/incorrect');     
        
        $this->assertEquals('incorrect', session('performed'));
    }

    /** @test */
    public function session_serialization_can_be_migrated_from_php_to_json()
    {
        config([
            'session.serialization' => 'json',
            'session.migrate.serialization' => true,
        ]);

        Route::middleware('web')->get('/correct', function () {
            $this->assertTrue(session('php'));
            session(['performed' => 'correct']);
        });

        $this->withCookies([
            'laravel_session' => ($name = Str::random(40)),
            $name => $this->sessionData(['php' => true], 'php'),
        ])->get('/correct');

        $this->assertEquals('correct', session('performed'));
    }

    /** @test */
    public function session_serialization_can_be_migrated_from_json_to_php()
    {
        config([
            'session.serialization' => 'php',
            'session.migrate.serialization' => true,
        ]);

        Route::middleware('web')->get('/correct', function () {
            $this->assertTrue(session('json'));
            session(['performed' => 'correct']);
        });

        $this->withCookies([
            'laravel_session' => ($name = Str::random(40)),
            $name => $this->sessionData(['json' => true], 'json'),
        ])->get('/correct');

        $this->assertEquals('correct', session('performed'));
    }
}