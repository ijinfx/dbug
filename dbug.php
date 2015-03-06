<?php
/**
 *  @version	2.5
 * 	@package	dbug
 * 	@author 	Gerald R. Zalsos
 * 	@link 		http://www.klaraontheweb.com
 * 	@copyright 	Copyright (C) 2015 klaraontheweb.com All rights reserved.
 * 	@license 	Licensed under the GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

/** ensure this file is being included by a parent file */
defined( '_JEXEC' ) or die( 'Restricted access' );

class plgSystemDbug extends JPlugin
{
	public function __construct( &$subject, $config = array() )
	{
		parent::__construct( $subject, $config );

		if( $this->allowDbug( ) )
		{
			$path = rtrim( JPATH_SITE, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'dbug' . DIRECTORY_SEPARATOR . 'dbug' . DIRECTORY_SEPARATOR . 'debug.php';

			try
			{
				require_once ($path);
			} catch(Exception $e)
			{
				echo "Could not load dBug class at {$path},<br/>please disable the dbug system plugin";
			}

		}

	}

	/**
	 * Method to check we if include the dBug library
	 *
	 * @return boolean
	 */
	private function allowDbug( )
	{
		$allow = false;
		$type = $this->params->get( 'type', 'all' );
		switch($type)
		{
			case 'ip' :
				$explodeIP = explode( ',', str_replace( ' ', '', $this->params->get( 'ip' ) ) );
				if( is_array( $explodeIP ) )
				{
					$ip = '';
					if( !empty( $_SERVER[ 'HTTP_CLIENT_IP' ] ) )//check ip from share internet
					{
						$ip = $_SERVER[ 'HTTP_CLIENT_IP' ];
					} elseif( !empty( $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] ) )//to check ip is pass from proxy
					{
						$ip = $_SERVER[ 'HTTP_X_FORWARDED_FOR' ];
					} else
					{
						$ip = $_SERVER[ 'REMOTE_ADDR' ];
					}

					if( in_array( $ip, $explodeIP ) || $ip == '127.0.0.1' )//always show localhost
						$allow = true;
				}
				break;
			case 'userid' :
				$explodeUID = explode( ',', str_replace( ' ', '', $this->params->get( 'userid' ) ) );
				if( is_array( $explodeUID ) )
				{
					$userid = JFactory::getUser( )->id;
					if( in_array( $userid, $explodeUID ) )
						$allow = true;
				}
				break;
			case 'access' :
				$access = $this->params->get( 'access' );
				$authorisedlevels = JFactory::getUser( )->getAuthorisedViewLevels( );

				if( in_array( $access, $authorisedlevels ) )
					$allow = true;

				break;
			case 'usergroup' :
				$usergroups = $this->params->get( 'usergroup' );
				$authorisedgroups = JFactory::getUser( )->getAuthorisedGroups( );

				if( array_intersect( $authorisedgroups, $usergroups ) )
					$allow = true;

				break;
			case 'all' :
			default :
				$allow = true;
				break;
		}

		return $allow;
	}

}

/**
 * Method to dump the structure of a variable for debugging purposes
 * @param mixed 	$var 		- the variable to dump
 * @param int 		$nb 		- heading number for reference
 * @param string 	$title 		- text in the header/title to better track you debugs
 * @param boolean	$bCollapsed	- to collapsed or not the debug on load
 * @return unknown
 */
function dbug( $var = '', $nb = 0, $title = '', $bCollapsed = false )
{
	if( !class_exists( 'Dbug' ) )
		return '';

	(int)$nb;

	if( is_string( $var ) )
		$var = str_replace( array( "\r\n", "\r", "\n", "\t" ), array( '\r\n', '\r', '\n', '\t' ), $var );

	return new dBug( $var, $nb, $title, '', $bCollapsed );
}
