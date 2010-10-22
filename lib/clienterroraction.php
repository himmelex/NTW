<?php

if (!defined('NEWTYPE') && !defined('DWORKS')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/error.php';

class ClientErrorAction extends ErrorAction
{
    static $status = array(400 => 'Bad Request',
                           401 => 'Unauthorized',
                           402 => 'Payment Required',
                           403 => 'Forbidden',
                           404 => 'Not Found',
                           405 => 'Method Not Allowed',
                           406 => 'Not Acceptable',
                           407 => 'Proxy Authentication Required',
                           408 => 'Request Timeout',
                           409 => 'Conflict',
                           410 => 'Gone',
                           411 => 'Length Required',
                           412 => 'Precondition Failed',
                           413 => 'Request Entity Too Large',
                           414 => 'Request-URI Too Long',
                           415 => 'Unsupported Media Type',
                           416 => 'Requested Range Not Satisfiable',
                           417 => 'Expectation Failed');

    function __construct($message='Error', $code=400)
    {
        parent::__construct($message, $code);
        $this->default = 400;
    }

    // XXX: Should these error actions even be invokable via URI?

    function handle($args)
    {
        parent::handle($args);

        $this->code = $this->trimmed('code');

        if (!$this->code || $code < 400 || $code > 499) {
            $this->code = $this->default;
        }

        $this->message = $this->trimmed('message');

        if (!$this->message) {
            $this->message = "Client Error $this->code";
        }

        $this->showPage();
    }
}
