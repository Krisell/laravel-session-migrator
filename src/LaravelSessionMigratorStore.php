<?php

namespace App;

class Store extends \Illuminate\Session\Store
{
    protected function readFromHandler()
    {
        // Attempt reading from session using the chosen serialization method
        $data = parent::readFromHandler();
        if ($data !== []) {
            return $data;
        }

        // Switch to the other serialization method to see if session data is decodeable that way
        $chosenSerialization = $this->serialization;
        $this->serialization = ($chosenSerialization === 'json') ? 'php' : 'json';

        // Returned decoded data (if any) and revert to chosen serialization method to ensure
        // that is used for further reads and when saving the session data.
        return tap(parent::readFromHandler(), fn () => $this->serialization = $chosenSerialization);
    }
}