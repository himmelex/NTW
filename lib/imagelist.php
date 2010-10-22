<?php

if (!defined('NEWTYPE') && !defined('DWORKS')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/widget.php';

define('IMAGE_PER_PAGE', 50);

/**
 * Widget to show a list of images
 */

class ImageList extends Widget
{
    /** Current image, image query. */
    var $image = null;
    /** Owner of this list */
    var $portfolio_id = null;
    /** Action object using us. */
    var $action = null;

    var $nickname = null;
    
    function __construct($image, $portfolio_id=null, $action=null)
    {
        parent::__construct($action);

        $this->image = $image;
        $this->portfolio_id = $portfolio_id;
        $this->action = $action;
        $this->nickname = $action->arg('nickname');
    }

    function showImageList($size)
    {
        $this->out->elementStart('ul', '');
        
        while ($this->image->fetch()) {
        	$this->out->elementStart('li', array('class' => 'image',
                                             'id' => 'image-' . $this->image->id));
            $this->out->elementStart('a', array('href' => common_local_url('imageview', array('image_id' => $this->image->id,
                                                                           'portfolio_id' => $this->portfolio_id,
                                                                           'nickname' => $this->nickname))));
            $this->showImage($size);
            $this->out->elementEnd('a');
            $this->out->element('a', array('href' => common_local_url('imageview', array('image_id' => $this->image->id,
                                                                      'portfolio_id' => $this->portfolio_id,
                                                                      'nickname' => $this->nickname))), $this->image->title);
            $this->out->elementEnd('li');
        }
        
        $this->out->elementEnd('ul');

        return;
    }

    function showImage($size)
    {
        $this->out->element('img', array('src' => $this->image->getUrl($size)));
    }
}
