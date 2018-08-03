<?php
/**
 *  @version	2.5
 * 	@package	dbug
 * 	@author 	Gerald R. Zalsos
 * 	@link 		http://www.klaraontheweb.com
 * 	@copyright 	Copyright (C) 2015 klaraontheweb.com All rights reserved.
 * 	@license 	Licensed under the GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

/**
 * ensure this file is being included by a parent file
 */
defined('_JEXEC') or die('Restricted access');

class plgSystemDbug extends JPlugin
{

	public function __construct (&$subject, $config = array())
	{			
		parent::__construct($subject, $config);
		
		if ($this->isShowDbug())
		{
			require_once JPATH_SITE . '/plugins/system/dbug/dbug/debug.php';
		}
	}

	/**
	 * Method to check we if include the dBug library
	 *
	 * @return boolean
	 */
	private function isShowDbug ()
	{
		$allow = false;
		$type = $this->params->get('type', 'all');
		switch ($type)
		{
			case 'ip':
				$explodeIP = explode(',', str_replace(' ', '', $this->params->get('ip')));
				if (is_array($explodeIP))
				{
					$ip = '';
					// check ip from share internet
					if (! empty($_SERVER['HTTP_CLIENT_IP']))
					{
						$ip = $_SERVER['HTTP_CLIENT_IP'];
					}
					// to check ip is pass from proxy
					elseif (! empty($_SERVER['HTTP_X_FORWARDED_FOR']))
					{
						$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
					}
					else
					{
						$ip = $_SERVER['REMOTE_ADDR'];
					}
					
					// always show localhost
					if (in_array($ip, $explodeIP) || $ip == '127.0.0.1')
						$allow = true;
				}
				break;
			case 'userid':
				$explodeID = explode(',', str_replace(' ', '', $this->params->get('userid')));
				if (is_array($explodeUID))
				{
					$userid = \JFactory::getUser()->id;
					if (in_array($userid, $explodeID))
						$allow = true;
				}
				break;
			case 'access':
				$access = $this->params->get('access');
				$authorisedlevels = \JFactory::getUser()->getAuthorisedViewLevels();
				
				if (in_array($access, $authorisedlevels))
					$allow = true;
				
				break;
			case 'usergroup':
				$usergroups = $this->params->get('usergroup');
				$authorisedgroups = \JFactory::getUser()->getAuthorisedGroups();
				
				if (array_intersect($authorisedgroups, $usergroups))
					$allow = true;
				
				break;
			case 'all':
			default:
				$allow = true;
				break;
		}
		
		return $allow;
	}
}