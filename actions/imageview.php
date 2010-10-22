<?php

if (!defined('NEWTYPE') && !defined('DWORKS')) {
    exit(1);
}

class ImageviewAction extends ProfileAction {
	
	var $nickname = null;
	var $image = null;
	var $user = null;
	var $portfolio_id = null;
	
    function prepare($args)
    {
        parent::prepare($args);
        $this->nickname  = common_canonical_nickname($this->arg('nickname'));
        $this->user = User::staticGet('nickname', $this->nickname);
        $this->image = Image::staticGet('id', $this->arg('image_id'));
        $this->portfolio_id = $this->arg('portfolio_id');
        return true;
    }
    
    function handle($args)
    {
        parent::handle($args);
        
        if (!$this->user) {
            $this->clientError(_('用户不存在'), 404);
            return;
        }
        
        if (!$this->image) {
            $this->clientError(_('No such image.'), 404);
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
    	$this->showImage();
    }
    
    function showImage() {
    	$this->element('h2', null, 'Image');

        $il = new ImageList($this->image, $this->portfolio_id, $this);
        
        $this->elementStart('a', array('href' => common_local_url('imagevieworiginal', array('image_id' => $this->image->id,
                                                                       'portfolio_id' => $this->portfolio_id,
                                                                       'nickname' => $this->nickname))));
        $il->showImage(600);
        $this->elementEnd('a');
    }
    
}
