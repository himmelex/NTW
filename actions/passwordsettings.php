<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Change user password
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
 * @category  Settings
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @author    Zach Copley <zach@status.net>
 * @copyright 2008-2009 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('NEWTYPE') && !defined('DWORKS')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/accountsettingsaction.php';

/**
 * Change password
 *
 * @category Settings
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @author   Zach Copley <zach@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */

class PasswordsettingsAction extends AccountSettingsAction
{
    /**
     * Title of the page
     *
     * @return string Title of the page
     */

    function title()
    {
        return _('修改密码');
    }

    /**
     * Instructions for use
     *
     * @return instructions for use
     */

    function getInstructions()
    {
        return _('修改密码');
    }

    function showScripts()
    {
        parent::showScripts();
        $this->autofocus('oldpassword');
    }

    /**
     * Content area of the page
     *
     * Shows a form for changing the password
     *
     * @return void
     */

    function showContent()
    {
        $user = common_current_user();

        $this->elementStart('form', array('method' => 'POST',
                                          'id' => 'form_password',
                                          'class' => 'form_settings',
                                          'action' =>
                                          common_local_url('passwordsettings')));
        $this->elementStart('fieldset');
        $this->hidden('token', common_session_token());


        $this->elementStart('ul', 'form_data');
        // Users who logged in with OpenID won't have a pwd
        if ($user->password) {
            $this->elementStart('li');
            $this->password('oldpassword', _('旧密码'));
            $this->elementEnd('li');
        }
        $this->elementStart('li');
        $this->password('newpassword', _('新密码'),
                        _('6个以上字符'));
        $this->elementEnd('li');
        $this->elementStart('li');
        $this->password('confirm', _('确认新密码'),
                        _('重复输入新密码'));
        $this->elementEnd('li');
        $this->elementEnd('ul');

        $this->submit('changepass', _('修 改'));

        $this->elementEnd('fieldset');
        $this->elementEnd('form');
    }

    /**
     * Handle a post
     *
     * Validate input and save changes. Reload the form with a success
     * or error message.
     *
     * @return void
     */

    function handlePost()
    {
        // CSRF protection

        $token = $this->trimmed('token');
        if (!$token || $token != common_session_token()) {
            $this->showForm(_('网页错误,请返回重试
                               '));
            return;
        }

        $user = common_current_user();
        assert(!is_null($user)); // should already be checked

        // FIXME: scrub input

        $newpassword = $this->arg('newpassword');
        $confirm     = $this->arg('confirm');

        # Some validation

        if (strlen($newpassword) < 6) {
            $this->showForm(_('密码必须是6个以上字符组成'));
            return;
        } else if (0 != strcmp($newpassword, $confirm)) {
            $this->showForm(_('新密码两次输入不一致'));
            return;
        }

        if ($user->password) {
            $oldpassword = $this->arg('oldpassword');

            if (!common_check_user($user->nickname, $oldpassword)) {
                $this->showForm(_('旧密码不正确'));
                return;
            }
        }else{
            $oldpassword = null;
        }

        $success = false;
        if(Event::handle('StartChangePassword', array($user, $oldpassword, $newpassword))){
            //no handler changed the password, so change the password internally
            $original = clone($user);

            $user->password = common_munge_password($newpassword, $user->id);

            $val = $user->validate();
            if ($val !== true) {
                $this->showForm(_('用户资料错误'));
                return;
            }

            if (!$user->update($original)) {
                $this->serverError(_('无法保存新密码,请重试'));
                return;
            }
            Event::handle('EndChangePassword', array($user));
        }

        $this->showForm(_('密码修改成功'), true);
    }
}
