<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Handler for posting new notices
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Personal
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @author    Zach Copley <zach@status.net>
 * @author    Sarven Capadisli <csarven@status.net>
 * @copyright 2008-2009 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('NEWTYPE') && !defined('DWORKS')) {
    exit(1);
}

require_once INSTALLDIR . '/lib/noticelist.php';
require_once INSTALLDIR . '/lib/mediafile.php';

/**
 * Action for posting new notices
 *
 * @category Personal
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @author   Zach Copley <zach@status.net>
 * @author   Sarven Capadisli <csarven@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */

class NewnoticeAction extends Action
{
    /**
     * Error message, if any
     */

    var $msg = null;

    /**
     * Title of the page
     *
     * Note that this usually doesn't get called unless something went wrong
     *
     * @return string page title
     */

    function title()
    {
        return _('New notice');
    }

    /**
     * Handle input, produce output
     *
     * Switches based on GET or POST method. On GET, shows a form
     * for posting a notice. On POST, saves the results of that form.
     *
     * Results may be a full page, or just a single notice list item,
     * depending on whether AJAX was requested.
     *
     * @param array $args $_REQUEST contents
     *
     * @return void
     */

    function handle($args)
    {
        if (!common_logged_in()) {
            $this->clientError(_('Not logged in.'));
        } else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // check for this before token since all POST and FILES data
            // is losts when size is exceeded
            if (empty($_POST) && $_SERVER['CONTENT_LENGTH']) {
                $this->clientError(sprintf(_('The server was unable to handle ' .
                                             'that much POST data (%s bytes) due to its current configuration.'),
                                           $_SERVER['CONTENT_LENGTH']));
            }
            parent::handle($args);

            // CSRF protection
            $token = $this->trimmed('token');
            if (!$token || $token != common_session_token()) {
                $this->clientError(_('网页错误,请返回重试
                                     '));
            }
            try {
                $this->saveNewNotice();
            } catch (Exception $e) {
                $this->showForm($e->getMessage());
                return;
            }
        } else {
            $this->showForm();
        }
    }

    /**
     * Save a new notice, based on arguments
     *
     * If successful, will show the notice, or return an Ajax-y result.
     * If not, it will show an error message -- possibly Ajax-y.
     *
     * Also, if the notice input looks like a command, it will run the
     * command and show the results -- again, possibly ajaxy.
     *
     * @return void
     */

    function saveNewNotice()
    {
        $user = common_current_user();
        assert($user); // XXX: maybe an error instead...
        $content = $this->trimmed('status_textarea');

        if (!$content) {
            $this->clientError(_('No content!'));
            return;
        }

        $inter = new CommandInterpreter();

        $cmd = $inter->handle_command($user, $content);

        if ($cmd) {
            if ($this->boolean('ajax')) {
                $cmd->execute(new AjaxWebChannel($this));
            } else {
                $cmd->execute(new WebChannel($this));
            }
            return;
        }

        $content_shortened = common_shorten_links($content);
        if (Notice::contentTooLong($content_shortened)) {
            $this->clientError(sprintf(_('That\'s too long. '.
                                         'Max notice size is %d chars.'),
                                       Notice::maxContent()));
        }

        $replyto = $this->trimmed('inreplyto');
        #If an ID of 0 is wrongly passed here, it will cause a database error,
        #so override it...
        if ($replyto == 0) {
            $replyto = 'false';
        }

        $upload = null;
        $upload = MediaFile::fromUpload('attach');

        if (isset($upload)) {

            $content_shortened .= ' ' . $upload->shortUrl();

            if (Notice::contentTooLong($content_shortened)) {
                $upload->delete();
                $this->clientError(
                    sprintf(
                        _('Max notice size is %d chars, including attachment URL.'),
                          Notice::maxContent()
                    )
                );
            }
        }

        $options = array('reply_to' => ($replyto == 'false') ? null : $replyto);

        if ($user->shareLocation()) {
            // use browser data if checked; otherwise profile data
            if ($this->arg('notice_data-geo')) {
                $locOptions = Notice::locationOptions($this->trimmed('lat'),
                                                      $this->trimmed('lon'),
                                                      $this->trimmed('location_id'),
                                                      $this->trimmed('location_ns'),
                                                      $user->getProfile());
            } else {
                $locOptions = Notice::locationOptions(null,
                                                      null,
                                                      null,
                                                      null,
                                                      $user->getProfile());
            }

            $options = array_merge($options, $locOptions);
        }

        $notice = Notice::saveNew($user->id, $content_shortened, 'web', $options);

        if (isset($upload)) {
            $upload->attachToNotice($notice);
        }

        if ($this->boolean('ajax')) {
            header('Content-Type: text/xml;charset=utf-8');
            $this->xw->startDocument('1.0', 'UTF-8');
            $this->elementStart('html');
            $this->elementStart('head');
            $this->element('title', null, _('Notice posted'));
            $this->elementEnd('head');
            $this->elementStart('body');
            $this->showNotice($notice);
            $this->elementEnd('body');
            $this->elementEnd('html');
        } else {
            $returnto = $this->trimmed('returnto');

            if ($returnto) {
                $url = common_local_url($returnto,
                                        array('nickname' => $user->nickname));
            } else {
                $url = common_local_url('shownotice',
                                        array('notice' => $notice->id));
            }
            common_redirect($url, 303);
        }
    }

    /**
     * Show an Ajax-y error message
     *
     * Goes back to the browser, where it's shown in a popup.
     *
     * @param string $msg Message to show
     *
     * @return void
     */

    function ajaxErrorMsg($msg)
    {
        $this->startHTML('text/xml;charset=utf-8', true);
        $this->elementStart('head');
        $this->element('title', null, _('Ajax Error'));
        $this->elementEnd('head');
        $this->elementStart('body');
        $this->element('p', array('id' => 'error'), $msg);
        $this->elementEnd('body');
        $this->elementEnd('html');
    }

    /**
     * Formerly page output
     *
     * This used to be the whole page output; now that's been largely
     * subsumed by showPage. So this just stores an error message, if
     * it was passed, and calls showPage.
     *
     * Note that since we started doing Ajax output, this page is rarely
     * seen.
     *
     * @param string $msg An error message, if any
     *
     * @return void
     */

    function showForm($msg=null)
    {
        if ($msg && $this->boolean('ajax')) {
            $this->ajaxErrorMsg($msg);
            return;
        }

        $this->msg = $msg;
        $this->showPage();
    }

    /**
     * Overload for replies or bad results
     *
     * We show content in the notice form if there were replies or results.
     *
     * @return void
     */

    function showNoticeForm()
    {
        $content = $this->trimmed('status_textarea');
        if (!$content) {
            $replyto = $this->trimmed('replyto');
            $inreplyto = $this->trimmed('inreplyto');
            $profile = Profile::staticGet('nickname', $replyto);
            if ($profile) {
                $content = '@' . $profile->nickname . ' ';
            }
        } else {
            // @fixme most of these bits above aren't being passed on above
            $inreplyto = null;
        }

        $notice_form = new NoticeForm($this, '', $content, null, $inreplyto);
        $notice_form->show();
    }

    /**
     * Show an error message
     *
     * Shows an error message if there is one.
     *
     * @return void
     *
     * @todo maybe show some instructions?
     */

    function showPageNotice()
    {
        if ($this->msg) {
            $this->element('p', array('id' => 'error'), $this->msg);
        }
    }

    /**
     * Output a notice
     *
     * Used to generate the notice code for Ajax results.
     *
     * @param Notice $notice Notice that was saved
     *
     * @return void
     */

    function showNotice($notice)
    {
        $nli = new NoticeListItem($notice, $this);
        $nli->show();
    }
}

