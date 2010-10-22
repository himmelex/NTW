<?php

if (!defined('NEWTYPE') && !defined('DWORKS')) {
    exit(1);
}

class PortfolioAction extends ProfileAction {
	
	var $nickname = null;
	var $portfolio = null;
	
    function prepare($args)
    {
        parent::prepare($args);
        $this->nickname  = common_canonical_nickname($this->arg('nickname'));
        $this->user = User::staticGet('nickname', $this->nickname);
        $this->portfolio = Portfolio::staticGet('id', $this->arg('id'));
        return true;
    }
    
    function handle($args)
    {
        parent::handle($args);
        
        if (!$this->user) {
            $this->clientError(_('用户不存在'), 404);
            return;
        }
        
        if (!$this->portfolio) {
            $this->clientError(_('No such portfolio.'), 404);
            return;
        }
        
        $this->showPage();
    }
	
    function title()
    {
        return sprintf(_("Portfolio"));
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
    	$this->element('h2', null, 'ImageList');
        
    	$imagelist = $this->portfolio->getImageList(0, null, 0);

        if ($imagelist) {
            $il = new ImageList($imagelist, $this->portfolio->id, $this);
            $il->showImageList(150);
        }
    }
    
}
