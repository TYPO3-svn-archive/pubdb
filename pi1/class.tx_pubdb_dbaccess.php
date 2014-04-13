<?php
/***************************************************************
 *  Copyright notice
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

class pubdbAccess {
	
	
	var $pub2cat;

	
	function __construct() {

		// load the publication to category mapping
		$this->pub2cat = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid_local as pubid,uid_foreign as catid','tx_pubdb_data_category_mm');
		$this->cat2group = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid_local as catid,uid_foreign as groupid','tx_pubdb_category_fegroups_mm');
		//debug($this->pub2cat,'pub2cat');
		//debug($this->cat2group,'cat2group');
	}
	
	/**
	 * Get an array of contributors for a comma separated list of publication ids
	 * @param String $pubids
	 */
	
	function fetchContributorsByPubId($pubids) {
		// fetch contributors
		$contributorsResult =  $GLOBALS['TYPO3_DB']->exec_SELECTquery('c.*,r.pubid,r.contributorid,r.role,r.contributorsort,r.pubsort',
				'tx_pubdb_contributors c JOIN tx_pubdb_pub_contributors r ON c.uid = r.contributorid ',
				'r.pubid IN ('.$pubids.') AND r.deleted=0 AND c.deleted=0',
				'','r.pubid,r.pubsort');
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($contributorsResult))  {
			$cont[$row['pubid']][] = $row;
				
		}
		//	t3lib_utility_Debug::debug($cont,'contributors');
		return $cont;
	}
	
	/**
	 * Fetches publications by search
	 * @param String $field The database field to search in
	 * @param String $match The string the field shall match
	 * @param String $orderby The field to sort the results, 'ASC' or 'DESC' may be added to define the direction
	 * @param Integer $limit Limit of result (default=1000)
	 * @param Integer $category - optinally a category ID can be defined to restrict the search to it
	 * @return Array
	 */
	
	function fetchSearchResult($field,$match,$orderby,$limit=1000, $category='0') {
		if ($match != '') {
			// if search string given search in the given field, else take everything
			$wherecl = 'tx_pubdb_data.'.$field.' LIKE "%'.$match.'%"'; 

			if ($field === 'author') 
				$wherecl .= ' OR tx_pubdb_data.coauthors LIKE "%'.$match.'%"';
			
		} else {
			$wherecl = 'tx_pubdb_data.deleted="0"';
		
		}
		// Add NOT hidden and not deleted clause
		$wherecl .= ' AND tx_pubdb_data.hidden="0" AND tx_pubdb_data.deleted="0"';
		
		// choose category from dropdownlist (0 for all)
		if ($category==='0')
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_pubdb_data',$wherecl);
		else {
			$mywherecl = 'AND '.$wherecl.' AND tx_pubdb_data_category_mm.uid_foreign="'.$category.'"';
			$result = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('tx_pubdb_data.*','tx_pubdb_data','tx_pubdb_data_category_mm','tx_pubdb_categories',$mywherecl);
		}
		
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) > 0) {
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
				$publications['pubs'][$row['uid']] = $row;
				$pubids[] = $row['uid'];
			}
		}
		
		// if searching for author, search also contributors
		if ($match != '' && $field === 'author') {
			// get all categories
			//$categories = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,name','tx_pubdb_categories');
			
			$table = 'tx_pubdb_data p JOIN tx_pubdb_pub_contributors rel ON p.uid=rel.pubid JOIN tx_pubdb_contributors c ON rel.contributorid = c.uid';
			$where = 'c.surname LIKE "%'.$match.'%" OR c.given_name LIKE "%'.$match.'%"';
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('p.*',$table,$where);
			//debug($result);
			
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
				if (!key_exists($publications) &&  (( $category!=='0' && $this->categorymatch($pub2cat, $row['uid'], $catetory)) || $category === '0')) {
					$publications['pubs'][$row['uid']] = $row;
					$pubids[] = $row['uid'];
				}
			}
		}
		
		$publications['contributors'] = $this->fetchContributorsByPubId(implode(',',$pubids));
		
		return $publications;
		
	
	}
	
	function fetchCategoriesByNameAsString($names) {
		// quote the names
		$namesArr = explode(',', $names);
		$in = '';
		foreach ($namesArr as $n) {
			if ($in !== '') $in .= ',';
			$in .= '"'.$n.'"';
		} 
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid','tx_pubdb_categories','name IN ('.$in.')');
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$ids[] = $row['uid'];
		}
		
		return implode(',',$ids);
	}
		
	/**
	 * Get publications by two category groups connected via AND or OR
	 * Categories wihin a variable are connected via OR
	 * The groups can be conncected via AND, makes really sense if there is only one category in a group 
	 * @param String $category1 comma separeated ids first category selection 
	 * @param String $category2 comma separated ids of second category selection
	 * @param String $rel 'AND' or 'OR'
	 * @param String $sortby field name to sort results by, optionally can have suffixes ASC or DESC with a space in between, e.g. "year ASC"
	 * @return A result array
	 */
	function fetchPubsByCategories($categories1, $categories2='0', $rel='OR', $sortby='title DESC', $limit=1000) {
	
		//$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = true;
		
		if ($categories1 === '0' OR $categories1 === '') {
			return;
		}
		
		if ($categories2 != '0' && $categories2 != '' && $rel=='AND') {
			$validAndRel = true;
		} else {
			$validAndRel = false;
		}
		
		
		$mywherecl = 'AND tx_pubdb_data.hidden="0" AND tx_pubdb_data.deleted="0"';
		// if relation is OR, just joint the category lists
		if ($categories2 != '0' && $categories2 != '' && $rel==='OR') {
				$mywherecl1 = $mywherecl.' AND tx_pubdb_data_category_mm.uid_foreign IN ('.$categories1.','.$categories2.')';
		} else {
				$mywherecl1 = $mywherecl.' AND tx_pubdb_data_category_mm.uid_foreign IN ('.$categories1.')';
		}
	
		$result = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('tx_pubdb_data.*','tx_pubdb_data','tx_pubdb_data_category_mm','tx_pubdb_categories',
				$mywherecl1,'',$sortby,$limit);
		
		// if relation is AND make second query
		if ($categories2 != '0' && $categories2 != '' && $rel==='AND') {
			$mywherecl2 = $mywherecl.' AND tx_pubdb_data_category_mm.uid_foreign IN ('.$categories2.')';
			$result2 = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('tx_pubdb_data.*','tx_pubdb_data','tx_pubdb_data_category_mm','tx_pubdb_categories',
					$mywherecl2,'',$sortby,$limit);
			
			// fetch the uids only
			while($row =  $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result2)) {
				$res2Array[$row['uid']] = $row['uid'];
			}
		}
		
		
		//debug($GLOBALS['TYPO3_DB']->debug_lastBuiltQuery,'query');
		
		// Fetch the results and add it to the assoc array holding all publications
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
			
			// if we have an AND relation, take only pubs which are also in the second result  
			if ( ($validAndRel && array_key_exists($row['uid'], $res2Array)) || !$validAndRel) { 
				$publications['pubs'][$row['uid']] = $row;
				$pubids[] = $row['uid'];
			}
		}
	
		// fetch contributors
		$publications['contributors'] = $this->fetchContributorsByPubId(implode(',',$pubids));
		
		return $publications;
	}
	
		
    /**
     * Fetches all parents and its contributors for a result array of publications and adds it to the array.
     * A new key 'parents' is created in the array at root level
     * @param Array $publications The list of publications
     * @return Array
     */
	function fetchAndAddParents($publications) {
		$parentids = array();
		foreach ($publications['pubs'] as $pub) {
			if ($pub['parent_pubid'] != 0 && !key_exists($pub['parent_pubid'])) 
				$parentids[] = $pub['parent_pubid'];	
		}
		
		$idString = implode(',', $parentids);
		$wherecl = 'hidden="0" AND deleted="0" AND uid IN ('.$idString.')';
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_pubdb_data',$wherecl);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
			$publications['parents'][$row['uid']] = $row;
			$pubids[] = $row['uid'];
		}
		
		$contributors = $this->fetchContributorsByPubId(implode(',',$pubids));
		
		foreach ($contributors as $pubid=>$value) {
			if (!key_exists($pubid, $publications['contributors'])) {
				$publications['contributors'][$pubid] = $value;
			}
		}
		
		return $publications;
	}

	/**
	 * Fetches all publications with its contributors which have the publication given by id as parent 
	 * @param String $pubid id of the publication
	 */
	function fetchChildren($pubid) {
		
		$resultChildren = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_pubdb_data','parent_pubid='.$pubid);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resultChildren)) {
			$childPubs['pubs'][$row['uid']] = $row;
			$pubids[] = $row['uid'];
		}

		$childPubs['contributors'] = $this->fetchContributorsByPubId(implode(',',$pubids));
		return $childPubs; 
	}
	
	/**
	 * Fetches a single publication by its id from the database and adds all related contributors to the result array
	 * @param String $pubid
	 */
	function fetchSinglePub($pubid) {
		// fetch publication
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_pubdb_data','uid='.$pubid.' AND tx_pubdb_data.hidden="0" AND tx_pubdb_data.deleted="0"');
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
		$publication['pubs'][$row['uid']] = $row;
		
		// fetch contributors
		$publication['contributors'] = $this->fetchContributorsByPubId($pubid);
		
		return $publication;
	
	}
	
	/**
	 * Checks, if any entry in list of fe usergroups has permission to download the file
	 * @param String $fe_usergroups comma separated list of fe_usergroup ids
	 * @param String $pubid The id of the publication
	 */
	function hasPubAccess($fe_usergroups, $pubid) {
	   
		foreach ($this->pub2cat as $entry) {
	    	if ($entry['pubid'] === $pubid && $this->hasCatAccess($fe_usergroups, $entry['catid'])) 
	    		return TRUE;		    	
	    }	
	    return FALSE;
	}
	
	/**
	 * Checks if any entry in list of fe usergroups matches the given category 
	 * @param String $fe_usergroups comma separated list of fe_usergroup ids
	 * @param String $catid A publication category id
	 */
	
	function hasCatAccess($fe_usergroups, $catid) {
		  $groups = explode(',',$fe_usergroups);
		  
		  foreach ($this->cat2group as $entry) {
		  	if ($entry['catid'] === $catid && in_array($entry['groupid'], $groups))
		  		return TRUE; 
		  }  
		  return FALSE;
	}
	
	
	/**
	 * Helper function to check if a pair of values matches in an associative array
	 * @param unknown_type $array
	 * @param unknown_type $pubid
	 * @param unknown_type $catid
	 */
	
	function categorymatch($array, $pubid, $catid) {
		foreach ($array as $a) {
			if ($a['pubid'] === $pubid && $a['catid'] === $catid) {
				return TRUE;
			}
		}
		return FALSE;
	}
	
	
		
}


?>