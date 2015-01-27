<?php
/**
 * @version 2.5
 * @package dBug
 * @author  Gerald Zalsos
 * @link    http://www.geraldzalsos.com
 * @copyright Copyright (C) 2011 geraldzalsos.com. All rights reserved.
 * @license GNU General Public License version 2 or later
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/** ensure this file is being included by a parent file */
defined( '_JEXEC' ) or die( 'Restricted access' );


class plgSystemDbug extends JPlugin 
{

	public static function triggerDbug() 
	{
		if ( $allow = plgSystemDbug::includeFile() ) 
		{
			
			$path = rtrim(JPATH_SITE,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'system'.DIRECTORY_SEPARATOR.'dbug'.DIRECTORY_SEPARATOR.'dbug'.DIRECTORY_SEPARATOR.'debug.php';
			
			try
			{
				require_once($path);
			}
			catch(Exception $e) 
			{
	 			echo "Could not load dBug class at {$path},<br/>please disable the dbug system plugin";
	 		}		
			
		}
	}

	private static function includeFile() 
	{
		$type = $this->params->get( 'type', 'all' );
		$allow = false;
		switch($type) 
		{
			case 'ip':
				$explodeIP = explode( ',', $this->params->get( 'ip' ) );
				if ( is_array( $explodeIP ) ) 
				{
					$ip = '';
					if ( !empty( $_SERVER['HTTP_CLIENT_IP'] ) )//check ip from share internet
					{
						$ip = $_SERVER['HTTP_CLIENT_IP'];
					} elseif ( !empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )//to check ip is pass from proxy
					{
						$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
					} else {
						$ip = $_SERVER['REMOTE_ADDR'];
					}

					if ( in_array( $ip, $explodeIP ) || $ip == '127.0.0.1' )//always show localhost
						$allow = true;
				}
				break;
			case 'userid':
				$explodeUID = explode( ',', $this->params->get( 'userid' ) );
				if ( is_array( $explodeUID ) ) 
				{
					$userid = JFactory::getUser( )->id;
					if ( in_array( $userid, $explodeUID ) )
						$allow = true;
				}
				break;
			case 'usertype':
				$explodeutype = explode( ',', strtolower( $this->params->get( 'usertype' ) ) );
				if ( is_array( $explodeutype ) ) {
					$usertype = strtolower( JFactory::getUser( )->usertype );

					if ( in_array( $usertype, $explodeutype ) )
						$allow = true;
				}
				break;
			case 'access':
				$access = $this->params->get( 'access' );
				$authorisedlevels = JFactory::getUser( )->getAuthorisedViewLevels( );

				if ( in_array( $access, $authorisedlevels ) )
					$allow = true;

				break;
			case 'usergroup':
				$usergroups = $this->params->get( 'usergroup' );
				$authorisedgroups = JFactory::getUser( )->getAuthorisedGroups( );

				if ( array_intersect( $authorisedgroups, $usergroups ) )
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

/**
 * Method to dump the structure of a variable for debugging purposes
 *
 * @param int $nb - heading number for reference
 * @param mixed $var - the vaiable to dump
 * @return unknown
 */
function dbug( $nb = 9, $var = '', $title = '' ) 
{

	plgSystemDbug::triggerDbug( );

	if ( !class_exists( 'Dbug' ) )
		return '';

	if ( !is_numeric( $nb ) ) 
	{
		$var = $nb;
		$nb = 0;
	}

	if ( is_string( $var ) )
		$var = str_replace( array( "\r\n", "\r", "\n", "\t" ), array( '\r\n', '\r', '\n', '\t' ), $var );

	return new Dbug( $var, $nb, $title );
}
