<?php
if (!defined('NEWTYPE') && !defined('DWORKS')) {
    exit(1);
}

require_once 'Net/URL/Mapper.php';

class StatusNet_URL_Mapper extends Net_URL_Mapper {

    private static $_singleton = null;

    private function __construct()
    {
    }

    public static function getInstance($id = '__default__')
    {
        if (empty(self::$_singleton)) {
            self::$_singleton = new StatusNet_URL_Mapper();
        }
        return self::$_singleton;
    }

    public function connect($path, $defaults = array(), $rules = array())
    {
        $result = null;
        if (Event::handle('StartConnectPath', array(&$path, &$defaults, &$rules, &$result))) {
            $result = parent::connect($path, $defaults, $rules);
            Event::handle('EndConnectPath', array($path, $defaults, $rules, $result));
        }
        return $result;
    }
}

/**
 * URL Router
 *
 * Cheap wrapper around Net_URL_Mapper
 *
 * @category URL
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */

class Router
{
    var $m = null;
    static $inst = null;
    static $bare = array('requesttoken', 'accesstoken', 'userauthorization',
                         'postnotice', 'updateprofile', 'finishremotesubscribe');

    static function get()
    {
        if (!Router::$inst) {
            Router::$inst = new Router();
        }
        return Router::$inst;
    }

    function __construct()
    {
        if (!$this->m) {
            $this->m = $this->initialize();
        }
    }

    function initialize()
    {
        $m = StatusNet_URL_Mapper::getInstance();

        if (Event::handle('StartInitializeRouter', array(&$m))) {

            $m->connect('robots.txt', array('action' => 'robotstxt'));

            $m->connect('opensearch/people', array('action' => 'opensearch',
                                                   'type' => 'people'));
            $m->connect('opensearch/notice', array('action' => 'opensearch',
                                                   'type' => 'notice'));

            // docs

            $m->connect('doc/:title', array('action' => 'doc'));

            $m->connect('main/otp/:user_id/:token',
                        array('action' => 'otp'),
                        array('user_id' => '[0-9]+',
                              'token' => '.+'));

            // main stuff is repetitive

            $main = array('login', 'logout', 'subscribe',
                          'unsubscribe', 'confirmaddress', 'recoverpassword',
                          'invite', 'favor', 'disfavor', 'sup',
                          'block', 'unblock', 'subedit',
                          'groupblock', 'groupunblock',
                          'sandbox', 'unsandbox',
                          'silence', 'unsilence',
                          'grantrole', 'revokerole',
                          'repeat',
                          'deleteuser',
                          'geocode',
                          'version',
                          );

            foreach ($main as $a) {
                $m->connect('main/'.$a, array('action' => $a));
            }

            $m->connect('main/register/:type', array('action' => 'register'),
                        array('type' => '[CD]'));
            
            $m->connect('main/sup/:seconds', array('action' => 'sup'),
                        array('seconds' => '[0-9]+'));

            $m->connect('main/tagother/:id', array('action' => 'tagother'));

            $m->connect('main/oembed',
                        array('action' => 'oembed'));

            $m->connect('main/xrds',
                        array('action' => 'publicxrds'));

            // these take a code

            foreach (array('register', 'confirmaddress', 'recoverpassword') as $c) {
                $m->connect('main/'.$c.'/:code', array('action' => $c));
            }

            // exceptional

            $m->connect('main/remote', array('action' => 'remotesubscribe'));
            $m->connect('main/remote?nickname=:nickname', array('action' => 'remotesubscribe'), array('nickname' => '[A-Za-z0-9_-]+'));

            foreach (Router::$bare as $action) {
                $m->connect('index.php?action=' . $action, array('action' => $action));
            }

            // settings

            foreach (array('profile', 'avatar', 'password', 'im', 'oauthconnections',
                           'oauthapps', 'email', 'sms', 'userdesign', 'other') as $s) {
                $m->connect('settings/'.$s, array('action' => $s.'settings'));
            }

            // search

            foreach (array('group', 'people', 'notice') as $s) {
                $m->connect('search/'.$s, array('action' => $s.'search'));
                $m->connect('search/'.$s.'?q=:q',
                            array('action' => $s.'search'),
                            array('q' => '.+'));
            }
            
            // The second of these is needed to make the link work correctly
            // when inserted into the page. The first is needed to match the
            // route on the way in. Seems to be another Net_URL_Mapper bug to me.
            $m->connect('search/notice/rss', array('action' => 'noticesearchrss'));
            $m->connect('search/notice/rss?q=:q', array('action' => 'noticesearchrss'),
                        array('q' => '.+'));

            $m->connect('notice/new', array('action' => 'newnotice'));
            $m->connect('notice/new?replyto=:replyto',
                        array('action' => 'newnotice'),
                        array('replyto' => '[A-Za-z0-9_-]+'));
            $m->connect('notice/new?replyto=:replyto&inreplyto=:inreplyto',
                        array('action' => 'newnotice'),
                        array('replyto' => '[A-Za-z0-9_-]+'),
                        array('inreplyto' => '[0-9]+'));

            $m->connect('notice/:notice/file',
                        array('action' => 'file'),
                        array('notice' => '[0-9]+'));

            $m->connect('notice/:notice',
                        array('action' => 'shownotice'),
                        array('notice' => '[0-9]+'));
            $m->connect('notice/delete', array('action' => 'deletenotice'));
            $m->connect('notice/delete/:notice',
                        array('action' => 'deletenotice'),
                        array('notice' => '[0-9]+'));

            $m->connect('bookmarklet/new', array('action' => 'bookmarklet'));

            // conversation

            $m->connect('conversation/:id',
                        array('action' => 'conversation'),
                        array('id' => '[0-9]+'));

            $m->connect('message/new', array('action' => 'newmessage'));
            $m->connect('message/new?to=:to', array('action' => 'newmessage'), array('to' => '[A-Za-z0-9_-]+'));
            $m->connect('message/:message',
                        array('action' => 'showmessage'),
                        array('message' => '[0-9]+'));

            $m->connect('user/:id',
                        array('action' => 'userbyid'),
                        array('id' => '[0-9]+'));

            $m->connect('tags/', array('action' => 'publictagcloud'));
            $m->connect('tag/', array('action' => 'publictagcloud'));
            $m->connect('tags', array('action' => 'publictagcloud'));
            $m->connect('tag', array('action' => 'publictagcloud'));
            $m->connect('tag/:tag/rss',
                        array('action' => 'tagrss'),
                        array('tag' => '[a-zA-Z0-9]+'));
            $m->connect('tag/:tag',
                        array('action' => 'tag'),
                        array('tag' => '[\pL\pN_\-\.]{1,64}'));

            $m->connect('peopletag/:tag',
                        array('action' => 'peopletag'),
                        array('tag' => '[a-zA-Z0-9]+'));

            // Admin

            $m->connect('admin/site', array('action' => 'siteadminpanel'));
            $m->connect('admin/design', array('action' => 'designadminpanel'));
            $m->connect('admin/user', array('action' => 'useradminpanel'));
	        $m->connect('admin/access', array('action' => 'accessadminpanel'));
            $m->connect('admin/paths', array('action' => 'pathsadminpanel'));
            $m->connect('admin/sessions', array('action' => 'sessionsadminpanel'));
            $m->connect('admin/sitenotice', array('action' => 'sitenoticeadminpanel'));
            $m->connect('admin/snapshot', array('action' => 'snapshotadminpanel'));

            $m->connect('getfile/:filename',
                        array('action' => 'getfile'),
                        array('filename' => '[A-Za-z0-9._-]+'));

            // In the "root"

                $m->connect('', array('action' => 'welcome'));
                $m->connect('rss', array('action' => 'publicrss'));
                $m->connect('featuredrss', array('action' => 'featuredrss'));
                $m->connect('favoritedrss', array('action' => 'favoritedrss'));
                $m->connect('featured/', array('action' => 'featured'));
                $m->connect('featured', array('action' => 'featured'));
                $m->connect('favorited/', array('action' => 'favorited'));
                $m->connect('favorited', array('action' => 'favorited'));

                foreach (array('subscriptions', 'subscribers',
                               'nudge', 'userhome', 'foaf', 'xrds',
                               'replies', 'upload', 'inbox', 'outbox', 'microsummary', 'hcard') as $a) {
                    $m->connect(':nickname/'.$a,
                                array('action' => $a),
                                array('nickname' => '[a-zA-Z0-9]{1,64}'));
                }
                
                $m->connect(':nickname/portfolio',
                            array('action' => 'portfoliolist'),
                            array('nickname' => '[a-zA-Z0-9]{1,64}'));
                
                $m->connect(':nickname/portfolio/:id',
                            array('action' => 'portfolio'),
                            array('nickname' => '[a-zA-Z0-9]{1,64}'),
                            array('id' => '[0-9]+'));
                            
                $m->connect(':nickname/portfolio/:portfolio_id/image/:image_id',
                            array('action' => 'imageview'),
                            array('nickname' => '[a-zA-Z0-9]{1,64}'),
                            array('portfolio_id' => '[0-9]+'),
                            array('image_id' => '[0-9]+'));
                            
                $m->connect(':nickname/portfolio/:portfolio_id/image/:image_id/original',
                            array('action' => 'imagevieworiginal'),
                            array('nickname' => '[a-zA-Z0-9]{1,64}'),
                            array('portfolio_id' => '[0-9]+'),
                            array('image_id' => '[0-9]+'));
                

                foreach (array('subscriptions', 'subscribers') as $a) {
                    $m->connect(':nickname/'.$a.'/:tag',
                                array('action' => $a),
                                array('tag' => '[a-zA-Z0-9]+',
                                      'nickname' => '[a-zA-Z0-9]{1,64}'));
                }

                foreach (array('rss', 'groups') as $a) {
                    $m->connect(':nickname/'.$a,
                                array('action' => 'user'.$a),
                                array('nickname' => '[a-zA-Z0-9]{1,64}'));
                }

                foreach (array('all', 'replies', 'favorites') as $a) {
                    $m->connect(':nickname/'.$a.'/rss',
                                array('action' => $a.'rss'),
                                array('nickname' => '[a-zA-Z0-9]{1,64}'));
                }

                $m->connect(':nickname/favorites',
                            array('action' => 'showfavorites'),
                            array('nickname' => '[a-zA-Z0-9]{1,64}'));

                $m->connect(':nickname/avatar/:size',
                            array('action' => 'avatarbynickname'),
                            array('size' => '(original|96|48|24)',
                                  'nickname' => '[a-zA-Z0-9]{1,64}'));

                $m->connect(':nickname/tag/:tag/rss',
                            array('action' => 'userrss'),
                            array('nickname' => '[a-zA-Z0-9]{1,64}'),
                            array('tag' => '[a-zA-Z0-9]+'));

                $m->connect(':nickname',
                            array('action' => 'showstream'),
                            array('nickname' => '[a-zA-Z0-9]{1,64}'));

            Event::handle('RouterInitialized', array($m));
        }

        return $m;
    }

    function map($path)
    {
        try {
            $match = $this->m->match($path);
        } catch (Net_URL_Mapper_InvalidException $e) {
            common_log(LOG_ERR, "Problem getting route for $path - " .
                       $e->getMessage());
            $cac = new ClientErrorAction("Page not found.", 404);
            $cac->showPage();
        }

        return $match;
    }

    function build($action, $args=null, $params=null, $fragment=null)
    {
        $action_arg = array('action' => $action);

        if ($args) {
            $args = array_merge($action_arg, $args);
        } else {
            $args = $action_arg;
        }

        $url = $this->m->generate($args, $params, $fragment);

        // Due to a bug in the Net_URL_Mapper code, the returned URL may
        // contain a malformed query of the form ?p1=v1?p2=v2?p3=v3. We
        // repair that here rather than modifying the upstream code...

        $qpos = strpos($url, '?');
        if ($qpos !== false) {
            $url = substr($url, 0, $qpos+1) .
              str_replace('?', '&', substr($url, $qpos+1));
        }
        return $url;
    }
}
