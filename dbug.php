<?php
/**
 * @package dBug Plugin for Joomla!
 * @version 1.00.0
 * @author Gerald R. Zalsos
 * @copyright (C) 2014- Gerald R. Zalsos
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
**/

/** ensure this file is being included by a parent file */
defined('_JEXEC') or die('Restricted access');

/** Import library dependencies */
jimport('joomla.plugin.plugin');

class plgSystemDbug extends JPlugin {

	public function plgSystemDbug(&$subject, $config) {
		parent::__construct($subject, $config);
	}
	
	public static function triggerDbug()
	{
		// Get plugin info
    	$plugin =& JPluginHelper::getPlugin('system', 'dbug');    	
		
		if (version_compare(JVERSION,'1.6.0','ge')) {				    
			$params = new JRegistry( $plugin->params );
			$path = JPATH_SITE . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'dbug' . DIRECTORY_SEPARATOR . 'dbug' . DIRECTORY_SEPARATOR . 'debug.php';
		} else {
		    // Joomla! 1.5 code here
		   $params = new JParameter( $plugin->params );
		   $path = JPATH_SITE . DS . 'plugins' . DS . 'system' . DS . 'dbug' . DS . 'debug.php';
		}
		
		if ($allow = plgSystemDbug::includeFile($params)) {
			if (file_exists($path)) {
				require_once ($path);
			} else {
				JError::raiseNotice(20, 'The dBug Plugin needs dBug Class');
			}
		}	
	}

	private static function includeFile($params) {
		$type = $params->def('type', 'all');		
		$allow = false;
		switch($type) {
			case 'ip' :
				$explodeIP = explode(',', $params->def('ip'));				
				if(is_array($explodeIP)) {
					$ip = plgSystemDbug::getRealIpAddr();					
					if(in_array($ip, $explodeIP) || $ip == '127.0.0.1') //always show localhost
						$allow = true;
				}							
				break;
			case 'userid':
				$explodeUID = explode(',', $params->def('userid'));
				if(is_array($explodeUID)) {					
					$userid = JFactory::getUser()->id;										
					if(in_array($userid, $explodeUID)) 
						$allow = true;
				}					
				break;
			case 'usertype':
				$explodeutype = explode(',', strtolower($params->def('usertype')));
				if(is_array($explodeutype)) {					
					$usertype = strtolower(JFactory::getUser()->usertype) ;
										
					if(in_array($usertype, $explodeutype)) 
						$allow = true;
				}					
				break;
			case 'all' :
			default :
				$allow = true;
				break;
		}

		return $allow;
	}

	private static function getRealIpAddr() {
		if (!empty($_SERVER['HTTP_CLIENT_IP']))//check ip from share internet
		{
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))//to check ip is pass from proxy
		{
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}
}

/**
 * Method to dump the structure of a variable for debugging purposes
 *
 * @param int $nb - heading number for reference
 * @param mixed $var - the vaiable to dump
 * @return unknown
 */
function dbug($nb = 9, $var = '', $title = '') {
			
	plgSystemDbug::triggerDbug();

	if (!class_exists('Dbug'))
		return '';

	if (!is_numeric($nb)) {
		$var = $nb;
		$nb = 0;
	}

	if (is_string($var))
		$var = str_replace(array("\r\n", "\r", "\n", "\t"), array('\r\n', '\r', '\n', '\t'), $var);

	return new Dbug($var, $nb, $title);
}
