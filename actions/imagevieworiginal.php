<?php

if (!defined('NEWTYPE') && !defined('DWORKS')) {
    exit(1);
}

class ImagevieworiginalAction extends Action {
	
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
    }
    
    function showPage() {
    if (Event::handle ( 'StartShowHTML', array ($this ) )) {
            $this->startHTML ();
            Event::handle ( 'EndShowHTML', array ($this ) );
        }
        if (Event::handle ( 'StartShowHead', array ($this ) )) {
            $this->showHead ();
            Event::handle ( 'EndShowHead', array ($this ) );
        }
        if (Event::handle ( 'StartShowBody', array ($this ) )) {
            $this->showImage();
            Event::handle ( 'EndShowBody', array ($this ) );
        }
        if (Event::handle ( 'StartEndHTML', array ($this ) )) {
            $this->endHTML ();
            Event::handle ( 'EndEndHTML', array ($this ) );
        }
    	
    }
    
    function showImage() {
        $il = new ImageList($this->image, $this->portfolio_id, $this);
        
        $this->elementStart('a', array('href' => common_local_url('imageview', array('image_id' => $this->image->id,
                                                                       'portfolio_id' => $this->portfolio_id,
                                                                       'nickname' => $this->nickname))));
        $il->showImage('original');
        $this->elementEnd('a');
    }
    
}
