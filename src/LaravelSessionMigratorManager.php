<?php

namespace Krisell\LaravelSessionMigrator;

use Illuminate\Http\Request;
use Illuminate\Session\CookieSessionHandler;
use Illuminate\Session\DatabaseSessionHandler;
use Illuminate\Session\FileSessionHandler;
use Illuminate\Session\SessionManager;
use Illuminate\Session\Store;

class LaravelSessionMigratorManager extends SessionManager
{
    protected function buildSession($handler)
    {
        $previousHandler = $this->getPreviousHandler();

        if (! $this->config->get('session.migrate.serialization') && ! $previousHandler) {
            return parent::buildSession($handler);
        }

        $arguments = [
            $this->config->get('session.cookie'),
            $handler,
            $id = null,
            $this->config->get('session.serialization', 'php'),
            $previousHandler,
        ];

        return $this->config->get('session.encrypt')
            ? new LaravelEncryptedSessionMigratorStore(...$arguments)
            : new LaravelSessionMigratorStore(...$arguments);
    }

    private function getPreviousHandler()
    {
        $from = $this->config->get('session.migrate.driver');

        // If the driver to migrate from is not specified or same as used driver, return null which skips this handling
        if (! $from || $from === $this->config->get('session.driver')) {
            return null;
        }

        $method = 'create'.ucfirst($from).'Handler';

        return method_exists($this, $method) ? $this->$method() : null;
    }

    private function createFileHandler()
    {
        return new FileSessionHandler(
            $this->container->make('files'),
            $this->config->get('session.files'),
            $this->config->get('session.lifetime')
        );
    }

    private function createCookieHandler()
    {
        return tap(
            new CookieSessionHandler(
                $this->container->make('cookie'),
                $this->config->get('session.lifetime')
            ),
            // This is only necessary for the CookieSessionHandler
            fn ($handler) => $handler->setRequest($this->container->make(Request::class)),
        );
    }
}
