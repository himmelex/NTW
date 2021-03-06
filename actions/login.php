<?php
/**
 * Login form
 *
 * PHP version 5
 *
 * @category  Login
 * @package   Dwork
 * @author    Himmel
 */

if (!defined('NEWTYPE') && !defined('DWORKS')) {
    exit(1);
}

/**
 * Login form
 */

class LoginAction extends Action
{
    /**
     * Has there been an error?
     */

    var $error = null;

    /**
     * Is this a read-only action?
     *
     * @return boolean false
     */

    function isReadOnly($args)
    {
        return false;
    }

    /**
     * Handle input, produce output
     *
     * Switches on request method; either shows the form or handles its input.
     *
     * @param array $args $_REQUEST data
     *
     * @return void
     */

    function handle($args)
    {
        parent::handle($args);

        if (common_is_real_login()) {
            $this->clientError(_('Already logged in.'));
        } else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->checkLogin();
        } else {
            common_ensure_session();
            $this->showForm();
        }
    }

    /**
     * Check the login data
     *
     * Determines if the login data is valid. If so, logs the user
     * in, and redirects to the 'with friends' page, or to the stored
     * return-to URL.
     *
     * @return void
     */

    function checkLogin($user_id=null, $token=null)
    {
        // XXX: login throttle

        // CSRF protection - token set in NoticeForm
        $token = $this->trimmed('token');
        if (!$token || $token != common_session_token()) {
	    $st = common_session_token();
	    if (empty($token)) {
		common_log(LOG_WARNING, 'No token provided by client.');
	    } else if (empty($st)) {
		common_log(LOG_WARNING, 'No session token stored.');
	    } else {
		common_log(LOG_WARNING, 'Token = ' . $token . ' and session token = ' . $st);
	    }

            $this->clientError(_('网页错误,请返回重试
                                 '));
            return;
        }

        $nickname = $this->trimmed('nickname');
        $password = $this->arg('password');

        $user = common_check_user($nickname, $password);

        if (!$user) {
            $this->showForm(_('Incorrect username or password.'));
            return;
        }

        // success!
        if (!common_set_user($user)) {
            $this->serverError(_('Error setting user. You are probably not authorized.'));
            return;
        }

        common_real_login(true);

        if ($this->boolean('rememberme')) {
            common_rememberme($user);
        }

        $url = common_get_returnto();

        if ($url) {
            // We don't have to return to it again
            common_set_returnto(null);
	    $url = common_inject_session($url);
        } else {
            $url = common_local_url('all',
                                    array('nickname' =>
                                          $user->nickname));
        }

        common_redirect($url, 303);
    }

    /**
     * Store an error and show the page
     *
     * This used to show the whole page; now, it's just a wrapper
     * that stores the error in an attribute.
     *
     * @param string $error error, if any.
     *
     * @return void
     */

    function showForm($error=null)
    {
        $this->error = $error;
        $this->showPage();
    }

    function showScripts()
    {
        parent::showScripts();
        $this->autofocus('nickname');
    }

    /**
     * Title of the page
     *
     * @return string title of the page
     */

    function title()
    {
        return _('Login');
    }

    /**
     * Show page notice
     *
     * Display a notice for how to use the page, or the
     * error if it exists.
     *
     * @return void
     */

    function showPageNotice()
    {
        if ($this->error) {
            $this->element('p', 'error', $this->error);
        } else {
            $instr  = $this->getInstructions();
            $output = common_markup_to_html($instr);

            $this->raw($output);
        }
    }

    /**
     * Core of the display code
     *
     * Shows the login form.
     *
     * @return void
     */

    function showContent()
    {
        $this->elementStart('form', array('method' => 'post',
                                          'id' => 'form_login',
                                          'class' => 'form_settings',
                                          'action' => common_local_url('login')));
        $this->elementStart('fieldset');
        $this->element('legend', null, _('Login to site'));
        $this->elementStart('ul', 'form_data');
        $this->elementStart('li');
        $this->input('nickname', _('Nickname'));
        $this->elementEnd('li');
        $this->elementStart('li');
        $this->password('password', _('Password'));
        $this->elementEnd('li');
        $this->elementStart('li');
        $this->checkbox('rememberme', _('Remember me'), false,
                        _('Automatically login in the future; ' .
                          'not for shared computers!'));
        $this->elementEnd('li');
        $this->elementEnd('ul');
        $this->submit('submit', _('Login'));
        $this->hidden('token', common_session_token());
        $this->elementEnd('fieldset');
        $this->elementEnd('form');
        $this->elementStart('p');
        $this->element('a', array('href' => common_local_url('recoverpassword')),
                       _('Lost or forgotten password?'));
        $this->elementEnd('p');
    }

    /**
     * Instructions for using the form
     *
     * For "remembered" logins, we make the user re-login when they
     * try to change settings. Different instructions for this case.
     *
     * @return void
     */

    function getInstructions()
    {
        if (common_logged_in() && !common_is_real_login() &&
            common_get_returnto()) {
            // rememberme logins have to reauthenticate before
            // changing any profile settings (cookie-stealing protection)
            return _('For security reasons, please re-enter your ' .
                     'user name and password ' .
                     'before changing your settings.');
        } else {
            $prompt = _('Login with your username and password.');
            if (!common_config('site', 'closed') && !common_config('site', 'inviteonly')) {
                $prompt .= ' ';
                $prompt .= _('Don\'t have a username yet? ' .
                             '[Register](%%action.register%%) a new account.');
            }
            return $prompt;
        }
    }

    /**
     * A local menu
     *
     * Shows different login/register actions.
     *
     * @return void
     */

    function showLocalNav()
    {
        $nav = new LoginGroupNav($this);
        $nav->show();
    }
}
