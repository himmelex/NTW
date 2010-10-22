<?php
if (!defined('NEWTYPE') && !defined('DWORKS')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/accountsettingsaction.php';

class ProfilesettingsAction extends AccountSettingsAction
{
    /**
     * Title of the page
     *
     * @return string Title of the page
     */

    function title()
    {
        return _('Profile settings');
    }

    /**
     * Instructions for use
     *
     * @return instructions for use
     */

    function getInstructions()
    {
        return _('更新个人信息');
    }

    function showScripts()
    {
        parent::showScripts();
        $this->autofocus('nickname');
    }

    /**
     * Content area of the page
     *
     * Shows a form for uploading an avatar.
     *
     * @return void
     */

    function showContent()
    {
        $user = common_current_user();
        $profile = $user->getProfile();

        $this->elementStart('form', array('method' => 'post',
                                          'id' => 'form_settings_profile',
                                          'class' => 'form_settings',
                                          'action' => common_local_url('profilesettings')));
        $this->elementStart('fieldset');
        $this->element('legend', null, _('个人信息'));
        $this->hidden('token', common_session_token());

        // too much common patterns here... abstractable?
        $this->elementStart('ul', 'form_data');
        if (Event::handle('StartProfileFormData', array($this))) {
            $this->elementStart('li');
            $this->input('fullname', _('真实姓名'),
                         ($this->arg('fullname')) ? $this->arg('fullname') : $profile->fullname);
            $this->elementEnd('li');
            $this->elementStart('li');
            $this->input('homepage', _('个人主页'),
                         ($this->arg('homepage')) ? $this->arg('homepage') : $profile->homepage,
                         _('个人主页或博客地址'));
            $this->elementEnd('li');
            $this->elementStart('li');
            $maxBio = Profile::maxBio();
            if ($maxBio > 0) {
                $bioInstr = sprintf(_('个人简介-最多%d字'),
                                    $maxBio);
            } else {
                $bioInstr = _('描述你和你的兴趣');
            }
            $this->textarea('bio', _('自我描述'),
                            ($this->arg('bio')) ? $this->arg('bio') : $profile->bio,
                            $bioInstr);
            $this->elementEnd('li');
            $this->elementStart('li');
            $this->input('location', _('所在位置'),
                         ($this->arg('location')) ? $this->arg('location') : $profile->location,
                         _('你的位置，格式类似"城市，省份，国家"'));
            $this->elementEnd('li');
            Event::handle('EndProfileFormData', array($this));
            $this->elementStart('li');
            $this->input('tags', _('标签'),
                         ($this->arg('tags')) ? $this->arg('tags') : implode(' ', $user->getSelfTags()),
                         _('用来描述你的标签,以逗号或空格分隔'));
            $this->elementEnd('li');
        }
        $this->elementEnd('ul');
        $this->submit('save', _('保 存'));

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

        if (Event::handle('StartProfileSaveForm', array($this))) {
            $fullname = $this->trimmed('fullname');
            $homepage = $this->trimmed('homepage');
            $bio = $this->trimmed('bio');
            $location = $this->trimmed('location');
            $tagstring = $this->trimmed('tags');

            // Some validation
            if (!is_null($homepage) && (strlen($homepage) > 0) &&
                       !Validate::uri($homepage, array('allowed_schemes' => array('http', 'https')))) {
                $this->showForm(_('个人主页地址不正确'));
                return;
            } else if (!is_null($fullname) && mb_strlen($fullname) > 255) {
                $this->showForm(_('真实姓名过长'));
                return;
            } else if (Profile::bioTooLong($bio)) {
                $this->showForm(sprintf(_('自我描述过长'),
                                        Profile::maxBio()));
                return;
            } else if (!is_null($location) && mb_strlen($location) > 255) {
                $this->showForm(_('位置信息过长'));
                return;
            }

            if ($tagstring) {
                $tags = array_map('common_canonical_tag', preg_split('/[\s,]+/', $tagstring));
            } else {
                $tags = array();
            }

            foreach ($tags as $tag) {
                if (!common_valid_profile_tag($tag)) {
                    $this->showForm(sprintf(_('标签格式不正确: "%s"'), $tag));
                    return;
                }
            }

            $user = common_current_user();

            $user->query('BEGIN');

            $profile = $user->getProfile();

            $orig_profile = clone($profile);

            $profile->nickname = $user->nickname;
            $profile->fullname = $fullname;
            $profile->homepage = $homepage;
            $profile->bio = $bio;
            $profile->location = $location;

            $loc = Location::fromName($location);

            if (empty($loc)) {
                $profile->lat         = null;
                $profile->lon         = null;
                $profile->location_id = null;
                $profile->location_ns = null;
            } else {
                $profile->lat         = $loc->lat;
                $profile->lon         = $loc->lon;
                $profile->location_id = $loc->location_id;
                $profile->location_ns = $loc->location_ns;
            }

            if (common_config('location', 'share') == 'user') {

                $exists = false;

                $prefs = User_location_prefs::staticGet('user_id', $user->id);

                if (empty($prefs)) {
                    $prefs = new User_location_prefs();

                    $prefs->user_id = $user->id;
                    $prefs->created = common_sql_now();
                } else {
                    $exists = true;
                    $orig = clone($prefs);
                }

                $prefs->share_location = $this->boolean('sharelocation');

                if ($exists) {
                    $result = $prefs->update($orig);
                } else {
                    $result = $prefs->insert();
                }

                if ($result === false) {
                    common_log_db_error($prefs, ($exists) ? 'UPDATE' : 'INSERT', __FILE__);
                    $this->serverError(_('Couldn\'t save location prefs.'));
                    return;
                }
            }

            common_debug('Old profile: ' . common_log_objstring($orig_profile), __FILE__);
            common_debug('New profile: ' . common_log_objstring($profile), __FILE__);

            $result = $profile->update($orig_profile);

            if ($result === false) {
                common_log_db_error($profile, 'UPDATE', __FILE__);
                $this->serverError(_('无法保存个人信息'));
                return;
            }

            // Set the user tags
            $result = $user->setSelfTags($tags);

            if (!$result) {
                $this->serverError(_('无法保存标签信息'));
                return;
            }

            $user->query('COMMIT');
            Event::handle('EndProfileSaveForm', array($this));
            common_broadcast_profile($profile);

            $this->showForm(_('个人信息已保存'), true);

        }
    }

    function nicknameExists($nickname)
    {
        $user = common_current_user();
        $other = User::staticGet('nickname', $nickname);
        if (!$other) {
            return false;
        } else {
            return $other->id != $user->id;
        }
    }
}
