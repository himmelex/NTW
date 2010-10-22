<?php
/**
 * Table Definition for image
 */
require_once INSTALLDIR.'/classes/Memcached_DataObject.php';

class Image extends Memcached_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'image';                           // table name
    public $id;                              // int(20)  not_null primary_key unsigned auto_increment
    public $portfolio_id;                    // int(11)  not_null
    public $size;                            // int(1)  unsigned
    public $width;                           // int(11)  not_null unsigned
    public $height;                          // int(11)  not_null unsigned
    public $mediatype;                       // string(32)  not_null binary
    public $filename;                        // string(255)  binary
    public $server_url;                      // string(255)  not_null binary
    public $title;                           // string(255)  not_null binary
    public $score;                           // int(11)  
    public $created;                         // datetime(19)  not_null binary
    public $modified;                        // timestamp(19)  not_null unsigned zerofill binary timestamp

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Image',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
    public $filepath;
    
    static function addImage($fields) {
        extract($fields);
        
        $image = new Image();
        $image->portfolio_id = $portfolio_id;
        $image->width = $width;
        $image->height = $height;
        $image->mediatype = $type;
        $image->filename = $filename;
        $image->created = common_sql_now();
        $image->modified = common_sql_now();
        $image->server_url = $image->setServer();
        $result = $image->insert();

        if (!$result) {
            common_log_db_error($user, 'INSERT', __FILE__);
            return false;
        }
        
        $dir = common_config('img', 'dir');
        $subdir = str_pad($image->id, 8, '0', STR_PAD_LEFT);
        $subdir = str_split($subdir, 3);
        $image->filepath = $dir . $subdir[0] ."/". $subdir[1] ."/";
        return $image;
    }
    
    function delete()
    {
        $filename = $this->filename;
        if (parent::delete()) {
            //@unlink(Image::path($filename));
        }
    }

    function pkeyGet($kv)
    {
        return Memcached_DataObject::pkeyGet('Image', $kv);
    }

    static function filename($id, $extension, $size=null, $extra=null)
    {
        if ($size) {
            return $id . '-' . $size . (($extra) ? ('-' . $extra) : '') . $extension;
        } else {
            return $id . '-original' . (($extra) ? ('-' . $extra) : '') . $extension;
        }
    }

    function setServer()
    {
        $path = common_config('img', 'path');

        if ($path[strlen($path)-1] != '/') {
            $path .= '/';
        }

        if ($path[0] != '/') {
            $path = '/'.$path;
        }

        $server = common_config('img', 'server');

        if (empty($server)) {
            $server = common_config('site', 'server');
        }

        return 'http://'.$server.$path;
    }
    
    function getUrl()
    {
    	$subdir = str_pad($this->id, 8, '0', STR_PAD_LEFT);
        $subdir = str_split($subdir, 3);
        $url = $this->server ."/". $subdir[0] ."/". $subdir[1] ."/" . $this->filename;
        return $url;
    }
    
}
