<?php
/***************************************************************
*  Copyright noticef
*
*  (c) 2013 Johannes Kropf <johannes@kropf.at>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Plugin 'Pubdb list' for the 'pubdb' extension.
 *
 * @author	Johannes Kropf <johannes@kropf.at>
 */


class tx_pubdb_utils {

/**
	* Parse a ; separated list of full names
	* return: array of the form [index][surname,given_name]
	*/
	static function parseAuthors($coauthors) {
	
		
		$authors = explode(';', $coauthors);
		$i = 0;
		foreach( $authors as $author) {
			$res[$i] = self::parseFullname(trim($author));
			$i++;
		}
		return $res;
	}


	/**
	*	Parse a full name, surname and given name(s) separated by , or SPACE
	*	return: array [surname,given_name]
	*/
	static function parseFullname($fullname) {
		// split by any number of spaces or commas
		$names=preg_split('/[\s,]+/',$fullname);
		

		// if namestring contains comma, surname is first, else last
		if (strpos($fullname,',') === FALSE) {
			$res['surname']=$names[sizeof($names)-1];
			
			for ($i=0; $i<sizeof($names)-1; $i++) {
			        if ($i > 0) $res['given_name'] .= ' ';
	
				$res['given_name'].=$names[$i];
			}
		} else {
			$names= explode(',', $fullname);
		    	$res['surname']=trim($names[0]);
			for ($i=1; $i<sizeof($names); $i++) { 
				if ($i > 1) $res['given_name'] .= ' ';
				$res['given_name'].= trim($names[$i]);
			}

	  	}
		return $res;
	}

}

?>
