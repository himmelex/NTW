<?php

if (!defined('NEWTYPE') && !defined('DWORKS')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/personalgroupnav.php';
require_once INSTALLDIR.'/lib/userprofile.php';
require_once INSTALLDIR.'/lib/noticelist.php';
require_once INSTALLDIR.'/lib/profileminilist.php';
require_once INSTALLDIR.'/lib/feedlist.php';

class ShowstreamAction extends ProfileAction
{
	
    function isReadOnly($args)
    {
        return true;
    }

    function title()
    {
    }

    function handle($args)
    {
        $this->showPage();
    }

    function showContent()
    {
        
    }

    function showLocalNav()
    {
        $nav = new PersonalGroupNav($this);
        $nav->show();
    }

    function showProfile()
    {
        $profile = new UserProfile($this, $this->user, $this->profile);
        $profile->show();
    }

}