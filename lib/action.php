<?php

if (! defined ( 'NEWTYPE' ) && ! defined ( 'DWORKS' )) {
	exit ( 1 );
}

require_once INSTALLDIR . '/lib/noticeform.php';
require_once INSTALLDIR . '/lib/htmloutputter.php';


class Action extends HTMLOutputter // lawsuit
{
	var $args;
	var $left_section = null;
    var $right_section = null;

	function __construct($output = 'php://output', $indent = null) {
		parent::__construct ( $output, $indent );
	}
	
	/**
	 * For initializing members of the class.
	 *
	 * @param array $argarray misc. arguments
	 *
	 * @return boolean true
	 */
	function prepare($argarray) {
		$this->args = & common_copy_args ( $argarray );
		return true;
	}
	
	/**
	 * Show page, a template method.
	 *
	 * @return nothing
	 */
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
			$this->showBody ();
			Event::handle ( 'EndShowBody', array ($this ) );
		}
		if (Event::handle ( 'StartEndHTML', array ($this ) )) {
			$this->endHTML ();
			Event::handle ( 'EndEndHTML', array ($this ) );
		}
	}
	
	/**
	 * Show head, a template method.
	 *
	 * @return nothing
	 */
	function showHead() {
		$this->elementStart ( 'head' );
		if (Event::handle ( 'StartShowHeadElements', array ($this ) )) {
			$this->showTitle ();
			$this->showShortcutIcon ();
			$this->showStylesheets ();
			$this->showDescription ();
			$this->extraHead ();
			Event::handle ( 'EndShowHeadElements', array ($this ) );
		}
		$this->elementEnd ( 'head' );
	}
	
	/**
	 * Show title, a template method.
	 *
	 * @return nothing
	 */
	function showTitle() {
		$this->element ( 'title', null, sprintf ( _ ( "%1\$s - %2\$s" ), $this->title (), common_config ( 'site', 'name' ) ) );
	}
	
	/**
	 * Returns the page title
	 *
	 * SHOULD overload
	 *
	 * @return string page title
	 */
	
	function title() {
		return _( "Newtype设计档案" );
	}
	
	/**
	 * Show themed shortcut icon
	 *
	 * @return nothing
	 */
	function showShortcutIcon() {
		if (is_readable ( INSTALLDIR . '/theme/' . common_config ( 'site', 'theme' ) . '/favicon.ico' )) {
			$this->element ( 'link', array ('rel' => 'shortcut icon', 'href' => Theme::path ( 'favicon.ico' ) ) );
		} else {
			$this->element ( 'link', array ('rel' => 'shortcut icon', 'href' => common_path ( 'favicon.ico' ) ) );
		}
		
		if (common_config ( 'site', 'mobile' )) {
//			if (is_readable ( INSTALLDIR . '/theme/' . common_config ( 'site', 'theme' ) . '/apple-touch-icon.png' )) {
//				$this->element ( 'link', array ('rel' => 'apple-touch-icon', 'href' => Theme::path ( 'apple-touch-icon.png' ) ) );
//			} else {
//				$this->element ( 'link', array ('rel' => 'apple-touch-icon', 'href' => common_path ( 'apple-touch-icon.png' ) ) );
//			}
		}
	}
	
	/**
	 * Show stylesheets
	 *
	 * @return nothing
	 */
	function showStylesheets() {
		
		$this->cssLink ( 'css/960_24_col.css', 'base', null );
		$this->cssLink ( 'css/base.css', 'base', null );
	}
	
	/**
	 * Show javascript headers
	 *
	 * @return nothing
	 */
	function showScripts() {

				$this->script ( 'jquery.min.js' );
				$this->script ( 'jquery.form.js' );
				$this->script ( 'jquery.cookie.js' );
				$this->inlineScript ( 'if (typeof window.JSON !== "object") { $.getScript("' . common_path ( 'js/json2.js' ) . '"); }' );
				$this->script ( 'jquery.joverlay.min.js' );
				$this->script ( 'xbImportNode.js' );
				$this->script ( 'util.js' );
				$this->script ( 'geometa.js' );
				// Frame-busting code to avoid clickjacking attacks.
				$this->inlineScript ( 'if (window.top !== window.self) { window.top.location.href = window.self.location.href; }' );

	}
		
	/**
	 * Show feed headers
	 *
	 * MAY overload
	 *
	 * @return nothing
	 */
	
	function showFeeds() {
		$feeds = $this->getFeeds ();
		
		if ($feeds) {
			foreach ( $feeds as $feed ) {
				$this->element ( 'link', array ('rel' => $feed->rel (), 'href' => $feed->url, 'type' => $feed->mimeType (), 'title' => $feed->title ) );
			}
		}
	}
	
	/**
	 * Show description.
	 *
	 * SHOULD overload
	 *
	 * @return nothing
	 */
	function showDescription() {
		// does nothing by default
	}
	
	/**
	 * Show extra stuff in <head>.
	 *
	 * MAY overload
	 *
	 * @return nothing
	 */
	function extraHead() {
		// does nothing by default
	}
	
	/**
	 * Show body.
	 *
	 * Calls template methods
	 *
	 * @return nothing
	 */
	function showBody() {
		$this->elementStart ( 'body', (common_current_user ()) ? array ('id' => $this->trimmed ( 'action' ), 'class' => 'user_in' ) : array ('id' => $this->trimmed ( 'action' ) ) );
		$this->elementStart ( 'div', array ('id' => 'wrap', 'class' => 'container_24' ) );
		$this->showHeader ();
		$this->showUserNav();
		$this->showCore ();
		$this->showFooter ();
		$this->elementEnd ( 'div' );
		$this->showScripts ();
		$this->elementEnd ( 'body' );
	}
	
	/**
	 * Show header of the page.
	 *
	 * Calls template methods
	 *
	 * @return nothing
	 */
	function showHeader() {
		$this->elementStart ( 'div', array ('id' => 'header' ) );
		$this->showLogo ();
		$this->showPrimaryNav ();
		if (Event::handle ( 'StartShowSiteNotice', array ($this ) )) {
			$this->showSiteNotice ();
			
			Event::handle ( 'EndShowSiteNotice', array ($this ) );
		}
		$this->elementEnd ( 'div' );
	}
	
	/**
	 * Show configured logo.
	 *
	 * @return nothing
	 */
	function showLogo() {
		$this->elementStart ( 'address', array ('id' => 'site_contact', 'class' => 'vcard grid_8' ) );
		
		$url = common_local_url ( 'public' );
		
		$this->elementStart ( 'a', array ('class' => 'url home bookmark', 'href' => $url ) );
		
		$this->element ( 'img', array ('class' => 'logo photo', 'src' => (common_config ( 'site', 'logo' )) ? common_config ( 'site', 'logo' ) : Theme::path ( 'logo.png' ), 'alt' => common_config ( 'site', 'name' ) ) );
		
		$this->text ( ' ' );
		//$this->element ( 'span', array ('class' => 'fn org' ), common_config ( 'site', 'name' ) );
		$this->elementEnd ( 'a' );
		
		$this->elementEnd ( 'address' );
	}
	
	/**
	 * Show primary navigation.
	 *
	 * @return nothing
	 */
	function showPrimaryNav() {
		$user = common_current_user ();
		$this->elementStart ( 'dl', array ('id' => 'site_nav_global_primary', 'class' => 'grid_16' ) );
		//$this->element('dt', null, _('Primary site navigation'));
		$this->elementStart ( 'dd' );
		$this->elementStart ( 'ul', array ('class' => 'nav' ) );
		if (Event::handle ( 'StartPrimaryNav', array ($this ) )) {
			if ($user) {
				$tooltip = _m ( 'TOOLTIP', '个人中心' );
                $this->menuItem ( common_local_url ( 'userhome', array('nickname' => $user->nickname) ) , _m ( 'MENU', '个人中心' ), $tooltip, false, 'nav_home' );
				if ($user->hasRight ( Right::CONFIGURESITE )) {
					$tooltip = _m ( 'TOOLTIP', 'Change site configuration' );
					$this->menuItem ( common_local_url ( 'siteadminpanel' ), _m ( 'MENU', '管理' ), $tooltip, false, 'nav_admin' );
				}
				$tooltip = _m ( 'TOOLTIP', '登出网站' );
				// TRANS: Main menu option when logged in to log out the current user
				$this->menuItem ( common_local_url ( 'logout' ), _m ( 'MENU', '退出' ), $tooltip, false, 'nav_logout' );
			} else {
				if (! common_config ( 'site', 'closed' ) && ! common_config ( 'site', 'inviteonly' )) {
					// TRANS: Tooltip for main menu option "Register"
					$tooltip = _m ( 'TOOLTIP', '建立设计师帐号发布作品案例' );
					// TRANS: Main menu option when not logged in to register a new account
					$this->menuItem ( common_local_url ( 'register', array ('type' => 'D' ) ), _m ( 'MENU', '设计师注册' ), $tooltip, false, 'nav_register_D' );
					$tooltip = _m ( 'TOOLTIP', '建立企业账号发布招募信息' );
					// TRANS: Main menu option when not logged in to register a new account
					$this->menuItem ( common_local_url ( 'register', array ('type' => 'C' ) ), _m ( 'MENU', '企业注册' ), $tooltip, false, 'nav_register_C' );
				}
				// TRANS: Tooltip for main menu option "Login"
				$tooltip = _m ( 'TOOLTIP', '用户登录' );
				// TRANS: Main menu option when not logged in to log in
				$this->menuItem ( common_local_url ( 'login' ), _m ( 'MENU', '登录' ), $tooltip, false, 'nav_login' );
			}
			// TRANS: Tooltip for main menu option "Help"
			$tooltip = _m ( 'TOOLTIP', '帮助信息' );
			// TRANS: Main menu option for help on the StatusNet site
			$this->menuItem ( common_local_url ( 'doc', array ('title' => 'help' ) ), _m ( 'MENU', '帮助' ), $tooltip, false, 'nav_help' );
			Event::handle ( 'EndPrimaryNav', array ($this ) );
		}
		$this->elementEnd ( 'ul' );
		$this->elementEnd ( 'dd' );
		$this->elementEnd ( 'dl' );
	}
	
	/**
	 * Show site notice.
	 *
	 * @return nothing
	 */
	function showSiteNotice() {
		$text = common_config ( 'site', 'notice' );
		if ($text) {
			$this->elementStart ( 'dl', array ('id' => 'site_notice', 'class' => 'system_notice' ) );
			$this->element ( 'dt', null, _ ( 'Site notice' ) );
			$this->elementStart ( 'dd', null );
			$this->raw ( $text );
			$this->elementEnd ( 'dd' );
			$this->elementEnd ( 'dl' );
		}
	}
	
	
	function showUserNav()
	{
		$user = common_current_user ();
	    if ($user) {
	        $this->elementStart ( 'dl', array ('id' => 'user_nav', 'class' => 'grid_24' ) );
	        $this->elementStart ( 'dd' );
	        $this->elementStart ( 'ul', array ('class' => 'nav' ) );
	        $tooltip = _m ( 'TOOLTIP', '个人中心' );
	        $this->menuItem ( common_local_url ( 'userhome', array('nickname' => $user->nickname) ) , _m ( 'MENU', '个人中心' ), $tooltip, false, 'nav_home' );
	        $this->menuItem(common_local_url('upload', array('nickname' => $user->nickname)),
                            _('上传作品'),
                            _('上传作品'));
	        $this->menuItem(common_local_url('portfoliolist', array('nickname' =>$user->nickname)),
                            _('管理作品'),
                            _('管理作品'));
            $this->menuItem(common_local_url('inbox', array('nickname' =>$user->nickname)),
                            _('收件箱'),
                            _('收件箱'));
	        $tooltip = _m ( 'TOOLTIP', '修改基本信息、密码、完善个人档案' );
	        $this->menuItem ( common_local_url ( 'profilesettings' ), _ ( '帐号管理' ), $tooltip, false, 'nav_account' );
            if (common_config ( 'invite', 'enabled' )) {
                $tooltip = _m ( 'TOOLTIP', '邀请你的设计师伙伴注册' );
                $this->menuItem ( common_local_url ( 'invite' ), _m ( 'MENU', '邀请注册' ), sprintf ( $tooltip, common_config ( 'site', 'name' ) ), false, 'nav_invitecontact' );
            }
            $this->menuItem('#',
                            _('设计档案库'),
                            _('设计档案库'));
            $this->menuItem('#',
                            _('招募信息板'),
                            _('招募信息板'));
	        $this->elementEnd ( 'ul' );
	        $this->elementEnd ( 'dd' );
	        $this->elementEnd ( 'dl' );
	    }
	}
	
	/**
	 * Show notice form.
	 *
	 *
	 * @return nothing
	 */
	function showNoticeForm() {
		$notice_form = new NoticeForm ( $this );
		$notice_form->show ();
	}
	
	/**
	 * Show anonymous message.
	 *
	 * SHOULD overload
	 *
	 * @return nothing
	 */
	function showAnonymousMessage() {
		// needs to be defined by the class
	}
	
	/**
	 * Show core.
	 *
	 * Shows local navigation, content block and aside.
	 *
	 * @return nothing
	 */
	function showCore() {
		$this->elementStart ( 'div', array ('id' => 'core' ) );
		$this->showContentBlock ();
		$this->elementEnd ( 'div' );
	}
	
	/**
	 * Show local navigation block.
	 *
	 * @return nothing
	 */
	function showLocalNavBlock() {
		$this->elementStart ( 'dl', array ('id' => 'site_nav_local_views', 'class' => '' ) );
		$this->elementStart ( 'dd' );
		$this->showLocalNav ();
		$this->elementEnd ( 'dd' );
		$this->elementEnd ( 'dl' );
	}
	
	/**
	 * Show local navigation.
	 *
	 * SHOULD overload
	 *
	 * @return nothing
	 */
	function showLocalNav() {
		// does nothing by default
	}
	
	/**
	 * Show content block.
	 *
	 * @return nothing
	 */
	function showContentBlock() {
		if($this->left_section && $this->right_section) {
			$this->showLeft();
			$this->elementStart ( 'div', array ('id' => 'content', 'class' => 'grid_14' ) );
		} elseif ($this->left_section) {
			$this->showLeft();
			$this->elementStart ( 'div', array ('id' => 'content', 'class' => 'grid_19' ) );
		} else {
			$this->elementStart ( 'div', array ('id' => 'content', 'class' => 'grid_24' ) );
		}
		$this->showLocalNavBlock ();
		$this->showPageNoticeBlock ();
		$this->showContent ();
		$this->elementEnd ( 'div' );
		if($this->right_section) {
			$this->showRight();
		}
	}
	
	/**
	 * Show page title.
	 *
	 * @return nothing
	 */
	function showPageTitle() {
		$this->element ( 'h1', null, $this->title () );
	}
	
	/**
	 * Show page notice block.
	 *
	 * Only show the block if a subclassed action has overrided
	 * Action::showPageNotice(), or an event handler is registered for
	 * the StartShowPageNotice event, in which case we assume the
	 * 'page_notice' definition list is desired.  This is to prevent
	 * empty 'page_notice' definition lists from being output everywhere.
	 *
	 * @return nothing
	 */
	function showPageNoticeBlock() {
		$rmethod = new ReflectionMethod ( $this, 'showPageNotice' );
		$dclass = $rmethod->getDeclaringClass ()->getName ();
		
		if ($dclass != 'Action' || Event::hasHandler ( 'StartShowPageNotice' )) {
			
			$this->elementStart ( 'dl', array ('id' => 'page_notice', 'class' => 'system_notice' ) );
			$this->element ( 'dt', null, _ ( 'Page notice' ) );
			$this->elementStart ( 'dd' );
			if (Event::handle ( 'StartShowPageNotice', array ($this ) )) {
				$this->showPageNotice ();
				Event::handle ( 'EndShowPageNotice', array ($this ) );
			}
			$this->elementEnd ( 'dd' );
			$this->elementEnd ( 'dl' );
		}
	}
	
	/**
	 * Show page notice.
	 *
	 * SHOULD overload (unless there's not a notice)
	 *
	 * @return nothing
	 */
	function showPageNotice() {
	}
	
	/**
	 * Show content.
	 *
	 * MUST overload (unless there's not a notice)
	 *
	 * @return nothing
	 */
	function showContent() {
	}
	
	/**
	 * Show Aside.
	 *
	 * @return nothing
	 */
	
	function showLeft() {
		$this->elementStart ( 'div', array ('id' => 'left', 'class' => 'side grid_5' ) );
		$this->showLeftSections ();
		$this->elementEnd ( 'div' );
	}
	
   function showRight() {
        $this->elementStart ( 'div', array ('id' => 'right', 'class' => 'side grid_5' ) );
        $this->showRightSections ();
        $this->elementEnd ( 'div' );
    }
    
    function showLeftSections() {
        // for each section, show it
    }
    
    function showRightSections() {
        // for each section, show it
    }
	
	/**
	 * Show export data feeds.
	 *
	 * @return void
	 */
	
	function showExportData() {
		$feeds = $this->getFeeds ();
		if ($feeds) {
			$fl = new FeedList ( $this );
			$fl->show ( $feeds );
		}
	}
	
	/**
	 * Show footer.
	 *
	 * @return nothing
	 */
	function showFooter() {
		$this->elementStart ( 'div', array ('id' => 'footer' ) );
		$this->showSecondaryNav ();
		//    $this->showLicenses();
		$this->elementEnd ( 'div' );
	}
	
	/**
	 * Show secondary navigation.
	 *
	 * @return nothing
	 */
	function showSecondaryNav() {
		$this->elementStart ( 'dl', array ('id' => 'site_nav_global_secondary', 'class' => 'grid_24' ) );
		$this->elementStart ( 'dd', null );
		$this->elementStart ( 'ul', array ('class' => 'nav' ) );
		if (Event::handle ( 'StartSecondaryNav', array ($this ) )) {
			$this->menuItem ( common_local_url ( 'doc', array ('title' => 'help' ) ), _ ( '帮助' ) );
			$this->menuItem ( common_local_url ( 'doc', array ('title' => 'about' ) ), _ ( '关于' ) );
			$this->menuItem ( common_local_url ( 'doc', array ('title' => 'faq' ) ), _ ( '常见问题' ) );
			$this->menuItem ( common_local_url ( 'doc', array ('title' => 'privacy' ) ), _ ( '隐私声明' ) );
			$this->menuItem ( common_local_url ( 'doc', array ('title' => 'contact' ) ), _ ( '联系我们' ) );
			Event::handle ( 'EndSecondaryNav', array ($this ) );
		}
		$this->elementEnd ( 'ul' );
		$this->elementEnd ( 'dd' );
		$this->elementEnd ( 'dl' );
	}
	
	function showLicenses() {
	}
	
	function showContentLicense() {
		if (Event::handle ( 'StartShowContentLicense', array ($this ) )) {
			$this->element ( 'dt', array ('id' => 'site_content_license' ), _ ( 'Site content license' ) );
			$this->elementStart ( 'dd', array ('id' => 'site_content_license_cc' ) );
			
			switch (common_config ( 'license', 'type' )) {
				case 'private' :
					$this->element ( 'p', null, sprintf ( _ ( 'Content and data of %1$s are private and confidential.' ), common_config ( 'site', 'name' ) ) );
				// fall through
				case 'allrightsreserved' :
					if (common_config ( 'license', 'owner' )) {
						$this->element ( 'p', null, sprintf ( _ ( 'Content and data copyright by %1$s. All rights reserved.' ), common_config ( 'license', 'owner' ) ) );
					} else {
						$this->element ( 'p', null, _ ( 'Content and data copyright by contributors. All rights reserved.' ) );
					}
					break;
				case 'cc' : // fall through
				default :
					$this->elementStart ( 'p' );
					$this->element ( 'img', array ('id' => 'license_cc', 'src' => common_config ( 'license', 'image' ), 'alt' => common_config ( 'license', 'title' ), 'width' => '80', 'height' => '15' ) );
					$this->text ( ' ' );
					// TRANS: license message in footer. %1$s is the site name, %2$s is a link to the license URL, with a licence name set in configuration.
					$notice = _ ( 'All %1$s content and data are available under the %2$s license.' );
					$link = "<a class=\"license\" rel=\"external license\" href=\"" . htmlspecialchars ( common_config ( 'license', 'url' ) ) . "\">" . htmlspecialchars ( common_config ( 'license', 'title' ) ) . "</a>";
					$this->raw ( sprintf ( htmlspecialchars ( $notice ), htmlspecialchars ( common_config ( 'site', 'name' ) ), $link ) );
					$this->elementEnd ( 'p' );
					break;
			}
			
			$this->elementEnd ( 'dd' );
			Event::handle ( 'EndShowContentLicense', array ($this ) );
		}
	}
	
	/**
	 * Return last modified, if applicable.
	 *
	 * MAY override
	 *
	 * @return string last modified http header
	 */
	function lastModified() {
		// For comparison with If-Last-Modified
		// If not applicable, return null
		return null;
	}
	
	/**
	 * Return etag, if applicable.
	 *
	 * MAY override
	 *
	 * @return string etag http header
	 */
	function etag() {
		return null;
	}
	
	/**
	 * Return true if read only.
	 *
	 * MAY override
	 *
	 * @param array $args other arguments
	 *
	 * @return boolean is read only action?
	 */
	
	function isReadOnly($args) {
		return false;
	}
	
	/**
	 * Returns query argument or default value if not found
	 *
	 * @param string $key requested argument
	 * @param string $def default value to return if $key is not provided
	 *
	 * @return boolean is read only action?
	 */
	function arg($key, $def = null) {
		if (array_key_exists ( $key, $this->args )) {
			return $this->args [$key];
		} else {
			return $def;
		}
	}
	
	/**
	 * Returns trimmed query argument or default value if not found
	 *
	 * @param string $key requested argument
	 * @param string $def default value to return if $key is not provided
	 *
	 * @return boolean is read only action?
	 */
	function trimmed($key, $def = null) {
		$arg = $this->arg ( $key, $def );
		return is_string ( $arg ) ? trim ( $arg ) : $arg;
	}
	
	/**
	 * Handler method
	 *
	 * @param array $argarray is ignored since it's now passed in in prepare()
	 *
	 * @return boolean is read only action?
	 */
	function handle($argarray = null) {
		header ( 'Vary: Accept-Encoding,Cookie' );
		$lm = $this->lastModified ();
		$etag = $this->etag ();
		if ($etag) {
			header ( 'ETag: ' . $etag );
		}
		if ($lm) {
			header ( 'Last-Modified: ' . date ( DATE_RFC1123, $lm ) );
			if (array_key_exists ( 'HTTP_IF_MODIFIED_SINCE', $_SERVER )) {
				$if_modified_since = $_SERVER ['HTTP_IF_MODIFIED_SINCE'];
				$ims = strtotime ( $if_modified_since );
				if ($lm <= $ims) {
					$if_none_match = (array_key_exists ( 'HTTP_IF_NONE_MATCH', $_SERVER )) ? $_SERVER ['HTTP_IF_NONE_MATCH'] : null;
					if (! $if_none_match || ! $etag || $this->_hasEtag ( $etag, $if_none_match )) {
						header ( 'HTTP/1.1 304 Not Modified' );
						// Better way to do this?
						exit ( 0 );
					}
				}
			}
		}
	}
	
	/**
	 * Has¬†etag? (private)
	 *
	 * @param string $etag          etag http header
	 * @param string $if_none_match ifNoneMatch http header
	 *
	 * @return boolean
	 */
	
	function _hasEtag($etag, $if_none_match) {
		$etags = explode ( ',', $if_none_match );
		return in_array ( $etag, $etags ) || in_array ( '*', $etags );
	}
	
	/**
	 * Boolean understands english (yes, no, true, false)
	 *
	 * @param string $key query key we're interested in
	 * @param string $def default value
	 *
	 * @return boolean interprets yes/no strings as boolean
	 */
	function boolean($key, $def = false) {
		$arg = strtolower ( $this->trimmed ( $key ) );
		
		if (is_null ( $arg )) {
			return $def;
		} else if (in_array ( $arg, array ('true', 'yes', '1', 'on' ) )) {
			return true;
		} else if (in_array ( $arg, array ('false', 'no', '0' ) )) {
			return false;
		} else {
			return $def;
		}
	}
	
	/**
	 * Integer value of an argument
	 *
	 * @param string $key      query key we're interested in
	 * @param string $defValue optional default value (default null)
	 * @param string $maxValue optional max value (default null)
	 * @param string $minValue optional min value (default null)
	 *
	 * @return integer integer value
	 */
	
	function int($key, $defValue = null, $maxValue = null, $minValue = null) {
		$arg = strtolower ( $this->trimmed ( $key ) );
		
		if (is_null ( $arg ) || ! is_integer ( $arg )) {
			return $defValue;
		}
		
		if (! is_null ( $maxValue )) {
			$arg = min ( $arg, $maxValue );
		}
		
		if (! is_null ( $minValue )) {
			$arg = max ( $arg, $minValue );
		}
		
		return $arg;
	}
	
	/**
	 * Server error
	 *
	 * @param string  $msg  error message to display
	 * @param integer $code http error code, 500 by default
	 *
	 * @return nothing
	 */
	
	function serverError($msg, $code = 500) {
		$action = $this->trimmed ( 'action' );
		common_debug ( "Server error '$code' on '$action': $msg", __FILE__ );
		throw new ServerException ( $msg, $code );
	}
	
	/**
	 * Client error
	 *
	 * @param string  $msg  error message to display
	 * @param integer $code http error code, 400 by default
	 *
	 * @return nothing
	 */
	
	function clientError($msg, $code = 400) {
		$action = $this->trimmed ( 'action' );
		common_debug ( "User error '$code' on '$action': $msg", __FILE__ );
		throw new ClientException ( $msg, $code );
	}
	
	/**
	 * Returns the current URL
	 *
	 * @return string current URL
	 */
	
	function selfUrl() {
		list ( $action, $args ) = $this->returnToArgs ();
		return common_local_url ( $action, $args );
	}
	
	/**
	 * Returns arguments sufficient for re-constructing URL
	 *
	 * @return array two elements: action, other args
	 */
	
	function returnToArgs() {
		$action = $this->trimmed ( 'action' );
		$args = $this->args;
		unset ( $args ['action'] );
		if (common_config ( 'site', 'fancy' )) {
			unset ( $args ['p'] );
		}
		if (array_key_exists ( 'submit', $args )) {
			unset ( $args ['submit'] );
		}
		foreach ( array_keys ( $_COOKIE ) as $cookie ) {
			unset ( $args [$cookie] );
		}
		return array ($action, $args );
	}
	
	/**
	 * Generate a menu item
	 *
	 * @param string  $url         menu URL
	 * @param string  $text        menu name
	 * @param string  $title       title attribute, null by default
	 * @param boolean $is_selected current menu item, false by default
	 * @param string  $id          element id, null by default
	 *
	 * @return nothing
	 */
	function menuItem($url, $text, $title = null, $is_selected = false, $id = null) {
		// Added @id to li for some control.
		// XXX: We might want to move this to htmloutputter.php
		$lattrs = array ();
		if ($is_selected) {
			$lattrs ['class'] = 'current';
		}
		
		(is_null ( $id )) ? $lattrs : $lattrs ['id'] = $id;
		
		$this->elementStart ( 'li', $lattrs );
		$attrs ['href'] = $url;
		if ($title) {
			$attrs ['title'] = $title;
		}
		$this->element ( 'a', $attrs, $text );
		$this->elementEnd ( 'li' );
	}
	
	/**
	 * Generate pagination links
	 *
	 * @param boolean $have_before is there something before?
	 * @param boolean $have_after  is there something after?
	 * @param integer $page        current page
	 * @param string  $action      current action
	 * @param array   $args        rest of query arguments
	 *
	 * @return nothing
	 */
	function pagination($have_before, $have_after, $page, $action, $args = null) {
		// Does a little before-after block for next/prev page
		if ($have_before || $have_after) {
			$this->elementStart ( 'dl', 'pagination' );
			$this->element ( 'dt', null, _ ( 'Pagination' ) );
			$this->elementStart ( 'dd', null );
			$this->elementStart ( 'ul', array ('class' => 'nav' ) );
		}
		if ($have_before) {
			$pargs = array ('page' => $page - 1 );
			$this->elementStart ( 'li', array ('class' => 'nav_prev' ) );
			$this->element ( 'a', array ('href' => common_local_url ( $action, $args, $pargs ), 'rel' => 'prev' ), _ ( '<<' ) );
			$this->elementEnd ( 'li' );
		}
		if ($have_after) {
			$pargs = array ('page' => $page + 1 );
			$this->elementStart ( 'li', array ('class' => 'nav_next' ) );
			$this->element ( 'a', array ('href' => common_local_url ( $action, $args, $pargs ), 'rel' => 'next' ), _ ( '>>' ) );
			$this->elementEnd ( 'li' );
		}
		if ($have_before || $have_after) {
			$this->elementEnd ( 'ul' );
			$this->elementEnd ( 'dd' );
			$this->elementEnd ( 'dl' );
		}
	}
	
	/**
	 * An array of feeds for this action.
	 *
	 * Returns an array of potential feeds for this action.
	 *
	 * @return array Feed object to show in head and links
	 */
	
	function getFeeds() {
		return null;
	}
	
	/**
	 * A design for this action
	 *
	 * @return Design a design object to use
	 */
	
	function getDesign() {
		return Design::siteDesign ();
	}
	
	/**
	 * Check the session token.
	 *
	 * Checks that the current form has the correct session token,
	 * and throw an exception if it does not.
	 *
	 * @return void
	 */
	
	function checkSessionToken() {
		// CSRF protection
		$token = $this->trimmed ( 'token' );
		if (empty ( $token ) || $token != common_session_token ()) {
			$this->clientError ( _ ( 'There was a problem with your session token.' ) );
		}
	}
}
