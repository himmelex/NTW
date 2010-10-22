<?php
/**
 * Table Definition for portfolio
 */
require_once INSTALLDIR.'/classes/Memcached_DataObject.php';

class Portfolio extends Memcached_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'portfolio';                       // table name
    public $id;                              // int(20)  not_null primary_key unsigned auto_increment
    public $name;                            // string(128)  not_null
    public $owner;                           // int(64)  not_null
    public $catalog;                         // string(255)  binary
    public $coverpic;                        // string(255)  binary
    public $description;                     // string(255)  
    public $created;                         // datetime(19)  not_null binary
    public $modified;                        // timestamp(19)  not_null unsigned zerofill binary timestamp

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Portfolio',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
    
    static function addPortfolio($fields) {

        extract($fields);
        
    	$portfolio = new Portfolio();
    	$portfolio->name = $name;
    	$portfolio->owner = $owner;
    	$portfolio->created = common_sql_now();
    	$portfolio->modified = common_sql_now();
    	
        $result = $portfolio->insert();

        if (!$result) {
            common_log_db_error($portfolio, 'INSERT', __FILE__);
            return false;
        }
        
        return $portfolio;
    }
    
    function getUser() {
    	$user = User::staticGet('id', $this->owner);
        if (empty($user)) {
            common_log(LOG_WARNING, sprintf("User does not exist."),
                               __FILE__);
        }
        return $user;
    }
    
    function getImageList($offset=0, $limit=null ,$size=0)
    {
        $qry =
          'SELECT image.* ' .
          'FROM image '.
          'WHERE portfolio_id = %d ' .
          'AND size = %d ' .
          'ORDER BY created DESC ';

        if ($offset>0 && !is_null($limit)) {
            if ($offset) {
                if (common_config('db','type') == 'pgsql') {
                    $qry .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;
                } else {
                    $qry .= ' LIMIT ' . $offset . ', ' . $limit;
                }
            }
        }

        $image = new Image();

        $image->query(sprintf($qry, $this->id, $size));

        return $image;
    }
    
}
