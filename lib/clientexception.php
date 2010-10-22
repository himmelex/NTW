<?php

if (!defined('NEWTYPE') && !defined('DWORKS')) {
    exit(1);
}

class ClientException extends Exception
{
    public function __construct($message = null, $code = 400) {
        parent::__construct($message, $code);
    }

    // custom string representation of object
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
