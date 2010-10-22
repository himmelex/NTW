<?php
if (!defined('NEWTYPE') && !defined('DWORKS')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/accountsettingsaction.php';


class EmailsettingsAction extends AccountSettingsAction
{
    /**
     * Title of the page
     *
     * @return string Title of the page
     */

    function title()
    {
        return _('邮件设置');
    }

    /**
     * Instructions for use
     *
     * @return instructions for use
     */

    function getInstructions()
    {
        return _('设置用来获取新消息以及找回密码的邮箱');
    }

    function showScripts()
    {
        parent::showScripts();
        $this->autofocus('email');
    }

    /**
     * Content area of the page
     *
     * Shows a form for adding and removing email addresses and setting
     * email preferences.
     *
     * @return void
     */

    function showContent()
    {
        $user = common_current_user();

        $this->elementStart('form', array('method' => 'post',
                                          'id' => 'form_settings_email',
                                          'class' => 'form_settings',
                                          'action' =>
                                          common_local_url('emailsettings')));
        $this->elementStart('fieldset');
        $this->elementStart('fieldset', array('id' => 'settings_email_address'));
        $this->element('legend', null, _('邮件地址'));
        $this->hidden('token', common_session_token());

        if ($user->email) {
            $this->element('p', array('id' => 'form_confirmed'), $user->email);
            $this->element('p', array('class' => 'form_note'), _('已验证的邮件地址'));
            $this->hidden('email', $user->email);
            $this->submit('remove', _('修 改'));
        } else {
            $confirm = $this->getConfirmation();
            if ($confirm) {
                $this->element('p', array('id' => 'form_unconfirmed'), $confirm->address);
                $this->element('p', array('class' => 'form_note'),
                                        _('等待验证的邮件地址,请登录邮箱查看邮件以确认验证信息'));
                $this->hidden('email', $confirm->address);
                $this->submit('cancel', _('取 消'));
            } else {
                $this->elementStart('ul', 'form_data');
                $this->elementStart('li');
                $this->input('email', _('邮件地址'),
                             ($this->arg('email')) ? $this->arg('email') : null,
                             _('格式如 "abc@example.com"'));
                $this->elementEnd('li');
                $this->elementEnd('ul');
                $this->submit('add', _('修 改'));
            }
        }
        $this->elementEnd('fieldset');

        $this->elementStart('fieldset', array('id' => 'settings_email_preferences'));
        $this->elementStart('ul', 'form_data');
        $this->elementStart('li');
        $this->checkbox('emailnotifysub',
                        _('有人订阅我的作品时发邮件通知我'),
                        $user->emailnotifysub);
        $this->elementEnd('li');
        $this->elementStart('li');
        $this->checkbox('emailnotifyfav',
                        _('有人收藏我的作品时发邮件通知我'),
                        $user->emailnotifyfav);
        $this->elementEnd('li');
        $this->elementStart('li');
        $this->checkbox('emailnotifymsg',
                        _('收到站内信时发邮件通知我'),
                        $user->emailnotifymsg);
        $this->elementEnd('li');
        $this->elementEnd('ul');
        $this->submit('save', _('保 存'));
        $this->elementEnd('fieldset');
        $this->elementEnd('fieldset');
        $this->elementEnd('form');
    }

    /**
     * Gets any existing email address confirmations we're waiting for
     *
     * @return Confirm_address Email address confirmation for user, or null
     */

    function getConfirmation()
    {
        $user = common_current_user();

        $confirm = new Confirm_address();

        $confirm->user_id      = $user->id;
        $confirm->address_type = 'email';

        if ($confirm->find(true)) {
            return $confirm;
        } else {
            return null;
        }
    }

    /**
     * Handle posts
     *
     * Since there are a lot of different options on the page, we
     * figure out what we're supposed to do based on which button was
     * pushed
     *
     * @return void
     */

    function handlePost()
    {
        // CSRF protection
        $token = $this->trimmed('token');
        if (!$token || $token != common_session_token()) {
            $this->show_form(_('网页错误,请返回重试'));
            return;
        }

        if ($this->arg('save')) {
            $this->savePreferences();
        } else if ($this->arg('add')) {
            $this->addAddress();
        } else if ($this->arg('cancel')) {
            $this->cancelConfirmation();
        } else if ($this->arg('remove')) {
            $this->removeAddress();
        } else if ($this->arg('removeincoming')) {
            $this->removeIncoming();
        } else if ($this->arg('newincoming')) {
            $this->newIncoming();
        } else {
            $this->showForm(_('表单提交错误,请返回重试'));
        }
    }

    /**
     * Save email preferences
     *
     * @return void
     */

    function savePreferences()
    {
        $emailnotifysub   = $this->boolean('emailnotifysub');
        $emailnotifyfav   = $this->boolean('emailnotifyfav');
        $emailnotifymsg   = $this->boolean('emailnotifymsg');
        $emailnotifynudge = $this->boolean('emailnotifynudge');
        $emailnotifyattn  = $this->boolean('emailnotifyattn');
        $emailmicroid     = $this->boolean('emailmicroid');
        $emailpost        = $this->boolean('emailpost');

        $user = common_current_user();

        assert(!is_null($user)); // should already be checked

        $user->query('BEGIN');

        $original = clone($user);

        $user->emailnotifysub   = $emailnotifysub;
        $user->emailnotifyfav   = $emailnotifyfav;
        $user->emailnotifymsg   = $emailnotifymsg;
        $user->emailnotifynudge = $emailnotifynudge;
        $user->emailnotifyattn  = $emailnotifyattn;
        $user->emailmicroid     = $emailmicroid;
        $user->emailpost        = $emailpost;

        $result = $user->update($original);

        if ($result === false) {
            common_log_db_error($user, 'UPDATE', __FILE__);
            $this->serverError(_('无法更新用户资料'));
            return;
        }

        $user->query('COMMIT');

        $this->showForm(_('修改已保存'), true);
    }

    /**
     * Add the address passed in by the user
     *
     * @return void
     */

    function addAddress()
    {
        $user = common_current_user();

        $email = $this->trimmed('email');

        // Some validation

        if (!$email) {
            $this->showForm(_('未填写邮箱地址'));
            return;
        }

        $email = common_canonical_email($email);

        if (!$email) {
            $this->showForm(_('邮件地址格式错误'));
            return;
        }
        if (!Validate::email($email, common_config('email', 'check_domain'))) {
            $this->showForm(_('邮件地址格式错误'));
            return;
        } else if ($user->email == $email) {
            $this->showForm(_('新邮件地址与原邮件地址相同'));
            return;
        } else if ($this->emailExists($email)) {
            $this->showForm(_('此邮件地址属于其他用户'));
            return;
        }

        $confirm = new Confirm_address();

        $confirm->address      = $email;
        $confirm->address_type = 'email';
        $confirm->user_id      = $user->id;
        $confirm->code         = common_confirmation_code(64);

        $result = $confirm->insert();

        if ($result === false) {
            common_log_db_error($confirm, 'INSERT', __FILE__);
            $this->serverError(_('生成验证邮件失败,请返回重试'));
            return;
        }

        mail_confirm_address($user, $confirm->code, $user->nickname, $email);

        $msg = _('验证邮件已经发送,请稍候查看邮箱以确认验证信息');

        $this->showForm($msg, true);
    }

    /**
     * Handle a request to cancel email confirmation
     *
     * @return void
     */

    function cancelConfirmation()
    {
        $email = $this->arg('email');

        $confirm = $this->getConfirmation();

        if (!$confirm) {
            $this->showForm(_('未确认删除目标'));
            return;
        }
        if ($confirm->address != $email) {
            $this->showForm(_('通讯地址错误'));
            return;
        }

        $result = $confirm->delete();

        if (!$result) {
            common_log_db_error($confirm, 'DELETE', __FILE__);
            $this->serverError(_('无法删除验证信息'));
            return;
        }

        $this->showForm(_('验证已取消'), true);
    }

    /**
     * Handle a request to remove an address from the user's account
     *
     * @return void
     */

    function removeAddress()
    {
        $user = common_current_user();

        $email = $this->arg('email');

        // Maybe an old tab open...?

        if ($user->email != $email) {
            $this->showForm(_('这个邮件地址不属于你'));
            return;
        }

        $user->query('BEGIN');

        $original = clone($user);

        $user->email = null;

        $result = $user->updateKeys($original);

        if (!$result) {
            common_log_db_error($user, 'UPDATE', __FILE__);
            $this->serverError(_('无法更新用户资料'));
            return;
        }
        $user->query('COMMIT');

        $this->showForm(_('邮件地址已删除'), true);
    }

    /**
     * Handle a request to remove an incoming email address
     *
     * @return void
     */

    function removeIncoming()
    {
        $user = common_current_user();

        if (!$user->incomingemail) {
            $this->showForm(_('没有收到邮件'));
            return;
        }

        $orig = clone($user);

        $user->incomingemail = null;

        if (!$user->updateKeys($orig)) {
            common_log_db_error($user, 'UPDATE', __FILE__);
            $this->serverError(_("无法更新记录"));
        }

        $this->showForm(_('邮件地址已删除'), true);
    }

    /**
     * Generate a new incoming email address
     *
     * @return void
     */

    function newIncoming()
    {
        $user = common_current_user();

        $orig = clone($user);

        $user->incomingemail = mail_new_incoming_address();

        if (!$user->updateKeys($orig)) {
            common_log_db_error($user, 'UPDATE', __FILE__);
            $this->serverError(_("无法更新用户资料"));
        }

        $this->showForm(_('邮件地址已添加'), true);
    }

    /**
     * Does another user already have this email address?
     *
     * Email addresses are unique for users.
     *
     * @param string $email Address to check
     *
     * @return boolean Whether the email already exists.
     */

    function emailExists($email)
    {
        $user = common_current_user();

        $other = User::staticGet('email', $email);

        if (!$other) {
            return false;
        } else {
            return $other->id != $user->id;
        }
    }
}
