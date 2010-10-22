<?php

if (!defined('NEWTYPE') && !defined('DWORKS')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/settingsaction.php';

class AccountSettingsAction extends SettingsAction
{
    /**
     * Show the local navigation menu
     *
     * This is the same for all settings, so we show it here.
     *
     * @return void
     */

    function showLocalNav()
    {
        $menu = new AccountSettingsNav($this);
        $menu->show();
    }
}

class AccountSettingsNav extends Widget
{
    var $action = null;

    /**
     * Construction
     *
     * @param Action $action current action, used for output
     */

    function __construct($action=null)
    {
        parent::__construct($action);
        $this->action = $action;
    }

    /**
     * Show the menu
     *
     * @return void
     */

    function show()
    {
        $action_name = $this->action->trimmed('action');
        $this->action->elementStart('ul', array('class' => 'nav'));

        $user = common_current_user();

        $this->showMenuItem('profilesettings',_('个人信息'),_('修改你的个人信息'));

        $this->showMenuItem('avatarsettings',_('头像'),_('上传或修改头像'));

        $this->showMenuItem('passwordsettings',_('密码'),_('修改密码'));

        $this->showMenuItem('emailsettings',_('电子邮件'),_('修改电子邮件'));

        //$this->showMenuItem('userdesignsettings',_('Design'),_('Design your profile'));

        $this->action->elementEnd('ul');
    }

    function showMenuItem($menuaction, $desc1, $desc2)
    {
        $action_name = $this->action->trimmed('action');
        $this->action->menuItem(common_local_url($menuaction),
            $desc1,
            $desc2,
            $action_name === $menuaction);
    }
}
