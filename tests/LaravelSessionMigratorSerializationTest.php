<?php

namespace Krisell\LarvelSessionMigrator\Tests;

use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Krisell\LaravelSessionMigrator\LaravelSessionMigratorServiceProvider;
use Orchestra\Testbench\TestCase;

class LaravelSessionMigratorSerializationTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [LaravelSessionMigratorServiceProvider::class];
    }

    private function sessionData($data, $serialization)
    {
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
            'app.key' => 'base64:'.base64_encode(Encrypter::generateKey('aes-256-cbc')),
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
        });

        $this->withCookies([
            'laravel_session' => ($name = Str::random(40)),
            $name => $this->sessionData(['php' => true], 'php'),
        ])->get('/correct')->assertOk();

        Route::middleware('web')->get('/incorrect', function () {
            $this->assertNull(session('json'));
        });

        $this->withCookies([
            'laravel_session' => ($name = Str::random(40)),
            $name => $this->sessionData(['json' => true], 'json'),
        ])->get('/incorrect')->assertOk();
    }

    /** @test */
    public function json_serialization_does_not_accept_php()
    {
        config(['session.serialization' => 'json']);

        Route::middleware('web')->get('/correct', function () {
            $this->assertTrue(session('json'));
        });

        $this->withCookies([
            'laravel_session' => ($name = Str::random(40)),
            $name => $this->sessionData(['json' => true], 'json'),
        ])->get('/correct')->assertOk();

        Route::middleware('web')->get('/incorrect', function () {
            $this->assertNull(session('php'));
        });

        $this->withCookies([
            'laravel_session' => ($name = Str::random(40)),
            $name => $this->sessionData(['php' => true], 'php'),
        ])->get('/incorrect')->assertOk();
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
        });

        $this->withCookies([
            'laravel_session' => ($name = Str::random(40)),
            $name => $this->sessionData(['php' => true], 'php'),
        ])->get('/correct')->assertOk();
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
        });

        $this->withCookies([
            'laravel_session' => ($name = Str::random(40)),
            $name => $this->sessionData(['json' => true], 'json'),
        ])->get('/correct')->assertOk();
    }

    /** @test */
    public function the_migrator_store_defaults_to_empty_array_for_invalid_data()
    {
        config([
            'session.serialization' => 'php',
            'session.migrate.serialization' => true,
        ]);

        Route::middleware('web')->get('/correct', function () {
            $this->assertEquals([], Arr::except(session()->all(), '_token'));
        });

        $this->withCookies([
            'laravel_session' => ($name = Str::random(40)),
            $name => 'invalid-data',
        ])->get('/correct')->assertOk();
    }

    /** @test */
    public function sessions_can_still_be_encrypted_with_migration_activated()
    {
        config([
            'session.migrate.serialization' => true,
            'session.encrypt' => true,
        ]);

        Route::middleware('web')->get('/session', function () {
            return session()->all();
        });

        $encrypter = session()->driver()->getEncrypter();

        $data = json_encode([
            'data' => $encrypter->encrypt(serialize(['php' => true])),
            'expires' => time() + 100,
        ]);

        $this->withCookies([
            'laravel_session' => ($name = Str::random(40)),
            $name => $data,
        ])->get('/session')->assertOk();

        $this->assertTrue(json_decode($this->get('/session')->getContent())->php);
    }

    /** @test */
    public function invalid_decryption_defaults_to_empty_array()
    {
        config([
            'session.migrate.serialization' => true,
            'session.encrypt' => true,
        ]);

        Route::middleware('web')->get('/session', function () {
            return session()->all();
        });

        $encrypter = session()->driver()->getEncrypter();

        $data = json_encode([
            'data' => 'invalid',
            'expires' => time() + 100,
        ]);

        $this->withCookies([
            'laravel_session' => ($name = Str::random(40)),
            $name => $data,
        ])->get('/session')->assertOk();
    }
}
