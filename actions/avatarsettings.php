<?php
if (!defined('NEWTYPE') && !defined('DWORKS')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/accountsettingsaction.php';

define('MAX_ORIGINAL', 480);

class AvatarsettingsAction extends AccountSettingsAction
{
    var $mode = null;
    var $imagefile = null;
    var $filename = null;

    /**
     * Title of the page
     *
     * @return string Title of the page
     */

    function title()
    {
        return _('头像');
    }

    /**
     * Instructions for use
     *
     * @return instructions for use
     */

    function getInstructions()
    {
        return sprintf(_('可以上传图片作为头像显示, 图片文件最大限制为 %s.'), ImageFile::maxFileSize());
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
        if ($this->mode == 'crop') {
            $this->showCropForm();
        } else {
            $this->showUploadForm();
        }
    }

    function showUploadForm()
    {
        $user = common_current_user();

        $profile = $user->getProfile();

        if (!$profile) {
            common_log_db_error($user, 'SELECT', __FILE__);
            $this->serverError(_('用户个人资料丢失'));
            return;
        }

        $original = $profile->getOriginalAvatar();

        $this->elementStart('form', array('enctype' => 'multipart/form-data',
                                          'method' => 'post',
                                          'id' => 'form_settings_avatar',
                                          'class' => 'form_settings',
                                          'action' =>
                                          common_local_url('avatarsettings')));
        $this->elementStart('fieldset');
        $this->element('legend', null, _('头像设置'));
        $this->hidden('token', common_session_token());
        
        if (Event::handle('StartAvatarFormData', array($this))) {
            $this->elementStart('ul', 'form_data');
            if ($original) {
                $this->elementStart('li', array('id' => 'avatar_original',
                                                'class' => 'avatar_view'));
                $this->element('h2', null, _("原始大小头像"));
                $this->elementStart('div', array('id'=>'avatar_original_view'));
                $this->element('img', array('src' => $original->url,
                                            'width' => $original->width,
                                            'height' => $original->height,
                                            'alt' => $user->nickname));
                $this->elementEnd('div');
                $this->elementEnd('li');
            }

            $avatar = $profile->getAvatar(AVATAR_PROFILE_SIZE);

            if ($avatar) {
                $this->elementStart('li', array('id' => 'avatar_preview',
                                                'class' => 'avatar_view'));
                $this->element('h2', null, _("缩略图预览"));
                $this->elementStart('div', array('id'=>'avatar_preview_view'));
                $this->element('img', array('src' => $original->url,
                                            'width' => AVATAR_PROFILE_SIZE,
                                            'height' => AVATAR_PROFILE_SIZE,
                                            'alt' => $user->nickname));
                $this->elementEnd('div');
                $this->submit('delete', _('删 除'));
                $this->elementEnd('li');
            }

            $this->elementStart('li', array ('id' => 'settings_attach'));
            $this->element('input', array('name' => 'avatarfile',
                                          'type' => 'file',
                                          'id' => 'avatarfile'));
            $this->element('input', array('name' => 'MAX_FILE_SIZE',
                                          'type' => 'hidden',
                                          'id' => 'MAX_FILE_SIZE',
                                          'value' => ImageFile::maxFileSizeInt()));
            $this->elementEnd('li');
            $this->elementEnd('ul');

            $this->elementStart('ul', 'form_actions');
            $this->elementStart('li');
            $this->submit('upload', _('上 传'));
            $this->elementEnd('li');
            $this->elementEnd('ul');
        }
        Event::handle('EndAvatarFormData', array($this));

        $this->elementEnd('fieldset');
        $this->elementEnd('form');

    }

    function showCropForm()
    {
        $user = common_current_user();

        $profile = $user->getProfile();

        if (!$profile) {
            common_log_db_error($user, 'SELECT', __FILE__);
            $this->serverError(_('用户个人资料丢失'));
            return;
        }

        $original = $profile->getOriginalAvatar();

        $this->elementStart('form', array('method' => 'post',
                                          'id' => 'form_settings_avatar',
                                          'class' => 'form_settings',
                                          'action' =>
                                          common_local_url('avatarsettings')));
        $this->elementStart('fieldset');
        $this->element('legend', null, _('头像设置'));
        $this->hidden('token', common_session_token());

        $this->elementStart('ul', 'form_data');

        $this->elementStart('li',
                            array('id' => 'avatar_original',
                                  'class' => 'avatar_view'));
        $this->element('h2', null, _("原始大小"));
        $this->elementStart('div', array('id'=>'avatar_original_view'));
        $this->element('img', array('src' => Avatar::url($this->filedata['filename']),
                                    'width' => $this->filedata['width'],
                                    'height' => $this->filedata['height'],
                                    'alt' => $user->nickname));
        $this->elementEnd('div');
        $this->elementEnd('li');

        $this->elementStart('li',
                            array('id' => 'avatar_preview',
                                  'class' => 'avatar_view'));
        $this->element('h2', null, _("预览"));
        $this->elementStart('div', array('id'=>'avatar_preview_view'));
        $this->element('img', array('src' => Avatar::url($this->filedata['filename']),
                                    'width' => AVATAR_PROFILE_SIZE,
                                    'height' => AVATAR_PROFILE_SIZE,
                                    'alt' => $user->nickname));
        $this->elementEnd('div');

        foreach (array('avatar_crop_x', 'avatar_crop_y',
                       'avatar_crop_w', 'avatar_crop_h') as $crop_info) {
            $this->element('input', array('name' => $crop_info,
                                          'type' => 'hidden',
                                          'id' => $crop_info));
        }
        $this->submit('crop', _('裁 剪'));

        $this->elementEnd('li');
        $this->elementEnd('ul');
        $this->elementEnd('fieldset');
        $this->elementEnd('form');

    }

    /**
     * Handle a post
     *
     * We mux on the button name to figure out what the user actually wanted.
     *
     * @return void
     */

    function handlePost()
    {
        // Workaround for PHP returning empty $_POST and $_FILES when POST
        // length > post_max_size in php.ini

        if (empty($_FILES)
            && empty($_POST)
            && ($_SERVER['CONTENT_LENGTH'] > 0)
        ) {
            $msg = _('图片大小超过上传限制');

            $this->showForm(sprintf($msg, $_SERVER['CONTENT_LENGTH']));
            return;
        }

        // CSRF protection

        $token = $this->trimmed('token');
        if (!$token || $token != common_session_token()) {
            $this->showForm(_('网页错误,请返回重试
                               '));
            return;
        }
        
        if (Event::handle('StartAvatarSaveForm', array($this))) {
            if ($this->arg('upload')) {
                $this->uploadAvatar();
                } else if ($this->arg('crop')) {
                    $this->cropAvatar();
                } else if ($this->arg('delete')) {
                    $this->deleteAvatar();
                } else {
                    $this->showForm(_('未知错误'));
                }
            Event::handle('EndAvatarSaveForm', array($this));
        }
    }

    /**
     * Handle an image upload
     *
     * Does all the magic for handling an image upload, and crops the
     * image by default.
     *
     * @return void
     */

    function uploadAvatar()
    {
        try {
            $imagefile = ImageFile::fromUpload('avatarfile');
        } catch (Exception $e) {
            $this->showForm($e->getMessage());
            return;
        }
        if ($imagefile === null) {
            $this->showForm(_('No file uploaded.'));
            return;
        }

        $cur = common_current_user();

        $filename = Avatar::filename($cur->id,
                                     image_type_to_extension($imagefile->type),
                                     null,
                                     'tmp'.common_timestamp());

        $filepath = Avatar::path($filename);

        move_uploaded_file($imagefile->filepath, $filepath);

        $filedata = array('filename' => $filename,
                          'filepath' => $filepath,
                          'width' => $imagefile->width,
                          'height' => $imagefile->height,
                          'type' => $imagefile->type);

        $_SESSION['FILEDATA'] = $filedata;

        $this->filedata = $filedata;

        $this->mode = 'crop';

        $this->showForm(_('调整方形区域作为头像缩略图'),
                        true);
    }

    /**
     * Handle the results of jcrop.
     *
     * @return void
     */

    function cropAvatar()
    {
        $filedata = $_SESSION['FILEDATA'];

        if (!$filedata) {
            $this->serverError(_('图片文件信息丢失'));
            return;
        }

        $file_d = ($filedata['width'] > $filedata['height'])
                     ? $filedata['height'] : $filedata['width'];

        $dest_x = $this->arg('avatar_crop_x') ? $this->arg('avatar_crop_x'):0;
        $dest_y = $this->arg('avatar_crop_y') ? $this->arg('avatar_crop_y'):0;
        $dest_w = $this->arg('avatar_crop_w') ? $this->arg('avatar_crop_w'):$file_d;
        $dest_h = $this->arg('avatar_crop_h') ? $this->arg('avatar_crop_h'):$file_d;
        $size = min($dest_w, $dest_h, MAX_ORIGINAL);

        $user = common_current_user();
        $profile = $user->getProfile();

        $imagefile = new ImageFile($user->id, $filedata['filepath']);
        $filename = $imagefile->resizeAvatar($size, $dest_x, $dest_y, $dest_w, $dest_h);

        if ($profile->setOriginal($filename)) {
            @unlink($filedata['filepath']);
            unset($_SESSION['FILEDATA']);
            $this->mode = 'upload';
            $this->showForm(_('头像已完成修改'), true);
            common_broadcast_profile($profile);
        } else {
            $this->showForm(_('头像修改失败,请重试'));
        }
    }
    
    /**
     * Get rid of the current avatar.
     *
     * @return void
     */
    
    function deleteAvatar()
    {
        $user = common_current_user();
        $profile = $user->getProfile();
        
        $avatar = $profile->getOriginalAvatar();
        if($avatar) $avatar->delete();
        $avatar = $profile->getAvatar(AVATAR_PROFILE_SIZE);
        if($avatar) $avatar->delete();
        $avatar = $profile->getAvatar(AVATAR_STREAM_SIZE);
        if($avatar) $avatar->delete();
        $avatar = $profile->getAvatar(AVATAR_MINI_SIZE);
        if($avatar) $avatar->delete();

        $this->showForm(_('头像已删除'), true);
    }

    /**
     * Add the jCrop stylesheet
     *
     * @return void
     */

    function showStylesheets()
    {
        parent::showStylesheets();
        $this->cssLink('css/jquery.Jcrop.css','base','screen, projection, tv');
    }

    /**
     * Add the jCrop scripts
     *
     * @return void
     */

    function showScripts()
    {
        parent::showScripts();

        if ($this->mode == 'crop') {
            $this->script('jcrop/jquery.Jcrop.min.js');
            $this->script('jcrop/jquery.Jcrop.go.js');
        }

        $this->autofocus('avatarfile');
    }
}
