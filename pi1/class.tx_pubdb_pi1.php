<?php
/***************************************************************
 *  Copyright notice
*
*  (c) 2007 Johannes Kropf <johannes@kropf.at>
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


require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(PATH_typo3conf.'ext/pubdb/lib/class.tx_pubdb_toxml.php');
require_once(PATH_typo3conf.'ext/pubdb/lib/class.tx_pubdb_utils.php');

class tx_pubdb_pi1 extends tslib_pibase {
	var $scriptRelPath = 'pi1/class.tx_pubdb_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey = 'pubdb';	// The extension key.
	var $pi_checkCHash = TRUE;
	var $ffdata;
	var $singlepageID;
	var $showSearchForm = 0;
	var $prefixId = 'tx_pubdb_pi1'; // Same as class name
	var $standardTemplate = 'typo3conf/ext/pubdb/pi1/template.tmpl';
	//  $this->local_cObj = t3lib_div::makeInstance('tslib_cObj');
	/*
	$filelinks .= $this->local_cObj->filelink($val, $this->conf(['newsFiles.']) ;
			*/
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The		content that is displayed on the website
	 */
	function main($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();

		$this->pi_initPIflexForm();

		// load the flexform data array
		$this->ffdata = $this->cObj->data['pi_flexform'];

		$this->singlepageID = $this->pi_getFFValue($this->ffdata,'singlepid','sOtherSettings');
		$this->showSearchForm = $this->pi_getFFValue($this->ffdata,'showsearchform','sDEF');

			
		// get view mode from the flexform data
		$viewmode = $this->pi_getFFValue($this->ffdata,'viewtype','sDEF');
		//$content .= "viewmode: ".$viewmode."<br><br>";

		switch($viewmode) {
			case 'NONE': $content .= "View mode not configured yet.";
			break;
			case 'LIST': $content .= $this->generateListView();
			break;
			case 'SINGLE': $content .= $this->generateSingleView();
			break;
			case 'ORDER': $content .= $this->generateOrderView();
			break;
		}

		return $this->pi_wrapInBaseClass($content);
	}


	/*
	 *   Creation of a marker for the tt_news plugin to allow a pubdb entry getting linked at a news entry
	*/
	function extraItemMarkerProcessor($markerArray,$row,$lConf,$parentObject) {
			
		$spid = $parentObject->conf['pubdb_singlePID'];

		if ($row['tx_pubdb_newslink'] > 0) {
			$params = array( $this->prefixId => array( 'pubid' => $row['tx_pubdb_newslink'], ppid => $GLOBALS['TSFE']->id));
			$publink = $parentObject->pi_linkToPage($row['tx_pubdb_link_title'],$spid,'',$params);
		}
		else
			$publink = "";

		$markerArray['###PUBLINK###'] = '<b>'.$publink.'</b>';
		return $markerArray;

	}

	/*
	 * Get all categories the user is allowed to download files
	*/

	function getAllowedCategories() {
		$allowedCat = array();

		// get front end user groups of the current user
		$usergroups= explode(',',$GLOBALS['TSFE']->fe_user->user['usergroup']);
		//t3lib_utility_Debug::debug($usergroups,'usergroups');

		// get category - web user group relations
		$result=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_pubdb_category_fegroups_mm','');
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))  {
			if (in_array($row['uid_foreign'],$usergroups))
				$allowedCat[$row['uid_local']] = 1;
			else {
				if (!key_exists($row['uid_local'],$allowedCat))
					$allowedCat[$row['uid_local']] = 0;
			}
		}

		// get categories without relation to a fe group
		$result=$GLOBALS['TYPO3_DB']->exec_SELECTquery('uid','tx_pubdb_categories','');
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
			if (!array_key_exists($row['uid'],$allowedCat))
				$allowedCat[$row['uid']] = 1;
		}

		//t3lib_utility_Debug::debug($allowedCat,'allowedcat');

		return $allowedCat;

	}

	
	function getContributorsForPubids($pubids) {
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
	

	
	
	/*
	 * Generates the list view page with a given list of publications
	*/
	function showList($pubs, $parents, $contributors) {

		// get the singlepage pid
		$singlepid = $this->pi_getFFValue($this->ffdata,'singlepid','sOtherSettings');

		// get the orderpage pid
		$orderpid =  $this->pi_getFFValue($this->ffdata,'orderpid','sOtherSettings');

		// load template file
		if (isset($this->conf["templateFile"]))
			$template = $this->cObj->fileResource($this->conf["templateFile"]);
		else
			$template = $this->cObj->fileResource($this->standardTemplate);


		$subpart_browser = $this->cObj->getSubpart($template,"###LIST_BROWSE_TEMPLATE###");
		// -------------------------------- check download permissioin ---------------------

		$allowedCat = $this->getAllowedCategories();

		// get publication to category relations
		$pub2cat = array();
		$result=$GLOBALS['TYPO3_DB']->exec_SELECTquery('uid_local,uid_foreign','tx_pubdb_data_category_mm','');
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))
			$pub2cat[$row['uid_local']][] = $row['uid_foreign'];

		// --------------------------- show page browser ------------------------------
		$pagebrowser = '';
		$start = 0;
		$max = $this->pi_getFFValue($this->ffdata,'resultnum','sDEF');
			
		$end = $max;
		if ($end > sizeof($pubs)) $end = sizeof($pubs);

		if (!isset($this->piVars['pnum'])) $this->piVars['pnum'] = 1;
			
		if (sizeof($pubs) > $max && $max>0) {

			$next = $this->piVars['pnum'] + 1;
			$prev = $this->piVars['pnum'] - 1;
			$last = sizeof($pubs)/$max;

			if (isset($this->piVars['pnum']) && $this->piVars['pnum'] > 1)
				$pagebrowser .= $this->pi_linkTP_keepPIvars('<<',array('pnum' => $prev)).'&nbsp;';

			for ($i=0; $i <=  $last; $i++)  {
				$c = $i+1;
				if ($this->piVars['pnum'] == $c || ( !isset($this->piVars['pnum']) && $c==1) )
					$link = $c;
				else
					$link = $this->pi_linkTP_keepPIvars($c,array('pnum' => $c));
				$pagebrowser .= $link.' ';
			}
			if ($this->piVars['pnum'] < $last)
				$pagebrowser .= '&nbsp;'.$this->pi_linkTP_keepPIvars('>>',array('pnum' => $next));
		}

		// show page browser on the top of list
		$bmarkerARRAY['###LIST_PAGEBROWSER###']=$pagebrowser;
		$content .= $this->cObj->substituteMarkerArray($subpart_browser,$bmarkerARRAY);


		// compute indizes
		if (isset($this->piVars['pnum'])) {

			$start = $max* ( $this->piVars['pnum'] - 1);
			$end = $max* $this->piVars['pnum'];
			if ($end > sizeof($pubs))
				$end = sizeof($pubs);

		}

		// --------------------------- render list of publications ------------------------------------

		if (sizeof($pubs) == 0)
			$content .= "<p>".$this->pi_getLL('list.noentries')."<p>";

		for ($i=$start; $i<$end; $i++) {
			$row = $pubs[$i];

			$p_title = '';
			$p_author = '';
			$p_file = '';
			$p_more = '';
			$p_order = '';
			$num_year = '';
			$p_year = '';
			$p_publisher = '';
			$p_subtitle = '';
			$p_location = '';


			if ($row['number'] != '' && $row['year'] != '')
				$num_year = $row['number'].'/'.$row['year'];

			$download = FALSE;
				
			//t3lib_utility_Debug::debug($pub2cat,'pub2cat');
			if (sizeof($pub2cat[$row['uid']]) > 0 ) {
				foreach ($pub2cat[$row['uid']] as $c) {
					if ($allowedCat[$c] === 1) {
						$download = TRUE;
						break;
					}
				}
			}
			// check download for foreign data
			if (isset($row['localCat']) && $allowedCat[$row['localCat']])
				$download = TRUE;

				

			$fileTypeLink = "filetype.".$row['openFileType'];


			// insert title
			if ($row['openFile'] != '' || $row['file'] != '')
				$p_file .= '<br/><b>'.$this->pi_getLL('download_title').'</b><br/>';


			// free downloadable files
			if ($row['openFile']!='') {
				$files = explode(',',$row['openFile']);
				foreach ($files as $f) {
					if (isset($row['isForeign']))
						$p_file .= '<a href=" 	'.$this->conf['foreignDB.'][$row['localCat'].'.']['filePath'].'/'.$f.'">'.$f.'</a><br/>';
					else
						$p_file .= '<a target="_blank" href="fileadmin/user_upload/tx_pubdb/'.$f.'">'.$f.'</a><br/>';
				}
			}

			$fileTypeLink = "filetype.".$row['fileType'];
		 if ($row['file']!='') {
		 	$files = explode(',',$row['file']);
		 	foreach ($files as $f) {
		 		if ($download) {
		 			if (isset($row['isForeign']))
		 				$p_file .= '<a href=" '.$this->conf['foreignDB.'][$row['localCat'].'.']['filePath'].'/'.$f.'">'.$f.'</a><br/>';
		 			else
		 				$p_file .= '<a target="_blank" href="fileadmin/user_upload/tx_pubdb/'.$f.'">'.$f.'</a><br/>';
		 		}
		 		else
		 			$p_file .= $f.'<br/>';
				}
		 }      //  else $p_file = $this->pi_getLL('download');




		 if (isset($row['isForeign'])) {
		 	$params = array( $this->prefixId => array( 'pubid' => $row['uid'], ppid => $GLOBALS['TSFE']->id,'foreign'=>1,'localCat'=>$row['localCat']));
		 } else
		 	$params = array( $this->prefixId => array( 'pubid' => $row['uid'], ppid => $GLOBALS['TSFE']->id));

			$content .= $this->renderListItem($row, $params, $parents[$row['parent_pubid']], $contributors);

		}
		// show page browser on the bottom of list
		$content .= $this->cObj->substituteMarkerArray($subpart_browser,$bmarkerARRAY);
		return($content);



	}
	
	
	function generateSearchResultList() {
		
		if ($this->piVars['search'] != '') {
				
			// if search string given search in the given field, else take everything
			switch ($this->piVars['field']) {
				case 'title': $wherecl = 'tx_pubdb_data.title LIKE "%'.$this->piVars['search'].'%" AND tx_pubdb_data.deleted="0"'; break;
				case 'author': $wherecl = 'tx_pubdb_data.author LIKE "%'.$this->piVars['search'].'%" AND tx_pubdb_data.deleted="0"'; break;
				case 'year': $wherecl = 'tx_pubdb_data.year LIKE "%'.$this->piVars['search'].'%" AND tx_pubdb_data.deleted="0"'; break;
				case 'number': $wherecl = 'tx_pubdb_data.number LIKE "%'.$this->piVars['search'].'%" AND tx_pubdb_data.deleted="0"'; break;
			}
		} else {
			$wherecl = 'tx_pubdb_data.deleted="0"';
		
		}
			
		// Add NOT hidden clause
		$wherecl .= ' AND tx_pubdb_data.hidden="0"';
		
		// choose category from dropdownlist (0 for all)
		if ($this->piVars['category']=='0')
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_pubdb_data',$wherecl);
		else {
			$mywherecl = 'AND '.$wherecl.' AND tx_pubdb_data_category_mm.uid_foreign="'.$this->piVars['category'].'"';
			$result = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('tx_pubdb_data.*','tx_pubdb_data','tx_pubdb_data_category_mm','tx_pubdb_categories',$mywherecl);
		}
		
		// check also foreign table if set
		if (isset($this->conf["foreignDB."])) {
		
			foreach ($this->conf['foreignDB.'] as $value ) {
				mysql_pconnect($value['dbHost'], $value['dbUser'], $value['dbPass']);
				mysql_select_db( $value['dbName']);
				if ( $this->piVars['category'] == $value['localCatPID'] || $this->piVars['category'] == 0) {
					if ($this->piVars['category'] == 0) {
						$resultForeign = mysql_query('select * FROM tx_pubdb_data WHERE '.$wherecl);
					} else {
						$mywherecl = $wherecl.' AND tx_pubdb_data_category_mm.uid_foreign="'.$value['catPID'].'"';
						$resultForeign = mysql_query('select * FROM tx_pubdb_data JOIN tx_pubdb_data_category_mm ON tx_pubdb_data.uid=tx_pubdb_data_category_mm.uid_local WHERE '.$mywherecl);
					}
		
					if (mysql_num_rows($resultForeign) > 0) {
						while($row = mysql_fetch_assoc($resultForeign))  {
							$row['isForeign'] = 1;
							$row['localCat'] = $value['localCatPID'];
							$publications[] = $row;
							$pubids[] = $row['uid'];
						}
						$counter +=  mysql_num_rows($resultForeign);
					}
		
					// fetch contributors TODO
					//$contributors = $this->getContributorsForPubids(implode(',',$pubids));
		
				}
				mysql_close();
			}
		}
		
		//$content .= 'wherecl: '.$wherecl.'<br>';
		$counter += $GLOBALS['TYPO3_DB']->sql_num_rows($result);
		$content .= $counter.'&nbsp;'.$this->pi_getLL('search.entriesfound').'<br />';
		
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) > 0) {
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))
				$publications[] = $row;
				$pubids[] = $row['uid'];
		}
			
		
		$contributors = $this->getContributorsForPubids(implode(',',$pubids));
		
		if ($counter > 0)
			$content .= $this->showList($publications);
		
		return $content;
	}

	/**
	 * Generates the list of publications to be shown
	 *
	 * @return	String   The lists content		...
	 */
	function generateListView() {


		$counter = 0;

		if ($this->showSearchForm == 1)
			$content .= $this->generateSearchView();

		// check if request is from search form
		if (isset($this->piVars['search'])) {

			$content .= $this->generateSearchResulList();

		} 
		// generate list from BE page settings otherwise
		elseif ($this->pi_getFFValue($this->ffdata,'category','sDEF') != -1) {

			// get the categories and the link relation from the flexfrom data
			$catstring = $this->pi_getFFValue($this->ffdata,'category','sDEF');

			// the AND or OR relation for categorie selection in list
			$linkrel = $this->pi_getFFValue($this->ffdata,'catrel','sDEF');

			// generate the WHERE statement
			$wherecl = 'tx_pubdb_data.deleted=0';
			$whereStr =  $wherecl.' AND (tx_pubdb_data_category_mm.uid_foreign=';
			$whereStr .= str_replace(',',' OR'.' tx_pubdb_data_category_mm.uid_foreign=',$catstring);
			$whereStr .=')';

			// GROUP BY
			$groupByStr = 'tx_pubdb_data.uid';

			// SELECT
			$selectStr = 'tx_pubdb_data.*, GROUP_CONCAT(tx_pubdb_categories.uid SEPARATOR ",") AS categories';
		
			// sort clause clause
			switch ($this->pi_getFFValue($this->ffdata,'sortmode','sDEF')) {
				case 'YEAR': $orderby = 'year';
				break;
				case 'TITLE': $orderby = 'title';
				break;
				case 'NUMBER': $orderby = 'number+0';
				break;
				case 'INTERNALNUMBER': $orderby = 'internalNumber+0';
				break;
				default:       $orderby = 'year';
			}

			// ORDER BY clause
			$orderby .= ' '.$this->pi_getFFValue($this->ffdata,'order','sDEF');

			if ($catstring != '') {


				$result = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query (
						$selectStr,
						'tx_pubdb_data',
						'tx_pubdb_data_category_mm',
						'tx_pubdb_categories',
						'AND '.$whereStr,
						$groupByStr,
						$orderby,
						$limit='');
					
			} else
				$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_pubdb_data',$wherecl,'',$orderby);

		
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
				$publications[] = $row;

				$pubids[] = $row['uid'];
				if (isset($row['parent_pubid']) && $row['parent_pubid'] !== '0') {
					$parentids[] = $row['parent_pubid'];
				}
			}
			$result =  $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_pubdb_data','uid IN ('.implode(',',$parentids).')');
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
				$parents[$row['uid']] = $row;
			}
				
			// fetch also the contributors
			$contributors = $this->getContributorsForPubids(implode(',',$pubids)); 
				
			// For the categories OR link, we are ready. For the AND link, the publications with all selected categories
			// have to get selected.

			if ($linkrel == 'AND' && sizeof($publications) > 0) {
					
				// get the selected categories array
				$list_cats = explode(',', $catstring);
				$newpubs=array();
				foreach ($publications as $p) {
					// get the publication categories
					$p_cats = explode(',',$p['categories']);

					// check, if publication has all selected categories
					if (sizeof(array_intersect($list_cats,$p_cats)) == sizeof($list_cats))                              $newpubs[] = $p;
				}
				$publications = $newpubs;
			}


			// check also foreign table if set
			if (isset($this->conf["foreignDB."])) {

				foreach ($this->conf['foreignDB.'] as $value ) {
					mysql_pconnect($value['dbHost'], $value['dbUser'], $value['dbPass']);
					mysql_select_db( $value['dbName']);
					//t3lib_div::debug($value);
					if ( in_array($value['localCatPID'],explode(',',$catstring)) || $catstring == '') {
						if ($catstring == '') {
							$resultForeign = mysql_query('select * FROM tx_pubdb_data WHERE '.$wherecl);
						} else {
							$mywherecl = $wherecl.' AND tx_pubdb_data_category_mm.uid_foreign="'.$value['catPID'].'"';
							$resultForeign = mysql_query('select * FROM tx_pubdb_data,tx_pubdb_data_category_mm WHERE '.$mywherecl);
						}

						if (mysql_num_rows($resultForeign) > 0) {
							while($row = mysql_fetch_assoc($resultForeign)) {
								$row['isForeign'] = 1;
								$publications[] = $row;
							}
							$counter +=  mysql_num_rows($resultForeign);
						}
					}
					mysql_close();
				}
			}

			$content .= $this->showList($publications, $parents, $contributors);
		}
		return($content);
	}

	/**
	 * Generates a detailed view of a single publication
	 *
	 * @return	String      The content
	 */
	function generateSingleView() {

		$params = t3lib_div::_GET($this->prefixId);

		if (array_key_exists('pubid',$params))
			$wherecl = 'uid='.$params['pubid'];
		elseif (array_key_exists('doi',$params)) {

			// look only for the last part of the key
		 // $wherecl = "doi like '%.".$params['doi']."'";

			// changed to pubid instead of doi part
		 $wherecl = 'uid='.$params['doi'];

		}

		if (array_key_exists('ppid',$params))
			$returnlink =  $this->pi_linkToPage('<< '.$this->pi_getLL('back'),$params['ppid']);
		else
			$returnlink = "";

			
		$orderpid =  $this->pi_getFFValue($this->ffdata,'orderpid','sOtherSettings');

		// get data from foreign database if necessary
		if (isset($params['foreign'])) {
			foreach ($this->conf['foreignDB.'] as $value )  {
				if ($value['localCatPID'] == $params['localCat'])
					break;
			}
			mysql_pconnect($value['dbHost'], $value['dbUser'], $value['dbPass']);
			mysql_select_db( $value['dbName']);
			$resultForeign = mysql_query('select * FROM tx_pubdb_data WHERE '.$wherecl);
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($resultForeign) == 0) {
				$singlepage .=  $this->pi_getLL('error.noentry')." ".$params['doi']."!</br>";
				return $singlepage;
			}


			$pub = mysql_fetch_assoc($resultForeign);

			if ($pub['pubtype'] === 'journal' || $pub['pubtype'] === 'book' || $pub['pubtype'] === 'conference_proceedings') {
				$resultChildrenForeign = mysql_query('select * FROM tx_pubdb_data WHERE parent_pubid='.$pub['uid']);
				while ($row = mysql_fetch_assoc($resultChildrenForeign)) {
					$childPubs[$row['uid']] = $row;
				}
			}
				
			if (isset($pub['parent_pubid']) && $pub['parent_pubid'] !== '0') {
				$resultParentForeign = mysql_query('select * FROM tx_pubdb_data WHERE uid='.$pub['parent_pubid']);
				$parentPub = mysql_fetch_assoc($resultParentForeign);
			}

			mysql_close();
		} else {

			// search in local database
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_pubdb_data',$wherecl);

			// return, if nothing was found
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) == 0) {
				$singlepage .=  $this->pi_getLL('error.noentry')." ".$params['doi']."!</br>";
				return $singlepage;
			}

			$pub = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

			if ($pub['pubtype'] === 'journal' || $pub['pubtype'] === 'book' || $pub['pubtype'] === 'conference_proceedings') {
				$resultChildren = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_pubdb_data','parent_pubid='.$pub['uid']);
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resultChildren)) {
					$childPubs[$row['uid']] = $row;
				}
			}
				
			if (isset($pub['parent_pubid']) && $pub['parent_pubid'] !== '0') {
				$resultParent = mysql_query('select * FROM tx_pubdb_data WHERE uid='.$pub['parent_pubid']);
				$parentPub = mysql_fetch_assoc($resultParent);
			}
		}

		// check permission for download
		$download = FALSE;
		$allowedCat = $this->getAllowedCategories();

		// get publication to category relations
		$pub2cat = array();
		$pubid = $pub['uid'];
		$result=$GLOBALS['TYPO3_DB']->exec_SELECTquery('uid_local,uid_foreign','tx_pubdb_data_category_mm','');
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))
			$pub2cat[$row['uid_local']][] = $row['uid_foreign'];

		if (sizeof($pub2cat[$pubid]) > 0 ) {
			foreach ($pub2cat[$pubid] as $c) {
				if ($allowedCat[$c] === 1) {
					$download = TRUE;
					break;
				}
			}
		}
			

		// check download for foreign data
		if (isset($params['localCat']) && $allowedCat[$params['localCat']])
			$download = TRUE;

		// insert title
		if ($pub['openFile'] != '' || $pub['file'] != '')
			$p_file .= '<b>'.$this->pi_getLL('download_title').'</b><br/>';

		// free downloadable files
		$fileTypeLink = "filetype.".$pub['openFileType'];
		if ($pub['openFile'] != '') {

			$files = explode(',',$pub['openFile']);
			foreach ($files as $f) {
				if (isset($params['foreign']))
					$p_file .= '<a href=" '.$this->conf['foreignDB.'][$params['localCat'].'.']['filePath'].'/'.$f.'">'.$f.'</a><br/>';
				else
					$p_file .= '<a  target="_blank" href="fileadmin/user_upload/tx_pubdb/'.$f.'">'.$f.'</a><br/>';
			}
		}
			
		$fileTypeLink = "filetype.".$pub['fileType'];
		if ($pub['file'] != '') {
			$files = explode(',',$pub['file']);
			foreach ($files as $f) {

				if ($download) {
					if (isset($params['foreign']))
						$p_file .= '<a href=" '.$this->conf['foreignDB.'][$params['localCat'].'.']['filePath'].'/'.$f.'">'.$f.'</a><br/>';
					else
						$p_file .= '<a  target="_blank" href="fileadmin/user_upload/tx_pubdb/'.$f.'">'.$f.'</a><br/>';
				}
				else
					$p_file .= $f.' ('.$this->pi_getLL('hint.loginrequiredfordownload').')<br/> ';
			}
		}
			
		$p_order = '';
		$p_info = '';

		if ($pub['hashardcopy'] == 1) {
			$params = array( $this->prefixId => array( 'pubid' => $pub['uid'], ppid => $GLOBALS['TSFE']->id));
			$p_order = $this->pi_linkToPage($this->pi_getLL('order'),$orderpid,'',$params);
		}

		if ($pub['series'] != '') $p_info .= $this->pi_getLL('list.inseries').':&nbsp;'.$pub['series'].',&nbsp;';
		if ($pub['number'] != '') $p_info .= $pub['number'];
		if ($pub['year'] != '' && $pub['year'] > 0) $p_info .= ', '.$pub['year'];
		if ($pub['publisher'] != '') $p_info .= ', '.$pub['publisher'];
		if ($pub['location'] != '') $p_info.= ', '.$pub['location'];
		if ($pub['pages'] != '') $p_info.=',&nbsp;'.$pub['pages'].'&nbsp;'.$this->pi_getLL('list.pages');
		if ($pub['isbn'] != '') $p_info.=',&nbsp;'.$this->pi_getLL('list.isbn').': '.$pub['isbn'];
		if ($pub['isbn2'] != '') $p_info.=',&nbsp;'.$this->pi_getLL('list.isbn2').': '.$pub['isbn2'];


		$p_info = trim($p_info,", ");

		// load tempalte file
		if (isset($this->conf["templateFile"]))
			$template = $this->cObj->fileResource($this->conf["templateFile"]);
		else
			$template = $this->cObj->fileResource($this->standardTemplate);

		// if journal, render the content
		if ($pub['pubtype'] === 'journal') {

			foreach ($childPubs as $childPub) {
				if (isset($childPub['isForeign'])) {
					$params = array( $this->prefixId => array( 'pubid' => $childPub['uid'], ppid => $GLOBALS['TSFE']->id,'foreign'=>1,'localCat'=>$childPub['localCat']));
				} else
					$params = array( $this->prefixId => array( 'pubid' => $childPub['uid'], ppid => $GLOBALS['TSFE']->id));

				$p_content .= $this->renderListItem($childPub, $params, $pub);
			}

		}

		// get subpart
		$subpart = $this->cObj->getSubpart($template,"###SINGLE_TEMPLATE###");


		// get content and define substitution
		$markerARRAY['###SINGLE_DOI###']=$pub['doi'];
		$markerARRAY['###SINGLE_TITLE###']=$pub['title'];
		$markerARRAY['###SINGLE_SUBTITLE###']=$pub['subtitle'];

		$authors=$pub['author'];
		if ($pub['coauthors']!='')
			$authors .= '; '.$pub['coauthors'];
		$markerARRAY['###SINGLE_AUTHOR###']=$authors;

		$markerARRAY['###SINGLE_INFO###']=$p_info;

		$markerARRAY['###SINGLE_CONTENT###'] = $p_content;


		$markerARRAY['###SINGLE_FILE###']=$p_file;
		$markerARRAY['###SINGLE_ABSTRACT###']=$pub['abstract'];
		$markerARRAY['###SINGLE_RETURNLINK###']=$returnlink;
		$markerARRAY['###SINGLE_ORDERLINK###'] = $p_order;

		// substitute
		$singlepage .= $this->cObj->substituteMarkerArray($subpart,$markerARRAY);

		return $singlepage;
	}

	/**
	 * Generates a detailed view of a single publication
	 *
	 * @return	String      The content
	 */
	function generateSearchView() {
		$url=$this->pi_getPageLink($GLOBALS['TSFE']->id);

		// load template file
		if (isset($this->conf["templateFile"]))
			$template = $this->cObj->fileResource($this->conf["templateFile"]);
		else
			$template = $this->cObj->fileResource($this->standardTemplate);

			
		// get subpart
		$subpart = $this->cObj->getSubpart($template,"###SEARCH_TEMPLATE###");

		// set marker replacements
		$markerARRAY['###SEARCH_TITLE###'] = $this->pi_getLL('search');
		$markerARRAY['###SEARCH_BEGIN_FORM###']= '<form method="POST" action="'.$url.'">';
		$markerARRAY['###SEARCH_SEARCH_FOR_DESC###'] = $this->pi_getLL('search.searchfor');
		$markerARRAY['###SEARCH_SEARCH_IN_DESC###']  = $this->pi_getLL('search.infield');
		$markerARRAY['###SEARCH_SEARCH_CAT_DESC###']  = $this->pi_getLL('search.incategory');

		$markerARRAY['###SEARCH_SEARCH_FOR_FIELD###'] = '<input name="'.$this->prefixId.'[search]" type="text" />';
		$markerARRAY['###SEARCH_SEARCH_IN_FIELD###'] = '<select name="'.$this->prefixId.'[field]" />';
		$markerARRAY['###SEARCH_SEARCH_IN_FIELD###'].='<option value="title">'.$this->pi_getLL('search.titlefield').'</option>';
		$markerARRAY['###SEARCH_SEARCH_IN_FIELD###'].='<option value="author">'.$this->pi_getLL('search.authorfield').'</option>';
		$markerARRAY['###SEARCH_SEARCH_IN_FIELD###'].='<option value="year">'.$this->pi_getLL('search.yearfield').'</option>';
		$markerARRAY['###SEARCH_SEARCH_IN_FIELD###'].='<option value="number">'.$this->pi_getLL('search.numberfield').'</option>';
		$markerARRAY['###SEARCH_SEARCH_IN_FIELD###'].='</select>';

		$markerARRAY['###SEARCH_SEARCH_CAT_FIELD###'] = '<select name="'.$this->prefixId.'[category]" />'.
				'<option value="0">'.$this->pi_getLL('search.allcat').'</option>';

		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,name','tx_pubdb_categories','name!="" AND deleted="0"');
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
			$markerARRAY['###SEARCH_SEARCH_CAT_FIELD###'].='<option value="'.$row['uid'].'">'.$row['name'].'</option>';
		}

		$markerARRAY['###SEARCH_SUBMIT###'] = '<input type="submit" value="'.$this->pi_getLL('search.submit').'"/>';
		$markerARRAY['###SEARCH_END_FORM###'] = '</form>';

		//subsitute
		$content .= $this->cObj->substituteMarkerArray($subpart,$markerARRAY);


			
			
		return $content;

	}



	function generateOrderView() {


		// check if ready for sending
		if (isset($this->piVars['ordersent']))  {
			if ($this->piVars['name'] != '' && $this->piVars['street'] != '' && $this->piVars['city'] != '' && $this->piVars['zipcode'] != '') {
				$message .= 'Bestellung'.chr(10);
				$message .= 'Bestellung von: '.chr(10);

				// get the publication
				$result=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_pubdb_data','uid='.$this->piVars['pub']);
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))  {
					$message .= $row['author'].chr(10).$row['title'];
					if ($row['subtitle'] != '') $message.=', '.$row['subtitle'];
					if ($row['number'] != '') $message.=', '.$row['number'];
					if ($row['location'] != '') $message.=', '.$row['location'];
					if ($row['year'] != '') $message.=', '.$row['year'];
					$message.=chr(10).'Preis: '.$row['price'];
					if ($row['reducedprice'] != '')     $message.=chr(10).'Mitgliedspreis: '.$row['reducedprice'];
				}

				$message .= chr(10).chr(10).'Besteller ist Mitglied: ';
				if ($ismember == 1)
					$message .= "Ja";
				else
					$message .= "Nein";


				$message .= chr(10).chr(10).'An: '.$this->piVars['name'].chr(10);
				$message .= 'Firma: '.$this->piVars['company'].chr(10);
				$message .= 'Adresse: '.$this->piVars['street'].chr(10).$this->piVars['zipcode'].' '.$this->piVars['city'].chr(10).$this->piVars['country'].chr(10);
				$message .= 'E-mail: '.$this->piVars['email'];
				$recipient = $this->pi_getFFValue($this->ffdata,'orderemail','sOtherSettings');
				$res = $this->cObj->sendNotifyEmail($message, $recipient, '', 'webadmin@seth.asc.tuwien.ac.at', 'Weborder', '');
				$content .= $this->pi_getLL('order.sentmessage').'<br /><br />';
				return $content;
			} else $ordercomplete = false;
		}



		$url=$this->pi_getPageLink($GLOBALS['TSFE']->id);

		// check if coming from list page
		if (isset($this->piVars['pubid'])) {

			$pubid = $this->piVars['pubid'];



			// get front end user groups of the current user
			//check if it is asim website, can be ignored
			$ismember = 0;
			if (isset($GLOBALS['TSFE']->fe_user->user['pers_vn'])) {
				$name= $GLOBALS['TSFE']->fe_user->user['pers_titel'].' '.$GLOBALS['TSFE']->fe_user->user['pers_vn'].' '.$GLOBALS['TSFE']->fe_user->user['pers_fn'];
				$company = $GLOBALS['TSFE']->fe_user->user['pers_firma'];
				$zipcode=$GLOBALS['TSFE']->fe_user->user['pers_plz'];
				$city=$GLOBALS['TSFE']->fe_user->user['pers_ort'];
				$street=$GLOBALS['TSFE']->fe_user->user['pers_str'];
				$country = $GLOBALS['TSFE']->fe_user->user['pers_land'];
				$email = $GLOBALS['TSFE']->fe_user->user['pers_email1'];
				$ismember = 1;
			} else {
				$name= $GLOBALS['TSFE']->fe_user->user['name'];
				$company = $GLOBALS['TSFE']->fe_user->user['company'];
				$zipcode=$GLOBALS['TSFE']->fe_user->user['zip'];
				$city=$GLOBALS['TSFE']->fe_user->user['city'];
				$street=$GLOBALS['TSFE']->fe_user->user['address'];
				$country = $GLOBALS['TSFE']->fe_user->user['country'];
				$email = $GLOBALS['TSFE']->fe_user->user['email'];
					
			}


		} else {
			// request from order page
			$pubid = $this->piVars['pub'];


			$name=  $this->piVars['name'];
			$company =  $this->piVars['company'];
			$zipcode= $this->piVars['zipcode'];
			$city= $this->piVars['city'];
			$street= $this->piVars['street'];
			$country = $this->piVars['country'];
			$email =  $this->piVars['email'];
			$content .= '<span class="tx_pubdb-order-error">'.$this->pi_getLL('order.formerrormessage').'</span><br /><br />';
		}

		// get the publication
		$result=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_pubdb_data','uid='.$pubid);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))  {
			$pub = $row['author'].'<br><b>'.$row['title'].'</b>';
			if ($row['subtitle'] != '') $pub.=', '.$row['subtitle'];
			if ($row['number'] != '') $pub.=', '.$row['number'];
			if ($row['location'] != '') $pub.=', '.$row['location'];
			if ($row['year'] != '') $pub.=', '.$row['year'];
			$price = $row['price'];
			if ($row['reducedprice'] != '')
				$reducedprice = $row['reducedprice'];
		}


		// load template file
		if (isset($this->conf["templateFile"]))
			$template = $this->cObj->fileResource($this->conf["templateFile"]);
		else
			$template = $this->cObj->fileResource($this->standardTemplate);


		// get subpart
		$subpart = $this->cObj->getSubpart($template,"###ORDER_TEMPLATE###");
			
		$markerARRAY['###ORDER_TEXT1###'] = $this->pi_getLL('order.text1').'<br /><br />'.$pub.'<br /><br />'.$this->pi_getLL('order.text2').'<br /><br />'.$this->pi_getLL('order.price').': <b>'.$price.'</b><br />'.
				$this->pi_getLL('order.reducedprice').': <b>'.$reducedprice.'</b><br />';
		$markerARRAY['###ORDER_BEGIN_FORM###']= '<form method="POST" action="'.$url.'">';
		$markerARRAY['###ORDER_NAME_DESC###'] = $this->pi_getLL('order.name');
		$markerARRAY['###ORDER_NAME###'] = '<input size="50" name="'.$this->prefixId.'[name]" type="text" value="'.$name.'"/> *';

		$markerARRAY['###ORDER_NAME###'] .= '<input size="50" name="'.$this->prefixId.'[pub]" type="hidden" value="'.$pubid.'" />';
		$markerARRAY['###ORDER_NAME###'] .= '<input size="50" name="'.$this->prefixId.'[ordersent]" type="hidden" value="1" />';
		$markerARRAY['###ORDER_COMPANY_DESC###']  = $this->pi_getLL('order.company');
		$markerARRAY['###ORDER_COMPANY###'] = '<input size="50" name="'.$this->prefixId.'[company]" type="text" value="'.$company.'"/>';
		$markerARRAY['###ORDER_STREET_DESC###']  = $this->pi_getLL('order.street');
		$markerARRAY['###ORDER_STREET###'] = '<input size="50" name="'.$this->prefixId.'[street]" type="text" value="'.$street.'"/> *';
		$markerARRAY['###ORDER_CODE_DESC###']  = $this->pi_getLL('order.zipcode');
		$markerARRAY['###ORDER_CODE###'] = '<input size="50" name="'.$this->prefixId.'[zipcode]" type="text" value="'.$zipcode.'"/> *';
		$markerARRAY['###ORDER_CITY_DESC###']  = $this->pi_getLL('order.city');
		$markerARRAY['###ORDER_CITY###'] = '<input size="50" name="'.$this->prefixId.'[city]" type="text" value="'.$city.'"/> *';
		$markerARRAY['###ORDER_COUNTRY_DESC###']  = $this->pi_getLL('order.country');
		$markerARRAY['###ORDER_COUNTRY###'] = '<input size="50" name="'.$this->prefixId.'[country]" type="text" value="'.$country.'"/>';
		$markerARRAY['###ORDER_EMAIL_DESC###']  = $this->pi_getLL('order.email');
		$markerARRAY['###ORDER_EMAIL###'] = '<input size="50" name="'.$this->prefixId.'[email]" type="text" value="'.$email.'"/>';

		$markerARRAY['###ORDER_SUBMIT###'] = '<input type="submit" value="'.$this->pi_getLL('order.submit').'"/>';
		$markerARRAY['###ORDER_END_FORM###'] = '</form>';
		$markerARRAY['###ORDER_REQUIRED_HINT###'] = $this->pi_getLL('order.required');

		//subsitute
		$content .= $this->cObj->substituteMarkerArray($subpart,$markerARRAY);

			
		return $content;

	}


	function renderListItem($row, $params, $parent=NULL, $contributors=NULL) {

		//t3lib_utility_Debug::debug($parent,'parent');

		if ($row['abstract'] != '' || $row['pubtype'] === 'journal') {
			$p_more = $this->pi_linkToPage($this->pi_getLL('more'),$this->singlepageID,'',$params);
		}

		if ($row['year'] != '' && $row['year'] > 0) $p_year=$row['year'];
		if (isset($parent['year'])) $p_year= $parent['year'];

		if ($row['hashardcopy'] == 1)
			$p_order = $this->pi_linkToPage($this->pi_getLL('order'),$orderpid,'',$params);

		if ($row['subtitle'] != '') $p_subtitle = $row['subtitle'];
		if ($row['publisher'] != '') $p_publisher = $row['publisher'];
		if ($row['location'] != '') $p_location = $row['location'];

		if ($row['number'] != '') $p_number = $row['number'];
		if (isset($parent['number'])) $p_number = $parent['number'];

		if ($row['issue'] != '') $p_issue = $row['issue'];
		if (isset($parent['issue'])) $p_issue = $parent['issue'];

		if ($row['isbn'] != '') $p_isbn = $this->pi_getLL('list.isbn').':&nbsp;'.$row['isbn'];
		if ($row['pages'] != '') $p_pages=$row['pages'];
		if ($row['doi'] != '') $p_doi = 'doi:'.$row['doi'];
			
		if ($row['edition'] != '') $p_edition = $this->addOrdinalNumberSuffix($row['edition']);

		/* Author policy:
		 * 1) check contributors
		 * 2) check coauthor list
		 * 3) check author/editor field
		 */ 
		$conts  = $contributors[$row['uid']];
		if (sizeof($conts) < 1 && isset($row['coauthors']) && strlen($row['coauthors']) > 1) $conts = tx_pubdb_utils::parseAuthors($row['coauthors']);
		if (sizeof($conts) < 1 && isset($row['author']) && strlen($row['author']) > 1) $conts[] = tx_pubdb_utils::parseFullname($row['author']);
		
		$author = $this->createContributorString($conts);
		
		$editors = $this->createContributorString($conts,'editor');

		if ($p_file!='' || $p_order!='') $p_order.='<br />';

		if (isset($parent)) {
			$p_parent_title = $parent['title'];
			$p_parent_abbrev_title = $parent['abbrev_title'];
		} else {
			$p_parent_title = '';
			$p_parent_abbrev_title = '';
		}

		// get content and define substitution
		$markerARRAY['###LIST_TITLE###']=$row['title'];
		$markerARRAY['###LIST_SUBTITLE###']=$p_subtitle;
		$markerARRAY['###LIST_AUTHOR###']=$author;
		$markerARRAY['###LIST_FILE###']=$p_file;
		$markerARRAY['###LIST_MORE###']=$p_more;
		$markerARRAY['###LIST_ORDER###']=$p_order;
		$markerARRAY['###LIST_YEAR###']=$p_year;
		$markerARRAY['###LIST_ISBN###']=$p_isbn;
		$markerARRAY['###LIST_PUBLISHER###']=$p_publisher;
		$markerARRAY['###LIST_LOCATION###']=$p_location;
		$markerARRAY['###LIST_SERIES###']=$p_series;
		$markerARRAY['###LIST_NUMBER###']=$p_number;
		$markerARRAY['###LIST_ISSUE###']=$p_issue;
		$markerARRAY['###LIST_PAGES###']=$p_pages;
		$markerARRAY['###LIST_PARENT_TITLE###']=$p_parent_title;
		$markerARRAY['###LIST_PARENT_ABBR###']=$parent_abbrev_title;
		$markerARRAY['###LIST_DOI###']=$p_doi;
		$markerARRAY['###LIST_EDITION###']=$p_edition.' ed';

		// load template file
		if (isset($this->conf["templateFile"]))
			$template = $this->cObj->fileResource($this->conf["templateFile"]);
		else
			$template = $this->cObj->fileResource($this->standardTemplate);


		// get subpart
		if($row['pubtype'] === 'journal_article')
			$subpart = $this->cObj->getSubpart($template,"###LIST_ITEM_TEMPLATE_JOURNAL_ARTICLE###");
		else if ($row['pubtype'] === 'book')
			$subpart = $this->cObj->getSubpart($template,"###LIST_ITEM_TEMPLATE_BOOK###");
		else

			$subpart = $this->cObj->getSubpart($template,"###LIST_ITEM_TEMPLATE###");

		// substitute
		$item = $this->cObj->substituteMarkerArray($subpart,$markerARRAY);
		return $this->postProcessItem($item);
			
	}


	function createContributorString($c, $role=NULL) {
		//t3lib_utility_Debug::debug($c,'contributors');
		for ($i=0; $i < sizeof($c); $i++) {
					
			// if a role is given, take only the coresonding once
			if (isset($role) && isset($c['role']) && $c['role'] !== $role)
				continue;

			$surname = $c[$i]['surname'];
			if (isset($c[$i]['suffix']) && strlen($c[$i]['suffix']) > 1) $surname .= $c[$i]['suffix'];
			$givenname = '';
			$givennames = explode(' ',trim($c[$i]['given_name']));
			foreach ($givennames as $n) {
				$givenname .= $n[0].'. ';
			}
			$givenname = trim($givenname);
			
			if ($i === 0) {
				$res .= $surname.', '.$givenname;
			} else if ($i === (sizeof($c) -1)) {
				$res .= ' and '.$givenname.' '.$surname;
			} else {
				$res .= ', '.$givenname.' '.$surname;
			}
			
		}
		return $res;
	}

	function addOrdinalNumberSuffix($num) {
		if (!in_array(($num % 100),array(11,12,13))){
			switch ($num % 10) {
				// Handle 1st, 2nd, 3rd
				case 1:  return $num.'st';
				case 2:  return $num.'nd';
				case 3:  return $num.'rd';
			}
		}
		return $num.'th';
	}


	function postProcessItem($item) {
		
	
  		// remove empty parantheis
		$item = str_replace('()', '', trim($item));
			
		// remove empty html tags, eg.<strong></strong> or <i></i> for able to detect the following patterns
		$item = preg_replace('/<[a-z]+><\/[a-z]+>/','',$item);
	
		// remove repeating commas and dots
		$item = preg_replace('/\,(\s*[\,\.]+)+/',',',$item);
		
		// remove empy partes ending with :	
		$item = preg_replace('/(\,\s)+\:/',':',$item);

		// remove empty comma parts following a dot			
		$item = preg_replace('/\.(\s+[\,\.\:\;])+/','.',$item);
	
		// remove unnecessary spaces
		$item = preg_replace('/\s+\,/',',',$item);

		// make sure we end with a dot and not with any other character
		$item = preg_replace('/([\,\:]\s*<\/span>)/','.</span>',$item);
		
		// make sure we do not start with a ., or :
		$item = preg_replace('/>\s*[\,\.\:\;]+/','>',$item);

		return $item;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pubdb/pi1/class.tx_pubdb_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pubdb/pi1/class.tx_pubdb_pi1.php']);
}

?>
