<?php

if (!defined('NEWTYPE') && !defined('DWORKS')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/error.php';

class ServerErrorAction extends ErrorAction
{
    static $status = array(500 => 'Internal Server Error',
                           501 => 'Not Implemented',
                           502 => 'Bad Gateway',
                           503 => 'Service Unavailable',
                           504 => 'Gateway Timeout',
                           505 => 'HTTP Version Not Supported');

    function __construct($message='Error', $code=500, $ex=null)
    {
        parent::__construct($message, $code);

        $this->default = 500;

        // Server errors must be logged.
        $log = "ServerErrorAction: $code $message";
        if ($ex) {
            $log .= "\n" . $ex->getTraceAsString();
        }
        common_log(LOG_ERR, $log);
    }

    // XXX: Should these error actions even be invokable via URI?

    function handle($args)
    {
        parent::handle($args);

        $this->code = $this->trimmed('code');

        if (!$this->code || $code < 500 || $code > 599) {
            $this->code = $this->default;
        }

        $this->message = $this->trimmed('message');

        if (!$this->message) {
            $this->message = "Server Error $this->code";
        }

        $this->showPage();
    }
}
