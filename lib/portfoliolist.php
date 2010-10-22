<?php

if (!defined('NEWTYPE') && !defined('DWORKS')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/widget.php';

/**
 * Widget to show a list of portfolios
 */

class PortfolioList extends Widget
{
    /** Current portfolio, portfolio query. */
    var $portfolio = null;
    /** Owner of this list */
    var $owner = null;
    /** Action object using us. */
    var $action = null;

    function __construct($portfolio, $owner=null, $action=null)
    {
        parent::__construct($action);

        $this->portfolio = $portfolio;
        $this->owner = $owner;
        $this->action = $action;
    }

    function show()
    {
        $this->out->elementStart('ul', 'profiles portfolios xoxo');

        while ($this->portfolio->fetch()) {
            $this->showPortfolio();
        }

        $this->out->elementEnd('ul');
        return;
    }

    function showPortfolio()
    {
        $this->out->elementStart('li', array('class' => 'portfolio',
                                             'id' => 'portfolio-' . $this->portfolio->id));

        $this->out->elementStart('div', 'portfolio-list');
        $this->out->elementStart('a', array('href' => common_local_url('portfolio', array('id' => $this->portfolio->id,
                                                                                          'nickname' => $this->owner->nickname))));
        $this->out->element('img', array('src' => $this->portfolio->coverpic));
        $this->out->elementEnd('a');
        $this->out->element('a', array('href' => common_local_url('portfolio', array('id' => $this->portfolio->id,
                                                                                     'nickname' => $this->owner->nickname))), $this->portfolio->name);
        $this->out->elementEnd('div');
    }
    
    function showPortfolioDropdown()
    {
    	$this->out->elementStart('select', array('id' => 'portfolio-select', 'name' => 'portfolio-select'));
        while ($this->portfolio->fetch()) {
            $this->out->element('option', array('value' => $this->portfolio->id),
                               $this->portfolio->name);
        }
        $this->out->elementEnd('select');
    }
}
