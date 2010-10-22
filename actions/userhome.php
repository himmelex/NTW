<?php
if (!defined('NEWTYPE') && !defined('DWORKS')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/personalgroupnav.php';
require_once INSTALLDIR.'/lib/noticelist.php';
require_once INSTALLDIR.'/lib/feedlist.php';

class UserhomeAction extends ProfileAction
{
	var $left_section = true;
	var $right_section = true;
    var $notice;

    function isReadOnly($args)
    {
        return true;
    }

    function prepare($args)
    {
        parent::prepare($args);
        $cur = common_current_user();

        if (!empty($cur) && $cur->id == $this->user->id) {
            $this->notice = $this->user->noticeInbox(($this->page-1)*NOTICES_PER_PAGE, NOTICES_PER_PAGE + 1);
        } else {
            $this->notice = $this->user->noticesWithFriends(($this->page-1)*NOTICES_PER_PAGE, NOTICES_PER_PAGE + 1);
        }

        if ($this->page > 1 && $this->notice->N == 0) {
            $this->serverError(_('您访问的网页不存在'), $code = 404);
        }

        return true;
    }

    function handle($args)
    {
        parent::handle($args);

        if (!$this->user) {
            $this->clientError(_('用户不存在'));
            return;
        }
        $cur = common_current_user();
        if (empty($cur) || $cur->id != $this->user->id) {
        	$this->clientError(_('无法访问此用户的个人页面'));
            return;
        }

        $this->showPage();
    }

    function title()
    {
         return sprintf(_('%1$s的个人中心'), $this->user->nickname);
    }

    function getFeeds()
    {
    }

    function showLocalNav()
    {
        $nav = new PersonalGroupNav($this);
        $nav->show();
    }

    function showEmptyListMessage()
    {
    }

    function showContent()
    {
        if (common_logged_in ()) {
        }
    }
    
    function showPageTitle()
    {
       
    }
}
