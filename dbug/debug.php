<?php
/*********************************************************************************************************************\
 * LAST UPDATE
 * ============
 * March 22, 2007
 *
 *
 * AUTHOR
 * =============
 * Kwaku Otchere
 * ospinto@hotmail.com
 *
 * Thanks to Andrew Hewitt (rudebwoy@hotmail.com) for the idea and suggestion
 *
 * All the credit goes to ColdFusion's brilliant cfdump tag
 * Hope the next version of PHP can implement this or have something similar
 * I love PHP, but var_dump BLOWS!!!
 *
 * FOR DOCUMENTATION AND MORE EXAMPLES: VISIT http://dbug.ospinto.com
 *
 *
 * PURPOSE
 * =============
 * Dumps/Displays the contents of a variable in a colored tabular format
 * Based on the idea, javascript and css code of Macromedia's ColdFusion cfdump tag
 * A much better presentation of a variable's contents than PHP's var_dump and print_r functions
 *
 *
 * USAGE
 * =============
 * new dBug ( variable [,forceType] );
 * example:
 * new dBug ( $myVariable );
 *
 *
 * if the optional "forceType" string is given, the variable supplied to the
 * function is forced to have that forceType type.
 * example: new dBug( $myVariable , "array" );
 * will force $myVariable to be treated and dumped as an array type,
 * even though it might originally have been a string type, etc.
 *
 * NOTE!
 * ==============
 * forceType is REQUIRED for dumping an xml string or xml file
 * new dBug ( $strXml, "xml" );
 *
 \*********************************************************************************************************************/

/** ensure this file is being included by a parent file */
defined( '_JEXEC' ) or die( 'Restricted access' );

class dBug
{

	public $xmlDepth = array( );
	public $xmlCData;
	public $xmlSData;
	public $xmlDData;
	public $xmlCount = 0;
	public $xmlAttrib;
	public $xmlName;
	public $arrType = array( "array", "object", "resource", "boolean", "NULL" );
	public $bInitialized = false;
	public $bCollapsed = false;
	public $arrHistory = array( );
	public $nb = 9;
	public $title = "";

	//constructor
	public function __construct( $var, $nb = 9, $title = "", $forceType = "", $bCollapsed = false )
	{

		$this->nb = $nb;
		$this->title = (string)$title;

		//include js and css scripts
		if( !defined( 'BDBUGINIT' ) ) //make use we only include the dbug.css and dbug.css once
		{
			define( "BDBUGINIT", TRUE );
			$doc = JFactory::getDocument( );
			$doc->addStyleSheet( JURI::root( true ) . '/plugins/system/dbug/dbug/debug.css' );
			$doc->addScript( JURI::root( true ) . '/plugins/system/dbug/dbug/debug.js' );
		}
		$arrAccept = array( "array", "object", "xml" );

		//array of variable types that can be "forced"
		$this->bCollapsed = $bCollapsed;
		if( in_array( $forceType, $arrAccept ) )
			$this->{"varIs".ucfirst($forceType)}( $var );
		else
			$this->checkType( $var );
	}

	//get variable name
	private function getVariableName( )
	{
		$arrBacktrace = debug_backtrace( );

		//possible 'included' functions
		$arrInclude = array( "include", "include_once", "require", "require_once" );

		//check for any included/required files. if found, get array of the last included file (they contain the right line numbers)
		for( $i = count( $arrBacktrace ) - 1; $i >= 0; $i-- )
		{
			$arrCurrent = $arrBacktrace[ $i ];
			if( array_key_exists( "function", $arrCurrent ) && (in_array( $arrCurrent[ "function" ], $arrInclude ) || (0 != strcasecmp( $arrCurrent[ "function" ], "dbug" ))) )
				continue;

			$arrFile = $arrCurrent;

			break;
		}

		if( isset( $arrFile ) )
		{
			$arrLines = file( $arrFile[ "file" ] );
			$code = $arrLines[ ($arrFile[ "line" ] - 1) ];

			//find call to dBug class
			preg_match( '/\bnew dBug\s*\(\s*(.+)\s*\);/i', $code, $arrMatches );
			if( count( $arrMatches ) )
				return $arrMatches[ 1 ];
		}
		return "";
	}

	//create the main table header
	private function makeTableHeader( $type, $header, $colspan = 2 )
	{
		if( !$this->bInitialized )
		{
			$header = $this->getVariableName( ) . " (" . $header . ")";
			$this->bInitialized = true;
		}
		$str_i = ($this->bCollapsed) ? "style=\"font-style:italic\" " : "";

		echo "<table cellspacing=2 cellpadding=3 class=\"dBug_" . $type . "\">
				<tr>
					<td " . $str_i . "class=\"dBug_" . $type . "Header\" colspan=" . $colspan . " onClick='dBug_toggleTable(this)'>" . $header . "</td>
				</tr>";
	}

	//create the table row header
	private function makeTDHeader( $type, $header )
	{
		$str_d = ($this->bCollapsed) ? " style=\"display:none\"" : "";
		echo "<tr" . $str_d . ">
				<td valign=\"top\" onClick='dBug_toggleRow(this)' class=\"dBug_" . $type . "Key\">" . $header . "</td>
				<td>";
	}

	//close table row
	private function closeTDRow( )
	{
		return "</td></tr>\n";
	}

	//error
	private function error( $type )
	{
		$error = "Error: Variable cannot be a";

		// this just checks if the type starts with a vowel or "x" and displays either "a" or "an"
		if( in_array( substr( $type, 0, 1 ), array( "a", "e", "i", "o", "u", "x" ) ) )
			$error .= "n";
		return ($error . " " . $type . " type");
	}

	//check variable type
	private function checkType( $var )
	{
		switch(gettype($var))
		{
			case "resource" :
				$this->varIsResource( $var );
				break;
			case "object" :
				$this->varIsObject( $var );
				break;
			case "array" :
				$this->varIsArray( $var );
				break;
			case "NULL" :
				$this->varIsNULL( );
				break;
			case "boolean" :
				$this->varIsBoolean( $var );
				break;
			default :
				$var = ($var == "") ? "[empty string]" : $var;
				echo "<table cellspacing=0><tr>\n<td>" . $var . "</td>\n</tr>\n</table>\n";
				break;
		}
	}

	//if variable is a NULL type
	private function varIsNULL( )
	{
		$null = "<strong>NULL</strong> ";
		$null .= $this->nb;
		$null .= !empty( $this->title ) ? " : " . $this->title : "";
		echo $null;
	}

	//if variable is a boolean type
	private function varIsBoolean( $var )
	{
		$bool = "Boolean: " . $this->nb;
		$bool .= '<span style="color: #006600; font-weight: font-weight:bold;">';
		$bool .= ($var == 1) ? "TRUE" : "FALSE";
		$bool .= "</span>";
		echo $bool;
	}

	//if variable is an array type
	private function varIsArray( $var )
	{
		$var_ser = serialize( $var );
		array_push( $this->arrHistory, $var_ser );

		$title = "array: " . (!empty( $this->nb ) ? $this->nb : "");
		$title .= !empty( $this->title ) ? ': ' . $this->title : '';
		$this->makeTableHeader( "array", $title );
		if( is_array( $var ) )
		{
			foreach( $var as $key => $value )
			{
				$this->makeTDHeader( "array", $key );

				//check for recursion
				if( is_array( $value ) )
				{
					$var_ser = serialize( $value );
					if( in_array( $var_ser, $this->arrHistory, TRUE ) )
						$value = "*RECURSION*";
				}

				if( in_array( gettype( $value ), $this->arrType ) )
					$this->checkType( $value );
				else
				{
					$value = (trim( $value ) == "") ? "[empty string]" : $value;
					echo $value;
				}
				echo $this->closeTDRow( );
			}
		} else
			echo "<tr><td>" . $this->error( "array" ) . $this->closeTDRow( );
		array_pop( $this->arrHistory );
		echo "</table>";
		$this->nb++;
	}

	//if variable is an object type
	private function varIsObject( $var )
	{
		$var_ser = serialize( $var );
		array_push( $this->arrHistory, $var_ser );

		$title = 'object: ' . ((!empty( $this->nb )) ? $this->nb : get_class( $var ));
		$title .= (!empty( $this->title )) ? ': ' . $this->title : '';
		$this->makeTableHeader( "object", $title );

		if( is_object( $var ) )
		{
			$arrObjVars = get_object_vars( $var );
			foreach( $arrObjVars as $key => $value )
			{

				$value = (!is_object( $value ) && !is_array( $value ) && @trim( $value ) == "") ? "[empty string]" : $value;
				$this->makeTDHeader( "object", $key );

				//check for recursion
				if( is_object( $value ) || is_array( $value ) )
				{
					$var_ser = serialize( $value );
					if( in_array( $var_ser, $this->arrHistory, TRUE ) )
					{
						$value = (is_object( $value )) ? "*RECURSION* -> $" . get_class( $value ) : "*RECURSION*";

					}
				}
				if( in_array( gettype( $value ), $this->arrType ) )
					$this->checkType( $value );
				else
					echo $value;
				echo $this->closeTDRow( );
			}
			$arrObjMethods = get_class_methods( get_class( $var ) );
			foreach( $arrObjMethods as $key => $value )
			{
				$this->makeTDHeader( "object", $value );
				echo "[function]" . $this->closeTDRow( );
			}
		} else
			echo "<tr><td>" . $this->error( "object" ) . $this->closeTDRow( );
		array_pop( $this->arrHistory );
		echo "</table>";
	}

	//if variable is a resource type
	private function varIsResource( $var )
	{
		$this->makeTableHeader( "resourceC", "resource", 1 );
		echo "<tr>\n<td>\n";
		switch(get_resource_type($var))
		{
			case "fbsql result" :
			case "mssql result" :
			case "msql query" :
			case "pgsql result" :
			case "sybase-db result" :
			case "sybase-ct result" :
			case "mysql result" :
				$db = current( explode( " ", get_resource_type( $var ) ) );
				$this->varIsDBResource( $var, $db );
				break;
			case "gd" :
				$this->varIsGDResource( $var );
				break;
			case "xml" :
				$this->varIsXmlResource( $var );
				break;
			default :
				echo get_resource_type( $var ) . $this->closeTDRow( );
				break;
		}
		echo $this->closeTDRow( ) . "</table>\n";
	}

	//if variable is a database resource type
	private function varIsDBResource( $var, $db = "mysql" )
	{
		if( $db == "pgsql" )
			$db = "pg";
		if( $db == "sybase-db" || $db == "sybase-ct" )
			$db = "sybase";
		$arrFields = array( "name", "type", "flags" );
		$numrows = call_user_func( $db . "_num_rows", $var );
		$numfields = call_user_func( $db . "_num_fields", $var );
		$this->makeTableHeader( "resource", $db . " result", $numfields + 1 );
		echo "<tr><td class=\"dBug_resourceKey\">&nbsp;</td>";
		for( $i = 0; $i < $numfields; $i++ )
		{
			$field_header = "";
			for( $j = 0; $j < count( $arrFields ); $j++ )
			{
				$db_func = $db . "_field_" . $arrFields[ $j ];
				if( function_exists( $db_func ) )
				{
					$fheader = call_user_func( $db_func, $var, $i ) . " ";
					if( $j == 0 )
						$field_name = $fheader;
					else
						$field_header .= $fheader;
				}
			}
			$field[ $i ] = call_user_func( $db . "_fetch_field", $var, $i );
			echo "<td class=\"dBug_resourceKey\" title=\"" . $field_header . "\">" . $field_name . "</td>";
		}
		echo "</tr>";
		for( $i = 0; $i < $numrows; $i++ )
		{
			$row = call_user_func( $db . "_fetch_array", $var, constant( strtoupper( $db ) . "_ASSOC" ) );
			echo "<tr>\n";
			echo "<td class=\"dBug_resourceKey\">" . ($i + 1) . "</td>";
			for( $k = 0; $k < $numfields; $k++ )
			{
				$tempField = $field[ $k ]->name;
				$fieldrow = $row[ ($field[ $k ]->name) ];
				$fieldrow = ($fieldrow == "") ? "[empty string]" : $fieldrow;
				echo "<td>" . $fieldrow . "</td>\n";
			}
			echo "</tr>\n";
		}
		echo "</table>";
		if( $numrows > 0 )
			call_user_func( $db . "_data_seek", $var, 0 );
	}

	//if variable is an image/gd resource type
	private function varIsGDResource( $var )
	{
		$this->makeTableHeader( "resource", "gd", 2 );
		$this->makeTDHeader( "resource", "Width" );
		echo imagesx( $var ) . $this->closeTDRow( );
		$this->makeTDHeader( "resource", "Height" );
		echo imagesy( $var ) . $this->closeTDRow( );
		$this->makeTDHeader( "resource", "Colors" );
		echo imagecolorstotal( $var ) . $this->closeTDRow( );
		echo "</table>";
	}

	//if variable is an xml type
	private function varIsXml( $var )
	{
		$this->varIsXmlResource( $var );
	}

	//if variable is an xml resource type
	private function varIsXmlResource( $var )
	{
		$xml_parser = xml_parser_create( );
		xml_parser_set_option( $xml_parser, XML_OPTION_CASE_FOLDING, 0 );
		xml_set_element_handler( $xml_parser, array( &$this, "xmlStartElement" ), array( &$this, "xmlEndElement" ) );
		xml_set_character_data_handler( $xml_parser, array( &$this, "xmlCharacterData" ) );
		xml_set_default_handler( $xml_parser, array( &$this, "xmlDefaultHandler" ) );

		$this->makeTableHeader( "xml", "xml document", 2 );
		$this->makeTDHeader( "xml", "xmlRoot" );

		//attempt to open xml file
		$bFile = (!($fp = @fopen( $var, "r" ))) ? false : true;

		//read xml file
		if( $bFile )
		{
			while( $data = str_replace( "\n", "", fread( $fp, 4096 ) ) )
				$this->xmlParse( $xml_parser, $data, feof( $fp ) );
		}
		//if xml is not a file, attempt to read it as a string
		else
		{
			if( !is_string( $var ) )
			{
				echo $this->error( "xml" ) . $this->closeTDRow( ) . "</table>\n";
				return;
			}
			$data = $var;
			$this->xmlParse( $xml_parser, $data, 1 );
		}

		echo $this->closeTDRow( ) . "</table>\n";

	}

	//parse xml
	private function xmlParse( $xml_parser, $data, $bFinal )
	{
		if( !xml_parse( $xml_parser, $data, $bFinal ) )
		{
			die( sprintf( "XML error: %s at line %d\n", xml_error_string( xml_get_error_code( $xml_parser ) ), xml_get_current_line_number( $xml_parser ) ) );
		}
	}

	//xml: inititiated when a start tag is encountered
	private function xmlStartElement( $parser, $name, $attribs )
	{
		$this->xmlAttrib[ $this->xmlCount ] = $attribs;
		$this->xmlName[ $this->xmlCount ] = $name;
		$this->xmlSData[ $this->xmlCount ] = '$this->makeTableHeader("xml","xml element",2);';
		$this->xmlSData[ $this->xmlCount ] .= '$this->makeTDHeader("xml","xmlName");';
		$this->xmlSData[ $this->xmlCount ] .= 'echo "<strong>' . $this->xmlName[ $this->xmlCount ] . '</strong>".$this->closeTDRow();';
		$this->xmlSData[ $this->xmlCount ] .= '$this->makeTDHeader("xml","xmlAttributes");';
		if( count( $attribs ) > 0 )
			$this->xmlSData[ $this->xmlCount ] .= '$this->varIsArray($this->xmlAttrib[' . $this->xmlCount . ']);';
		else
			$this->xmlSData[ $this->xmlCount ] .= 'echo "&nbsp;";';
		$this->xmlSData[ $this->xmlCount ] .= 'echo $this->closeTDRow();';
		$this->xmlCount++;
	}

	//xml: initiated when an end tag is encountered
	private function xmlEndElement( $parser, $name )
	{
		for( $i = 0; $i < $this->xmlCount; $i++ )
		{
			eval( $this->xmlSData[ $i ] );
			$this->makeTDHeader( "xml", "xmlText" );
			echo(!empty( $this->xmlCData[ $i ] )) ? $this->xmlCData[ $i ] : "&nbsp;";
			echo $this->closeTDRow( );
			$this->makeTDHeader( "xml", "xmlComment" );
			echo(!empty( $this->xmlDData[ $i ] )) ? $this->xmlDData[ $i ] : "&nbsp;";
			echo $this->closeTDRow( );
			$this->makeTDHeader( "xml", "xmlChildren" );
			unset( $this->xmlCData[ $i ], $this->xmlDData[ $i ] );
		}
		echo $this->closeTDRow( );
		echo "</table>";
		$this->xmlCount = 0;
	}

	//xml: initiated when text between tags is encountered
	private function xmlCharacterData( $parser, $data )
	{
		$count = $this->xmlCount - 1;
		if( !empty( $this->xmlCData[ $count ] ) )
			$this->xmlCData[ $count ] .= $data;
		else
			$this->xmlCData[ $count ] = $data;
	}

	//xml: initiated when a comment or other miscellaneous texts is encountered
	private function xmlDefaultHandler( $parser, $data )
	{
		//strip '<!--' and '-->' off comments
		$data = str_replace( array( "&lt;!--", "--&gt;" ), "", htmlspecialchars( $data ) );
		$count = $this->xmlCount - 1;
		if( !empty( $this->xmlDData[ $count ] ) )
			$this->xmlDData[ $count ] .= $data;
		else
			$this->xmlDData[ $count ] = $data;
	}

}
