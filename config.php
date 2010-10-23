<?php
if (!defined('NEWTYPE') && !defined('DWORKS')) { exit(1); }

$config['site']['name'] = 'MBtest';

$config['site']['server'] = '192.168.1.100';
$config['site']['path'] = 'sn'; 

$config['db']['database'] = 'mysqli://himmel:irvine@127.0.0.1/MB';

$config['db']['type'] = 'mysql';

$config['site']['languages'] = array(
        'zh_CN'      => array('q' => 1, 'lang' => 'zh_CN',    'name' => 'Simplified Chinese', 'direction' => 'ltr'),
);
