<?php

if (!defined('NEWTYPE') && !defined('DWORKS')) {
    exit(1);
}

class UploadAction extends Action {
	
	var $msg = null;
	var $success=false;
	var $user = null;

    function prepare($args)
    {
        parent::prepare($args);
        $nickname   = common_canonical_nickname($this->arg('nickname'));
        $this->user = User::staticGet('nickname', $nickname);
        return true;
    }
    
    function handle($args)
    {
        parent::handle($args);

        if (!$this->user) {
            $this->clientError(_('用户不存在'), 404);
            return;
        }

        $cur = common_current_user();

        if (!$cur || $cur->id != $this->user->id) {
            $this->clientError(_('只有登录后才能上传'),
                403);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->handlePost();
        } else {
            $this->showPage();
        }
    }
	
    function title()
    {
        return sprintf(_("作品上传"));
    }
    function showContent() 
    {
    	$this->showCreatPortfolioForm();
    	$this->showUploadForm();
    }
    
    function showScripts() 
    {
        parent::showScripts();
// for SWFupload
//        $this->script ( 'swfupload/swfupload.js' );
//        $this->script ( 'swfupload/swfupload_config.js' );
//        $this->script ( 'swfupload/handlers.js' );
//        $this->script ( 'swfupload/fileprogress.js' );
//        $this->script ( 'swfupload/swfupload.queue.js' );
    }
    
    function showCreatPortfolioForm() 
    {
    	$this->element('h2', null, _('创建作品集'));
        $this->elementStart('form', array('method' => 'post',
                                          'action' => common_local_url('upload', array('nickname' => $this->arg('nickname')))));
        $this->input('portfolio-title','输入标题' );
        $this->hidden('token', common_session_token());
        $this->submit('creat',_('创 建'));
        $this->elementEnd('form');
    }
    
    function showUploadForm()
    {
        $this->elementStart('form', array('method' => 'post',
                                          'action' => common_local_url('upload', array('nickname' => $this->arg('nickname'))),
                                          'enctype' => 'multipart/form-data'));
		
		$this->element('h2', null, _('选择上传至作品集'));
        $profile = $this->user->getProfile();
        $portfolios = $profile->getPortfolioList();
        if ($portfolios) {
            $portfolioList = new PortfolioList($portfolios, $this->user, $this);    
        }
		$portfolioList->showPortfolioDropdown();
		
		$this->element('h2', null, _('选择要上传的图片文件:'));
		$this->element ('input', array ('name' => 'works', 
                                        'type' => 'file', 
                                        'id' => 'works' ,
		                                'text' => '123'));
		
		$this->hidden('token', common_session_token());
		$this->submit('upload', _('上 传')); 
        $this->elementEnd('form');
    }
    
    function showSWFUploadForm() 
    {
    	$this->element('h2', null, __('Upload Your Works'));
    	$this->elementStart('form', array('method' => 'post',
                                          'action' => common_local_url('upload'),
                                          'enctype' => 'multipart/form-data'));
        $this->element('div', array('class' => 'fieldset flash',
                                    'id' => 'fsUploadProgress'));
        $this->raw('<span id="spanButtonPlaceHolder"></span>');
        $this->raw('<div id="divStatus">0 Files Uploaded</div>');
        $this->raw('<input id="btnCancel" class="submit" type="button" value="Cancel" onclick="swfu.cancelQueue();" disabled="disabled"/>');
        $this->elementEnd('form');
    }
    
    function handlePost()
    {
    	if(array_key_exists('upload', $_POST))
    	{
    		$this->handleUpload();
    	} else {
    		$this->creatPortfolio();
    	}
    }
    
    function handleUpload() 
    {
        // Workaround for PHP returning empty $_POST and $_FILES when POST
        // length > post_max_size in php.ini
        if (empty($_FILES)
            && empty($_POST)
            && ($_SERVER['CONTENT_LENGTH'] > 0)
        ) {
            $msg = _('上传文件超过最大限制');

            $this->showForm(sprintf($msg, $_SERVER['CONTENT_LENGTH']));
            return;
        }

        // CSRF protection

        $token = $this->trimmed('token');
        if (!$token || $token != common_session_token()) {
            $this->showForm(_('网页错误,请返回重试'));
            
            return;
        }
        
        //upload
        try {
            $imagefile = ImageFile::fromUpload('works');
        } catch (Exception $e) {
            $this->showForm($e->getMessage());
            return;
        }
        if ($imagefile === null) {
            $this->showForm(_('请选择要上传的文件'));
            return;
        }

        $cur = common_current_user();
        $timestamp = common_timestamp();
        $filename = Image::filename($cur->id,
                                     image_type_to_extension($imagefile->type),
                                     null,
                                     $timestamp);
        
        $portfolio_id = $this->arg('portfolio-select');
                                     
        $image = Image::addImage(array('portfolio_id' => $portfolio_id,
                                       'width' => $imagefile->width,
                                       'height' => $imagefile->height,
                                       'type' => $imagefile->type,
                                       'filename' => $filename ));
          
        $filepath = $image->filepath;
        
        @mkdir($filepath, 0777, true);
        move_uploaded_file($imagefile->filepath, $filepath . $filename);

        //resize
        
        $imagefileResize = new ImageFile($this->user->id, $filepath . $filename);
        
        foreach (array(600, 150, 100) as $size) {
	        $filenameResized = Image::filename($cur->id,
	                                     image_type_to_extension($imagefile->type),
	                                     $size,
	                                     $timestamp);
	        $imagefileResize->resize($size, $filenameResized, $filepath . $filenameResized);
        }

        $this->showForm(_('Upload success.'));
    }
    
    function creatPortfolio()
    {
            $token = $this->trimmed('token');
            if (!$token || $token != common_session_token()) {
                $this->showForm(_('网页错误,请返回重试
                                  '));
                return;
            }

            $name = $this->arg('portfolio-title');
            $owner = $this->user->id;
                
            if (!$name) {
                $this->showForm(_('请输入作品集名称'));
            } else if ($portfolio = Portfolio::addPortfolio(array(
                                                    'name' => $name,
                                                    'owner' => $owner))) {
                if (!$portfolio) {
                    $this->showForm(_('无法创建作品集'));
                    return;
                } else {
                	$this->showForm(_('已创建作品集'));
                }
    	   }
    	   
    }
    
    function showForm($msg=null, $success=false)
    {
        $this->msg     = $msg;
        $this->success = $success;

        $this->showPage();
    }
    
    function showPageNotice()
    {
        if ($this->msg) {
            $this->element('div', ($this->success) ? 'success' : 'error',
                           $this->msg);
        }
    }
}
