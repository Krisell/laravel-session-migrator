<?php

namespace Krisell\LaravelSessionMigrator;

use SessionHandlerInterface;
use Illuminate\Session\Store;

class LaravelSessionMigratorStore extends Store
{
    protected $previousHandler;
    
    public function __construct($name, SessionHandlerInterface $handler, $id = null, $serialization = 'php', $previousHandler = null)
    {
        parent::__construct($name, $handler, $id, $serialization);

        $this->previousHandler = $previousHandler;
    }

    protected function readFromHandler()
    {
        $methods = [[$this->handler, $this->serialization]];

        if ($this->previousHandler) {
            $methods[] = [$this->previousHandler, $this->serialization];
        }

        if (config('session.migrate.serialization')) {
            foreach ($methods as [$handler]) {
                $methods[] = [$handler, ($this->serialization === 'php') ? 'json' : 'php'];
            }
        }

        // Attempt to read session data using all candidate handlers and serializations and 
        // return data on first successful read.
        foreach ($methods as [$handler, $serialization]) {
            if ($data = $handler->read($this->getId())) {
                $prepared = $this->prepareForUnserialize($data);
                $data = ($serialization === 'json') ? json_decode($prepared, true) : @unserialize($prepared);
    
                if ($data !== false && is_array($data)) {
                    return $data;
                }
            }
        }

        return [];
    }
}