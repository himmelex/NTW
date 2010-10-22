<?php

if (!defined('NEWTYPE') && !defined('DWORKS')) {
    exit(1);
}

class PortfoliolistAction extends ProfileAction {

	var $nickname = null;
	
    function prepare($args)
    {
        parent::prepare($args);
        $this->nickname   = common_canonical_nickname($this->arg('nickname'));
        $this->user = User::staticGet('nickname', $this->nickname);
        return true;
    }
    
    function handle($args)
    {
        parent::handle($args);
        
        if (!$this->user) {
            $this->clientError(_('用户不存在'), 404);
            return;
        }
        $this->showPage();
    }
	
    function title()
    {
        return sprintf(_("Manage Your Portfolios"));
    }
    
    function showLocalNav()
    {
        $nav = new PersonalGroupNav($this);
        $nav->show();
    }
    
    function showContent() {
    	
    	$this->showPortfolioList();
    }
    
    function showPortfolioList() {
    	$this->element('h2', null, 'PortfolioList');

        $profile = $this->user->getProfile();
        $portfolios = $profile->getPortfolioList();

        if ($portfolios) {
            $pl = new PortfolioList($portfolios, $this->user, $this);
            $pl->show();
        }
    }
    
}
