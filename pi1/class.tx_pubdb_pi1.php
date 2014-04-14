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
/**
 * Plugin 'Pubdb list' for the 'pubdb' extension.
 *
 * @author	Johannes Kropf <johannes@kropf.at>
*/


require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(PATH_typo3conf.'ext/pubdb/Classes/class.tx_pubdb_toxml.php');
require_once(PATH_typo3conf.'ext/pubdb/Classes/class.tx_pubdb_utils.php');
require_once(PATH_typo3conf.'ext/pubdb/pi1/class.tx_pubdb_dbaccess.php');

class tx_pubdb_pi1 extends tslib_pibase {
	public $scriptRelPath = 'pi1/class.tx_pubdb_pi1.php';	// Path to this script relative to the extension dir.
	public $extKey = 'pubdb';	// The extension key.
	public $pi_checkCHash = TRUE;
	public $prefixId = 'tx_pubdb_pi1'; // Same as class name
	
	private $ffdata;
	private $showSearchForm = 0;
	private $standardTemplate = 'typo3conf/ext/pubdb/pi1/template.tmpl';
	private $db;
	private $usergroups = '';
	
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The		content that is displayed on the website
	 */
	public function main($content, $conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
	    
		$this->pi_initPIflexForm();

		// load the flexform data array
		$this->ffdata = $this->cObj->data['pi_flexform'];

		$this->showSearchForm = $this->pi_getFFValue($this->ffdata, 'showsearchform', 'sDEF');
		$this->db = t3lib_div::makeInstance('pubdbAccess');
			
		$this->usergroups = $GLOBALS['TSFE']->fe_user->user['usergroup'];
		//debug($this->usergroups,"usergroups");
		
		// get view mode from the flexform data
		$viewmode = $this->pi_getFFValue($this->ffdata, 'viewtype', 'sDEF');

		switch($viewmode) {
			case 'NONE': $content .= 'View mode not configured yet.';
			break;
			case 'LIST': $content .= $this->generateListView();
			break;
			case 'SINGLE': $content .= $this->generateSingleView();
			break;
			case 'ORDER': $content .= $this->generateOrderView();
			break;
			case 'REMOTE': return $this->getPublicationsByRemoteSite();
			break;
			case 'SHOWREMOTE': $content .= $this->renderRemotePublications();
			break;
		}

		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * Generating content for a remote site accessing publications of this database via a HTTP GET request
	 **/
	private function getPublicationsByRemoteSite() {
		$content = '';
		$sortby = $this->piVars['sortby'].' '.$this->piVars['order'];		
 		
		// get pubs by categories
		if (isset($this->piVars['categories'])) {
  			$catIds = $this->db->fetchCategoriesByNameAsString($this->piVars['categories']);
  			$pubs = $this->db->fetchPubsByCategories($catIds, '', '', $sortby, $this->piVars['limit']);
  			$pubs = $this->db->fetchAndAddParents($pubs);
  		// get a single publication	
		} elseif (isset($this->piVars['pubid'])) {
			$pubs = $this->db->fetchSinglePub($this->piVars['pubid']);
			$pubs = $this->db->fetchAndAddParents($pubs);
			$pubs['children'] = $this->db->fetchChildren($this->piVars['pubid']);
		}
				
	    $content = json_encode($pubs);									

	    // return the result array between to markes	
		return '###REQUEST_START###'.$content.'###REQUEST_END###';
	}
	
	
	private function renderRemotePublications() {
		
		if (isset($this->piVars['pubid']))
			$mode = 'single';
	    else 
			$mode = 'list';
		
		// set the singlepage pid to the current page
		$singlepid = $GLOBALS['TSFE']->id;
		
		$remoteurl = $this->pi_getFFValue($this->ffdata, 'remoteurl', 'sDEF');
		$rpid = $this->pi_getFFValue($this->ffdata, 'remotepid', 'sDEF');
		if (isset($rpid) && $rpid!=='' && $rpid !== 0)
			$confArray['id'] = $rpid;  
		$confArray['tx_pubdb_pi1[sortby]'] = $this->pi_getFFValue($this->ffdata, 'sortmode', 'sDEF');
		$confArray['tx_pubdb_pi1[order]'] = $this->pi_getFFValue($this->ffdata, 'order', 'sDEF');
		$confArray['tx_pubdb_pi1[limit]'] =  $this->pi_getFFValue($this->ffdata, 'limit', 'sDEF');

		if ($mode === 'single')		
			$confArray['tx_pubdb_pi1[pubid]'] = $this->piVars['pubid'];
		else
			$confArray['tx_pubdb_pi1[categories]'] = $this->pi_getFFValue($this->ffdata, 'remotecategories', 'sDEF');
 		
		$confArray['tx_pubdb_pi1[pid]'] =  $singlepid;
		
		$request = t3lib_div::makeInstance('t3lib_http_Request', $remoteurl);
		$request->setMethod('GET');
		$url = $request->getUrl();
		$url->setQueryVariables($confArray);
		
		try {
			$response = $request->send();
			//debug($response);
		} catch(Exception $e) {
			debug($e);
		}
		preg_match('/###REQUEST_START###(.+)###REQUEST_END###/s', $response->getBody(), $match);
		
		$pubs = json_decode($match[1], TRUE);
	    
		if ($mode === 'list')
			$content .= $this->renderList($pubs, $singlepid);
		elseif ($mode === 'single') {
			
			// fetch first publication
			$singlepubArray = $pubs['pubs'];
			$pub = reset($singlepubArray);
			
			// fetch parent
			$parentPubArray = $pubs['parents'];
			$parent = reset($parentPubArray);
									
			$content .= $this->renderSingleView($pub, $parent, $pubs['children'], '', $GLOBALS['TSFE']->id);
		}
		
		//$content .= $response->getBody();
		return $content;
	}

	/*
	 *   Creation of a marker for the tt_news plugin to allow a pubdb entry getting linked at a news entry
	*/
	public function extraItemMarkerProcessor($markerArray, $row, $lConf, $parentObject) {
			
		$spid = $parentObject->conf['pubdb_singlePID'];

		if ($row['tx_pubdb_newslink'] > 0) {
			$params = array( $this->prefixId => array( 'pubid' => $row['tx_pubdb_newslink'], ppid => $GLOBALS['TSFE']->id));
			$publink = $parentObject->pi_linkToPage($row['tx_pubdb_link_title'], $spid, '', $params);
		}
		else
			$publink = '';

		$markerArray['###PUBLINK###'] = '<b>'.$publink.'</b>';
		return $markerArray;

	}
	
	
	/*
	 * Generates the list view page with a given list of publications
	*/
	private function renderList($pubs, $singlepid=0) {
		
		//debug($pubs);
		
		// get the singlepage pid
		if ($singlepid === 0)
			$singlepid = $this->pi_getFFValue($this->ffdata, 'singlepid', 'sOtherSettings');

		// get the orderpage pid
		$orderpid =  $this->pi_getFFValue($this->ffdata, 'orderpid', 'sOtherSettings');

		// load template file
		if (isset($this->conf['templateFile']))
			$template = $this->cObj->fileResource($this->conf['templateFile']);
		else
			$template = $this->cObj->fileResource($this->standardTemplate);

		$subpart_browser = $this->cObj->getSubpart($template, '###LIST_BROWSE_TEMPLATE###');
	
		// --------------------------- show page browser ------------------------------
		$pagebrowser = '';
		$start = 0;
		$max = $this->pi_getFFValue($this->ffdata, 'resultnum', 'sDEF');
		$n_pubs = sizeof($pubs['pubs']);
		
		//debug($n_pubs, '# of pubs');
		
		$end = $max;
		if ($end > $n_pubs) $end = $n_pubs;

		if (!isset($this->piVars['pnum'])) $this->piVars['pnum'] = 1;
			
		if ($n_pubs > $max && $max>0) {

			$next = $this->piVars['pnum'] + 1;
			$prev = $this->piVars['pnum'] - 1;
			$last = $n_pubs/$max;

			if ($this->piVars['pnum'] > 1)
				$pagebrowser .= $this->pi_linkTP_keepPIvars('<<', array('pnum' => $prev)) . '&nbsp;';

			for ($i=0; $i <=  $last; $i++)  {
				$c = $i+1;
				if ($this->piVars['pnum'] == $c || ( !isset($this->piVars['pnum']) && $c==1) )
					$link = $c;
				else
					$link = $this->pi_linkTP_keepPIvars($c, array('pnum' => $c));
				$pagebrowser .= $link.' ';
			}
			if ($this->piVars['pnum'] < $last)
				$pagebrowser .= '&nbsp;'.$this->pi_linkTP_keepPIvars('>>', array('pnum' => $next));
		}

		// show page browser on the top of list
		$bmarkerARRAY['###LIST_PAGEBROWSER###']=$pagebrowser;
		$content .= $this->cObj->substituteMarkerArray($subpart_browser, $bmarkerARRAY);

		// compute indizes
		$start = $max* ( $this->piVars['pnum'] - 1);
		$end = $max* $this->piVars['pnum'];
		if ($end > $n_pubs)	$end = $n_pubs;

		// --------------------------- render list of publications ------------------------------------

		if ($n_pubs === 0)
			$content .= '<p>' . $this->pi_getLL('list.noentries') . '<p>';
	
		$publications = $pubs['pubs'];

		// get first publication
		$pub = reset($publications);

		for ($i=1; $i <= $start; $i++)
			$pub = next($publications);
		
		for ($i=$start; $i<$end; $i++) {

		 	$params = array( $this->prefixId => array( 'pubid' => $pub['uid'], ppid => $GLOBALS['TSFE']->id));

		 	// render list item
			$content .= $this->renderListItem($pub, $params, $pubs, $singlepid);

			$pub = next($publications);	
			
		}
		// show page browser on the bottom of list
		$content .= $this->cObj->substituteMarkerArray($subpart_browser, $bmarkerARRAY);
		return($content);
	}
	
	
	private function generateSearchResultList() {
		
		if ($this->piVars['search'] != '') {
			
			if ($this->piVars['field'] != '') 
				$field = $this->piVars['field'];
			else
				$field = 'title';

			$match = $this->piVars['search'];

			$pubs = $this->db->fetchSearchResult($field, $match, $field, 1000, $this->piVars['category']);
			$pubs = $this->db->fetchAndAddParents($pubs);
			
		} else {
			$content .= $this->pi_getLL('list.noentries').'<br />';
			return $content;
		}
		
		if (sizeof($pubs['pubs']) === 0) {
			$content .= $content .= $this->pi_getLL('list.noentries').'<br />';
			return $content;
		} else {
			$content .= sizeof($pubs['pubs']).'&nbsp;'.$this->pi_getLL('search.entriesfound').'<br />';
			$content .= $this->renderList($pubs);
		}
		
		return $content;
	}

	/**
	 * Generates the list of publications to be shown
	 *
	 * @return	String   The lists content		...
	 */
	private function generateListView() {

		$counter = 0;

		if ($this->showSearchForm == 1)
			$content .= $this->renderSearchView(); 

		// check if request is from search form
		if (isset($this->piVars['search'])) {
			$content .= $this->generateSearchResultList();
		} 
		// generate list from BE page settings otherwise
		elseif ($this->pi_getFFValue($this->ffdata, 'category', 'sDEF') != -1) {

			// get the categories and the link relation from the flexfrom data
			$catstring1 = $this->pi_getFFValue($this->ffdata, 'category', 'sDEF');
			$catstring2 = $this->pi_getFFValue($this->ffdata, 'notcategory', 'sDEF');

			// the AND or OR relation for categorie selection in list
			$linkrel = $this->pi_getFFValue($this->ffdata, 'catrel', 'sDEF');
		
			// sort clause clause
			switch ($this->pi_getFFValue($this->ffdata, 'sortmode', 'sDEF')) {
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
			$orderby .= ' '.$this->pi_getFFValue($this->ffdata, 'order', 'sDEF');

			$publications = $this->db->fetchPubsByCategories($catstring1, $catstring2, $linkrel, $orderby);
			
			$publications = $this->db->fetchAndAddParents($publications);
			//debug($publications);
			$content .= $this->renderList($publications);
		}
			return($content);
	}

	
	private function generateSingleView() {
		$params = t3lib_div::_GET($this->prefixId);
		
		// generate the return link
		if (array_key_exists('ppubid', $params)) {
			$parentPubId = $params['ppubid'];
		} else
			$parentPubId = 0;
		
		if (array_key_exists('ppid', $params))
			$pPID = $params['ppid'];
		else 
			$pPID = 0;
		
		if (array_key_exists('pubid', $params))
			$pubid = $params['pubid'];
		elseif (array_key_exists('doi', $params)) {
			// changed to pubid instead of doi part
			$pubid = $params['doi'];
		}
		
		// fetch publicaction
		$data = $this->db->fetchSinglePub($pubid);
		//debug($data);
		// return, if nothing was found
		if (sizeof($data['pubs']) === 0) {
			$singlepage .=  $this->pi_getLL('error.noentry') . ' ' . $pubid . '!</br>';
			return $singlepage;
		}
		
		// fetch parent pubs if any
		$data = $this->db->fetchAndAddParents($data);
		 
		// get the first publication
		$publications = $data['pubs'];
		$pub = reset($publications);
		
		// get the parent
		$parents = $data['parents'];
		$parentPub = reset($parents);
		
		if ($pub['pubtype'] === 'journal' || $pub['pubtype'] === 'book' || $pub['pubtype'] === 'conference_proceedings') {
			$childPubs = $this->db->fetchChildren($pub['uid']);
		}
		
		return $this->renderSingleView($pub, $parentPub, $childPubs, $parentPubId, $pPID, $orderPID);
	}
	
	
	/**
	 * Generates a detailed view of a single publication
	 *
	 * @return	String      The content
	 */
	private function renderSingleView($pub, $parentPub, $childPubs, $parentPubId=0, $parentPID=0) {
		
		// generate the return link
		if ($parentPubId !== 0 && $parentPubId !== '') {
			$parentPubIdStr = array($this->prefixId.'[pubid]' => $parentPubId);
		} else
			$parentPubIdSr = NULL;
		
		if (parentPID !== 0)
			$returnlink =  $this->pi_linkToPage('<< '.$this->pi_getLL('back'), $parentPID, '', $parentPubIdStr);
		else
			$returnlink = '';
		
		// get the id of the order page
		$orderpid =  $this->pi_getFFValue($this->ffdata, 'orderpid', 'sOtherSettings');
		
		// insert title
		if ($pub['openFile'] != '' || $pub['file'] != '')
			$p_file .= '<b>'.$this->pi_getLL('download_title').'</b><br/>';

		// free downloadable files
		$fileTypeLink = 'filetype.' . $pub['openFileType'];
		if ($pub['openFile'] != '') {
			$files = explode(',', $pub['openFile']);
			foreach ($files as $f) {
					$p_file .= '<a  target="_blank" href="fileadmin/user_upload/tx_pubdb/'.$f.'">'.$f.'</a><br/>';
			}
		}
			
		$download = $this->db->hasPubAccess($this->usergroups, $pub['uid']);
		$fileTypeLink = 'filetype.' . $pub['fileType'];
		if ($pub['file'] != '') {
			$files = explode(',', $pub['file']);
			foreach ($files as $f) {

				if ($download) 
						$p_file .= '<a  target="_blank" href="fileadmin/user_upload/tx_pubdb/'.$f.'">'.$f.'</a><br/>';
				else
						$p_file .= '<span title="'.$this->pi_getLL('hint.loginrequiredfordownload').'">'.$f.'</span><br/> ';
			}
		}
			
		$p_order = '';
		$p_info = '';

		if ($pub['hashardcopy'] == 1) {
			$params = array( $this->prefixId => array( 'pubid' => $pub['uid'], ppid => $GLOBALS['TSFE']->id));
			$p_order = $this->pi_linkToPage($this->pi_getLL('order'), $orderpid, '', $params);
		}

		if ($pub['number'] != '' && $pub['number'] > 0) $p_info .= $this->pi_getLL('volume').' '.$pub['number'].', ';
		if ($pub['issue'] != '' && $pub['issue'] > 0) $p_info .= $this->pi_getLL('issue').' '.$pub['issue'].', ';
		if ($pub['year'] != '' && $pub['year'] > 0) $p_info .= $pub['year'].', ';
		if ($pub['publisher'] != '') $p_info .= $pub['publisher'].', ';
		if ($pub['location'] != '') $p_info.= $pub['location'].', ';
		if ($pub['pages'] != '') $p_info .= $this->pi_getLL('pages').' '.$pub['pages'].', ';
		if ($pub['isbn'] != '') $p_info.= $this->pi_getLL('list.isbn').': '.$pub['isbn'].', ';
		if ($pub['isbn2'] != '') $p_info.= $this->pi_getLL('list.isbn2').': '.$pub['isbn2'].', ';
		$p_info = preg_replace('/(\,\s*)$/', '', $p_info);
		
		// load template file
		if (isset($this->conf['templateFile']))
			$template = $this->cObj->fileResource($this->conf['templateFile']);
		else
			$template = $this->cObj->fileResource($this->standardTemplate);
		
		$p_parent = '';
	    if ($parentPub['title'] != '') {
	    	$p_parent_title = $parentPub['title'];
	    	if ($parentPub['number'] != '' && $parentPub['number'] > 0) $p_parent_meta = $this->pi_getLL('volume').' '.$parentPub['number'].', ';
		    if ($parentPub['issue'] != '' && $parentPub['issue'] > 0) $p_parent_meta .= $this->pi_getLL('issue').' '.$parentPub['issue'].', ';
		    if ($parentPub['edition'] != '' && $parentPub['edition'] >0 ) $p_parent_meta .= $this->pi_getLL('edition').' '.$parentPub['edition'].', ';
	    	if ($pub['pages'] != '') $p_parent_meta .= $this->pi_getLL('pages').' '.$pub['pages'].', ';
	    	$p_parent_meta = preg_replace('/(\,\s*)$/', '', $p_parent_meta);
	    	
	    	$subpart_parent = $this->cObj->getSubpart($template, '###SINGLE_TEMPLATE_PARENT###');
	    		
	    	// get content and define substitution
	    	$markerARRAYParent['###SINGLE_PARENT_TITLE###'] = $p_parent_title;
	    	$markerARRAYParent['###SINGLE_PARENT_META###'] = $p_parent_meta;
	    	// substitute
	    	$p_parent = $this->cObj->substituteMarkerArray($subpart_parent, $markerARRAYParent);
	    }
		
	    /* Author policy:
	     * 1) check contributors
	    * 2) check coauthor list
	    * 3) check author/editor field
	    */
	    $contributors = $data['contributors'];
	    $conts  = $contributors[$pub['uid']];
	    if (sizeof($conts) < 1 && isset($pub['coauthors']) && strlen($pub['coauthors']) > 1) $conts = tx_pubdb_utils::parseAuthors($pub['coauthors']);
	    if (sizeof($conts) < 1 && isset($pub['author']) && strlen($pub['author']) > 1) $conts[] = tx_pubdb_utils::parseFullname($pub['author']);
	    $author = $this->createContributorString($conts);
	    $editors = $this->createContributorString($conts, 'editor');

		// if journal,proceedings or book, render the content
		$p_content = '';
		$p_content_title = '';
		if (tx_pubdb_utils::typeHasChildren($pub['pubtype'])) {

			foreach ($childPubs['pubs'] as $childPub) {
 	 			$params = array( $this->prefixId => array( 'pubid' => $childPub['uid'], 'ppubid' => $pub['uid'], ppid => $GLOBALS['TSFE']->id));

				$p_content .= $this->renderListItem($childPub, $params, $childPubs, $this->pi_getFFValue($this->ffdata, 'singlepid', 'sOtherSettings'));
			}
			if ($p_content !== '') $p_content_title = $this->pi_getLL('single.content.title');
		}
		
		if (strlen($pub['abstract']) > 0) 
			$p_abstract_title = $this->pi_getLL('single.abstract.title'); 
		else 
			$p_abstract_title = '';
		
		// get subpart
		$subpart = $this->cObj->getSubpart($template, '###SINGLE_TEMPLATE###');
		
		$markerARRAY['###SINGLE_PARENT###'] = $p_parent;
		$markerARRAY['###SINGLE_DOI###'] = $pub['doi'];
		$markerARRAY['###SINGLE_TITLE###'] = $pub['title'];
		$markerARRAY['###SINGLE_SUBTITLE###'] = $pub['subtitle'];
		$markerARRAY['###SINGLE_META###'] = $p_info;
		$markerARRAY['###SINGLE_AUTHORS###'] = $author;
		$markerARRAY['###SINGLE_AFFILIATION###'] = '';
		
		$markerARRAY['###SINGLE_CONTENT_TITLE###'] = $p_content_title;
		$markerARRAY['###SINGLE_CONTENT###'] = $p_content;

		$markerARRAY['###SINGLE_FILE###'] = $p_file;
		$markerARRAY['###SINGLE_ABSTRACT_TITLE###'] = $p_abstract_title;
		$markerARRAY['###SINGLE_ABSTRACT###'] = $pub['abstract'];
		
		$markerARRAY['###SINGLE_RETURNLINK###'] = $returnlink;
		$markerARRAY['###SINGLE_ORDERLINK###'] = $p_order;
		
		$singlepage .= $this->cObj->substituteMarkerArray($subpart, $markerARRAY);

		return $singlepage;
	}

	/**
	 * Generates a detailed view of a single publication
	 *
	 * @return	String      The content
	 */
	private function renderSearchView() {
		$url=$this->pi_getPageLink($GLOBALS['TSFE']->id);

		// load template file
		if (isset($this->conf['templateFile']))
			$template = $this->cObj->fileResource($this->conf['templateFile']);
		else
			$template = $this->cObj->fileResource($this->standardTemplate);

		// get subpart
		$subpart = $this->cObj->getSubpart($template, '###SEARCH_TEMPLATE###');

		// set marker replacements
		$markerARRAY['###SEARCH_TITLE###'] = $this->pi_getLL('search');
		$markerARRAY['###SEARCH_BEGIN_FORM###']= '<form method="POST" action="'.$url.'">';
		$markerARRAY['###SEARCH_SEARCH_FOR_DESC###'] = $this->pi_getLL('search.searchfor');
		$markerARRAY['###SEARCH_SEARCH_IN_DESC###']  = $this->pi_getLL('search.infield');
		$markerARRAY['###SEARCH_SEARCH_CAT_DESC###']  = $this->pi_getLL('search.incategory');

		$markerARRAY['###SEARCH_SEARCH_FOR_FIELD###'] = '<input name="'.$this->prefixId.'[search]" type="text" />';
		$markerARRAY['###SEARCH_SEARCH_IN_FIELD###'] = '<select name="'.$this->prefixId.'[field]" />';
		$markerARRAY['###SEARCH_SEARCH_IN_FIELD###'] .= '<option value="title">'.$this->pi_getLL('search.titlefield').'</option>';
		$markerARRAY['###SEARCH_SEARCH_IN_FIELD###'] .= '<option value="author">'.$this->pi_getLL('search.authorfield').'</option>';
		$markerARRAY['###SEARCH_SEARCH_IN_FIELD###'] .= '<option value="year">'.$this->pi_getLL('search.yearfield').'</option>';
		$markerARRAY['###SEARCH_SEARCH_IN_FIELD###'] .= '<option value="number">'.$this->pi_getLL('search.numberfield').'</option>';
		$markerARRAY['###SEARCH_SEARCH_IN_FIELD###'] .= '</select>';

		$markerARRAY['###SEARCH_SEARCH_CAT_FIELD###'] = '<select name="'.$this->prefixId.'[category]" />'.
				'<option value="0">'.$this->pi_getLL('search.allcat').'</option>';

		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,name', 'tx_pubdb_categories', 'name!="" AND deleted="0"');
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
			$markerARRAY['###SEARCH_SEARCH_CAT_FIELD###'].='<option value="'.$row['uid'].'">'.$row['name'].'</option>';
		}

		$markerARRAY['###SEARCH_SUBMIT###'] = '<input type="submit" value="'.$this->pi_getLL('search.submit').'"/>';
		$markerARRAY['###SEARCH_END_FORM###'] = '</form>';

		//subsitute
		$content .= $this->cObj->substituteMarkerArray($subpart, $markerARRAY);

		return $content;
	}



	private function generateOrderView() {

		// check if ready for sending
		if (isset($this->piVars['ordersent']))  {
			if ($this->piVars['name'] != '' && $this->piVars['street'] != '' && $this->piVars['city'] != '' && $this->piVars['zipcode'] != '') {
				$message .= 'Bestellung'.chr(10);
				$message .= 'Bestellung von: '.chr(10);

				// get the publication
				$pubs = $this->db->fetchSinglePub($this->piVars['pub']);
				$pub = reset($pubs['pubs']);
				$message .= $pub['author'].chr(10).$pub['title'];
				if ($pub['subtitle'] != '') $message .= ', '.$pub['subtitle'];
				if ($pub['number'] != '') $message .= ', '.$pub['number'];
				if ($pub['location'] != '') $message .= ', '.$pub['location'];
				if ($pub['year'] != '') $message .= ', '.$pub['year'];
				$message.=chr(10).'Preis: ' . $pub['price'];
				if ($pub['reducedprice'] != '')     $message.=chr(10).'Mitgliedspreis: '.$pub['reducedprice'];
				
				$message .= chr(10).chr(10).'Besteller ist Mitglied: ';
				if ($ismember == 1)
					$message .= 'Ja';
				else
					$message .= 'Nein';

				$message .= chr(10).chr(10).'An: '.$this->piVars['name'].chr(10);
				$message .= 'Firma: '.$this->piVars['company'].chr(10);
				$message .= 'Adresse: '.$this->piVars['street'].chr(10).$this->piVars['zipcode'].' '.$this->piVars['city'].chr(10).$this->piVars['country'].chr(10);
				$message .= 'E-mail: '.$this->piVars['email'];
				$recipient = $this->pi_getFFValue($this->ffdata, 'orderemail', 'sOtherSettings');
				$res = $this->cObj->sendNotifyEmail($message, $recipient, '', 'webadmin@seth.asc.tuwien.ac.at', 'Weborder', '');
				$content .= $this->pi_getLL('order.sentmessage').'<br /><br />';
				return $content;
			} else $ordercomplete = FALSE;
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
		$result=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_pubdb_data', 'uid='.$pubid);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))  {
			$pub = $row['author'].'<br><b>'.$row['title'].'</b>';
			if ($row['subtitle'] != '') $pub .= ', '.$row['subtitle'];
			if ($row['number'] != '') $pub .= ', '.$row['number'];
			if ($row['location'] != '') $pub .= ', '.$row['location'];
			if ($row['year'] != '') $pub .= ', '.$row['year'];
			$price = $row['price'];
			if ($row['reducedprice'] != '')
				$reducedprice = $row['reducedprice'];
		}

		// load template file
		if (isset($this->conf['templateFile']))
			$template = $this->cObj->fileResource($this->conf['templateFile']);
		else
			$template = $this->cObj->fileResource($this->standardTemplate);

		// get subpart
		$subpart = $this->cObj->getSubpart($template, '###ORDER_TEMPLATE###');
			
		$markerARRAY['###ORDER_TEXT1###'] = $this->pi_getLL('order.text1').'<br /><br />'.$pub.'<br /><br />'.
				$this->pi_getLL('order.text2').'<br /><br />'.$this->pi_getLL('order.price').': <b>'.$price.'</b><br />'.
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
		$content .= $this->cObj->substituteMarkerArray($subpart, $markerARRAY);
			
		return $content;
	}

	/**
	 * Renders a single list item
	 * @param Array $pub
	 * @param Array $params
	 * @param Array $publications
	 * @param integer $singlepageId 
	 * @return mixed
	 */
	private function renderListItem($pub, $params, $publications=NULL, $singlepageId) {

		if ($pub['abstract'] !== '' || tx_pubdb_utils::typeHasChildren($pub['pubtype']) || $pub['file'] !=='' || $pub['openFile'] !== '') {
			$p_more = $this->pi_linkToPage($this->pi_getLL('more'), $singlepageId, '', $params);
		}

		if (isset($parent['year']) && $parent['year'] > 0) $p_year= $parent['year'];
		if ($pub['year'] != '' && $pub['year'] > 0) $p_year=$pub['year'];

		if ($pub['hashardcopy'] == 1)
			$p_order = $this->pi_linkToPage($this->pi_getLL('order'), $orderpid, '', $params);

		if ($pub['subtitle'] != '') $p_subtitle = $pub['subtitle'];
		if ($pub['publisher'] != '') $p_publisher = $pub['publisher'];
		if ($pub['location'] != '') $p_location = $pub['location'];

		if ($pub['number'] != '') $p_number = $pub['number'];
		if (isset($parent['number'])) $p_number = $parent['number'];

		if ($pub['issue'] != '') $p_issue = $pub['issue'];
		if (isset($parent['issue'])) $p_issue = $parent['issue'];

		if ($pub['isbn'] != '') $p_isbn = $this->pi_getLL('list.isbn').':&nbsp;'.$pub['isbn'];
		if ($pub['pages'] != '') $p_pages=$pub['pages'];
		if ($pub['doi'] != '') $p_doi = 'doi:'.$pub['doi'];
			
		if ($pub['edition'] != '') $p_edition = $this->addOrdinalNumberSuffix($pub['edition']);

		/* Author policy:
		 * 1) check contributors
		 * 2) check coauthor list
		 * 3) check author/editor field
		 */ 
		$conts  = $publications['contributors'][$pub['uid']];
		if (sizeof($conts) < 1 && isset($pub['coauthors']) && strlen($pub['coauthors']) > 1) $conts = tx_pubdb_utils::parseAuthors($pub['coauthors']);
		if (sizeof($conts) < 1 && isset($pub['author']) && strlen($pub['author']) > 1) $conts[] = tx_pubdb_utils::parseFullname($pub['author']);
		
		$author = $this->createContributorString($conts);
		
		$editors = $this->createContributorString($conts, 'editor');

		if ($p_file!='' || $p_order!='') $p_order.='<br />';

		if (isset($publications) && key_exists($pub['parent_pubid'], $publications['parents'])) {
			$parent = $publications['parents'][$pub['parent_pubid']];
			$p_parent_title = $parent['title'];
			$p_parent_abbrev_title = $parent['abbrev_title'];
		} else {
			$p_parent_title = '';
			$p_parent_abbrev_title = '';
		}

		// get content and define substitution
		$markerARRAY['###LIST_TITLE###']=$pub['title'];
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
		if (isset($this->conf['templateFile']))
			$template = $this->cObj->fileResource($this->conf['templateFile']);
		else
			$template = $this->cObj->fileResource($this->standardTemplate);

		// get subpart
		if($pub['pubtype'] === 'journal_article')
			$subpart = $this->cObj->getSubpart($template, '###LIST_ITEM_TEMPLATE_JOURNAL_ARTICLE###');
		else if ($pub['pubtype'] === 'book')
			$subpart = $this->cObj->getSubpart($template, '###LIST_ITEM_TEMPLATE_BOOK###');
		else

			$subpart = $this->cObj->getSubpart($template, '###LIST_ITEM_TEMPLATE###');

		// substitute
		$item = $this->cObj->substituteMarkerArray($subpart, $markerARRAY);
		return $this->postProcessItem($item);
	}


	private function createContributorString($c, $role=NULL) {
		//t3lib_utility_Debug::debug($c,'contributors');
		$n = sizeof($c);
		for ($i=0; $i < $n; $i++) {
			
			// if a role is given, take only the coresonding once
			if (isset($role) && isset($c['role']) && $c['role'] !== $role)
				continue;

			$surname = $c[$i]['surname'];
			if (isset($c[$i]['suffix']) && strlen($c[$i]['suffix']) > 1) $surname .= $c[$i]['suffix'];
			$givenname = '';
			$givennames = explode(' ', trim($c[$i]['given_name']));
			foreach ($givennames as $n) {
				$givenname .= $n[0].'. ';
			}
			$givenname = trim($givenname);
			
			if ($i === 0) {
				$res .= $surname . ', '.$givenname;
			} else if ($i === (sizeof($c) -1)) {
				$res .= ' and '.$givenname.' '.$surname;
			} else {
				$res .= ', '.$givenname.' '.$surname;
			}
			
		}
		return $res;
	}

	private function addOrdinalNumberSuffix($num) {
		
		if (!in_array(($num % 100), array(11,12,13))) {
			switch ($num % 10) {
				// Handle 1st, 2nd, 3rd
				case 1:  return $num.'st';
				case 2:  return $num.'nd';
				case 3:  return $num.'rd';
			}
		}
		return $num.'th';
	}


	private function postProcessItem($item) {
	
  		// remove empty parantheis
		$item = str_replace('()', '', trim($item));
			
		// remove empty html tags, eg.<strong></strong> or <i></i> for able to detect the following patterns
		$item = preg_replace('/<[a-z]+><\/[a-z]+>/', '', $item);
	
		// remove repeating commas and dots
		$item = preg_replace('/\,(\s*[\,\.]+)+/', ',', $item);
		
		// remove empy partes ending with :	
		$item = preg_replace('/(\,\s)+\:/', ':', $item);

		// remove empty comma parts following a dot			
		$item = preg_replace('/\.(\s+[\,\.\:\;])+/', '.', $item);
	
		// remove unnecessary spaces
		$item = preg_replace('/\s+\,/', ',', $item);

		// make sure we end with a dot and not with any other character
		$item = preg_replace('/([\,\:]\s*<\/span>)/', '.</span>', $item);
		
		// make sure we do not start with a ., or :
		$item = preg_replace('/>\s*[\,\.\:\;]+/', '>', $item);

		return $item;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pubdb/pi1/class.tx_pubdb_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pubdb/pi1/class.tx_pubdb_pi1.php']);
}

?>
