<?php

if (!defined('NEWTYPE') && !defined('DWORKS')) {
    exit(1);
}

class ErrorAction extends Action
{
    static $status = array();

    var $code    = null;
    var $message = null;
    var $default = null;

    function __construct($message, $code, $output='php://output', $indent=null)
    {
        parent::__construct($output, $indent);

        $this->code = $code;
        $this->message = $message;
        $this->minimal = StatusNet::isApi();

        // XXX: hack alert: usually we aren't going to
        // call this page directly, but because it's
        // an action it needs an args array anyway
        $this->prepare($_REQUEST);
    }

    /**
     *  To specify additional HTTP headers for the action
     *
     *  @return void
     */
    function extraHeaders()
    {
        $status_string = @self::$status[$this->code];
        header('HTTP/1.1 '.$this->code.' '.$status_string);
    }

    /**
     * Display content.
     *
     * @return nothing
     */
    function showContent()
    {
        $this->element('div', array('class' => 'error'), $this->message);
    }

    /**
     * Page title.
     *
     * @return page title
     */

    function title()
    {
        return @self::$status[$this->code];
    }

    function isReadOnly($args)
    {
        return true;
    }

    function showPage()
    {
        if ($this->minimal) {
            // Even more minimal -- we're in a machine API
            // and don't want to flood the output.
            $this->extraHeaders();
            $this->showContent();
        } else {
            parent::showPage();
        }

        // We don't want to have any more output after this
        exit();
    }

    // Overload a bunch of stuff so the page isn't too bloated

    function showBody()
    {
        $this->elementStart('body', array('id' => 'error'));
        $this->elementStart('div', array('id' => 'wrap', 'class' => 'container_24'));
        $this->showHeader();
        $this->showCore();
        $this->showFooter();
        $this->elementEnd('div');
        $this->elementEnd('body');
    }

    function showCore()
    {
        $this->elementStart('div', array('id' => 'core'));
        $this->showContentBlock();
        $this->elementEnd('div');
    }

    function showHeader()
    {
        $this->elementStart('div', array('id' => 'header'));
        $this->showLogo();
        $this->showPrimaryNav();
        $this->elementEnd('div');
    }

}
