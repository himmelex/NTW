<?php

if (!defined('NEWTYPE') && !defined('DWORKS')) {
    exit(1);
}

class WelcomeAction extends Action
{
    /**
     * page of the stream we're on; default = 1
     */

    var $page = null;
    var $notice;

    function isReadOnly($args)
    {
        return true;
    }

    /**
     * Read and validate arguments
     *
     * @param array $args URL parameters
     *
     * @return boolean success value
     */

    function prepare($args)
    {
        parent::prepare($args);

        common_set_returnto($this->selfUrl());

        return true;
    }

    /**
     * handle request
     *
     * Show the public stream, using recipe method showPage()
     *
     * @param array $args arguments, mostly unused
     *
     * @return void
     */

    function handle($args)
    {
        parent::handle($args);

        $this->showPage();
    }

    /**
     * Title of the page
     *
     * @return page title, including page number if over 1
     */

    function title()
    {

	   return sprintf(_('欢迎来到Newtype设计档案'));

    }

    /**
     * Output <head> elements for RSS and Atom feeds
     *
     * @return void
     */

    function getFeeds()
    {
    }

    /**
     * Show tabset for this page
     *
     * Uses the PublicGroupNav widget
     *
     * @return void
     * @see PublicGroupNav
     */

    function showLocalNav()
    {

    }
    
    function showContentBlock()
    {        
    	$this->elementStart( 'div', array( 'id' => 'welcome_body' ) );
        $this->showContent();
        $this->elementEnd( 'div' );
    	
    }


    /**
     * Fill the content area
     *
     *
     * @return void
     */

    function showContent()
    {
    	$user = common_current_user ();
    	
        $this->elementStart( 'div', array( 'id' => 'to_portf', 'class' => 'grid_11 prefix_1' ) );
        $this->element( 'a', array( 'href' => common_local_url('portfolio') ), '设计档案库' );
        $this->element('br');
        if(!$user) {
        	$this->element( 'a', array( 'id' => 'myportf', 'href' => common_local_url('register', array('type' => 'D')) ), '创建你的设计档案!' );
        } else {
        	$this->element( 'a', array( 'id' => 'myportf', 'href' => common_local_url ( 'userhome', array ('nickname' => $user->nickname ) ) ), '管理你的设计档案' );
        }
        $this->elementEnd( 'div' );
        
        $this->elementStart( 'div', array( 'id' => 'to_job', 'class' => 'grid_11' ) );
        $this->element( 'a', array( 'href' => common_local_url('job') ), '招募信息板' );
        $this->element('br');
        if(!$user) {
            $this->element( 'a', array( 'id' => 'myjob', 'href' => common_local_url('register', array('type' => 'C')) ), '发布招募信息' );
        } else {
            $this->element( 'a', array( 'id' => 'myjob', 'href' => common_local_url ( 'userhome', array ('nickname' => $user->nickname ) ) ), '管理你的招募信息' );
        }
        $this->elementEnd( 'div' );
        
        
       
        
        
    }
    
    function showAside()
    {
    }

    function showSections()
    {

    }

    function showAnonymousMessage()
    {
    	
    }
}
