<?php
if (!defined('NEWTYPE') && !defined('DWORKS')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/widget.php';

class PersonalGroupNav extends Widget
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
        $user = null;

	// FIXME: we should probably pass this in

        $action = $this->action->trimmed('action');
        $nickname = $this->action->trimmed('nickname');

        if ($nickname) {
            $user = User::staticGet('nickname', $nickname);
            $user_profile = $user->getProfile();
        } else {
            $user_profile = false;
        }

        if (Event::handle('StartPersonalGroupNav', array($this))) {
       
            $cur = common_current_user();

            if ($user && $cur && $cur->id == $user->id) {
                $this->out->elementStart('ul', array('class' => 'nav'));
//                $this->out->menuItem(common_local_url('outbox', array('nickname' =>
//                                                                         $nickname)),
//                                 _('Outbox'),
//                                 _('Your sent messages'),
//                                 $action == 'outbox');
                $this->out->elementEnd('ul');
            }
            Event::handle('EndPersonalGroupNav', array($this));
        }
        
    }
}
