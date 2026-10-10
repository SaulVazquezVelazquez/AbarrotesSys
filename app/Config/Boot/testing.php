<?php

error_reporting(-1);
ini_set('display_errors', '1');

defined('SHOW_DEBUG_BACKTRACE') || define('SHOW_DEBUG_BACKTRACE', true);
defined('CI_DEBUG') || define('CI_DEBUG', true);

if (! defined('HOMEPATH')) {
    define('HOMEPATH', realpath(rtrim(ROOTPATH, '\\/ ')) . DIRECTORY_SEPARATOR);
}
if (! defined('CONFIGPATH')) {
    define('CONFIGPATH', realpath(APPPATH . 'Config') . DIRECTORY_SEPARATOR);
}
if (! defined('PUBLICPATH')) {
    define('PUBLICPATH', realpath(rtrim(ROOTPATH, '\\/ ') . DIRECTORY_SEPARATOR . 'public') . DIRECTORY_SEPARATOR);
}
