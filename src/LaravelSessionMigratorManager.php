<?php

namespace Krisell\LaravelSessionMigrator;

use Illuminate\Session\SessionManager;

class LaravelSessionMigratorManager extends SessionManager
{
    protected function buildSession($handler)
    {
        return $this->config->get('session.encrypt')
            ? $this->buildEncryptedSession($handler)
            : new LaravelSessionMigratorStore(
                $this->config->get('session.cookie'),
                $handler,
                $id = null,
                $this->config->get('session.serialization', 'php')
            );
    }
}
