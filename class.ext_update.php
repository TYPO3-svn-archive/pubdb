<?php
/***************************************************************
 *  Copyright notice
*
*  (c) 2014 Johannes Kropf <johannes@kropf.at>
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
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * New for pubdb 1.1.0
* Class for updating pubdb data elements.
*
* $Id: class.ext_update.php 26950 2014-01-11 14:41:54Z schoni $
*
* @author  Johannes Kropf <johannes@kropf.at>
* @package TYPO3
* @subpackage pubdb
*/

require_once(PATH_typo3conf.'ext/pubdb/Classes/class.tx_pubdb_utils.php');

class ext_update {

	private $ll = 'LLL:EXT:pubdb/locallang_updater.xml:updater.';
	private $contentItems;	
	private $authorFieldItems;
	private $multipleContributorItems;

	/**
	 * The main function called by typo3 handling the update
	 */
	public function main() {

		$GLOBALS['TYPO3_DB']->debugOutput = TRUE;
		
		$this->contentItems = $this->getContentItems();
		$this->authorFieldItems = $this->getAuthorFieldItems();
		$this->multipleContributorItems = $this->getMultipleContributorItems(); 
		
		 if (t3lib_div::_GP('do_update')) {
 		   $out .= '<a href="' . t3lib_div::linkThisScript(array('do_update' => '', 'func' => '')) . '">' . $GLOBALS['LANG']->sL($this->ll . 'back') . '</a><br>';
		   $func = trim(t3lib_div::_GP('func'));
		   if (method_exists($this, $func)) {
		   	$msg = $this->$func();
		   	$out .= '
		   	<div style="padding:15px 15px 20px 0;">
		   	<div class="typo3-message message-ok">
		   	<div class="message-header">' . $GLOBALS['LANG']->sL($this->ll . 'updateresults') . '</div>
		   	<div class="message-body">
		   	' . $msg['success'] . '
		   	</div>
		   	</div>';
		   	if (strlen($msg['failure']) > 0) {
		   		$out .= '<div class="typo3-message message-error">'.
				   		'<div class="message-header">' . $GLOBALS['LANG']->sL($this->ll . 'updatefailure') . '</div>'.
				   		'<div class="message-body">'.$msg['failure'] . '</div>'.
		  			 	'</div>';
			   	}
		  	 	$out .= '</div>';
		   } else {
		   	$out .= '
		   	<div style="padding:15px 15px 20px 0;">
		   	<div class="typo3-message message-error">
		   	<div class="message-body">ERROR: ' . $func . '() not found</div>
		   	</div>
		   	</div>';
		   }
		} else {
		    //$out .= 'change content items ('.count($contentItems).')';
			$out .= '<a href="' . t3lib_div::linkThisScript(array('do_update' => '', 'func' => '')) . '">' . $GLOBALS['LANG']->sL($this->ll . 'reload') . '
			<img style="vertical-align:bottom;" ' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/refresh_n.gif', 'width="18" height="16"') . '></a><br>';
			
			$out .= $this->displayWarning();
			
			$out .= '<h3>' . $GLOBALS['LANG']->sL($this->ll . 'actions') . '</h3>';
			
			// outdated content item entry
			$out .= $this->displayUpdateOption('searchContentItems', count($this->contentItems), 'updateContentItems');
			
			// outdated author field
			$out .= $this->displayUpdateOption('searchAuthorFieldItems', count($this->authorFieldItems), 'updateAuthorFieldItems');
			
			// multiple contributor entries
			$out .= $this->displayUpdateOption('searchDuplicateContributors', count($this->multipleContributorItems), 'updateMultipleContributorItems');
		}

		 if (t3lib_div::int_from_ver(TYPO3_version) < 4003000) {
                                // add flashmessages styles
                        $cssPath = $GLOBALS['BACK_PATH'] . t3lib_extMgm::extRelPath('pubdb');
                        $out = '<link rel="stylesheet" type="text/css" href="' . $cssPath . 'compat/flashmessages.css" media="screen" />' . $out;
                }
	
		return $out;
	}

    /**
     * Return all rows of the tx_pubdb_data table for which the pubtype is set to "content_item".
     */
	private function getContentItems() {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_pubdb_data', 'pubtype="content_item"');
	
		$resultRows = array();
		while(($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			$resultRows[] = $row;
		}
		return $resultRows;
	}	
	
	/**
	 * Returns all rows of the tx_pubdb_data table for with the "author" field is not empty.
	 */
	private function getAuthorFieldItems() {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_pubdb_data', 'author!=""');
	
		$resultRows = array();
		while(($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			$resultRows[] = $row;
		}
		return $resultRows;
	}
	
	
	private function getMultipleContributorItems() {
		$multiples = array();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('a.uid,a.surname,a.given_name', 'tx_pubdb_contributors a INNER JOIN tx_pubdb_contributors b ON a.surname=b.surname',
				'a.uid<>b.uid AND a.deleted = 0');
		//debug($GLOBALS['TYPO3_DB']->sql_num_rows($res), 'rows');
	    while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))  {
	    	$surname = trim($row['surname']);
	    	$given_name  = trim($row['given_name']);
	    	
	    	$index = $this->getContributorItem($multiples, $surname, $given_name);
	    	if ($index >= 0)
	    		$item = $multiples[$index];
	    	else 
	    		$item = array();
	    	
	    	if (!in_array($row['uid'], $item['uids']))
	    		$item['uids'][] = $row['uid'];

    	    // take the name which has more details
	    	 if (strlen($row['given_name']) > strlen($item['given_name']))
	    	  	$item['given_name'] = $given_name;
	    	 
	    	 // same for the affiliation
	    	 if (strlen($row['affiliation']) > strlen($item['affiliation']))
	    	 	$item['affiliation'] = $row['affiliation'];
	    	
	    	 if ($index >= 0) {
	    	 	$multiples[$index] = $item; 
	    	 } else {
	    	 	$item['surname'] = $surname;
	    	 	$multiples[] = $item;
	    	 }
	    }
	    
	    // remove non-duplicates (cases where surname maches but given_name didn't)
	    foreach ($multiples as $key=>$d) {
	    	if (sizeof($d['uids']) === 1)
	    		$temp[] = $key;
	    }
	    
	    foreach ($temp as $key) {
	    	unset($multiples[$key]);
	    }
	    
	    //debug($multiples);
		return $multiples;
	}
	
	private function getContributorItem($multiples, $surname, $given_name) {
		
		foreach ($multiples as $key => $m) {
			if (strcasecmp($m['surname'], $surname) === 0 && $this->givenameMatch($m['given_name'], $given_name))
				return $key;
		}

		return -1;
		
	} 
	
	private function givenameMatch($name1, $name2) {

		// make sure we have one and only one space between a dot and the next letter
		$n1 = preg_replace('/(\p{L})(\.\s*)(\p{L})/', '$1. $3', $name1);
		$n2 = preg_replace('/(\p{L})(\.\s*)(\p{L})/', '$1. $3', $name2);
		
		if (strcasecmp($n1, $n2) === 0) {
			return TRUE;
		}
		
		// compare initials
   		$n1_i = $this->getNameInitials(trim($n1));
   		$n2_i = $this->getNameInitials(trim($n2));
		
   		//debug($n1, $n1_i);
   		//debug($n2, $n2_i);
   		
   		if (strcasecmp($n1_i, $n2_i) === 0)
   			return TRUE;
   		
   		return FALSE;
	}
	
	
	private function getNameInitials($name) {
		// parse the first letter of every part of the name, separated by space or -
		return preg_replace('/([\p{L}])[\p{L}]+([\s\-]*)/', '$1.$2', $name);
	}
	
	
    /**
     * Changes all 'content_item' entries to 'book_chapter' in the column 'pubtype' in table 'tx_pubdb_data'.
     */
	private function updateContentItems() {
		$result = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_pubdb_data', 'pubtype="content_item"', array('pubtype' => 'book_chapter'));
	}

    /**
     * Creates entries in the contributors table and the table relating pubs and contributors. It also deletes the author entry in the pub table.
     * 
     * @return array Array of strings with success and error messages
     */
 	private function updateAuthorFieldItems() {
		$successMsg = '';
		$failureMsg = '';
		$userid = $GLOBALS['BE_USER']->user['uid'];
		foreach ($this->authorFieldItems as $item) {
		
                $contributors = $this->parseAuthorField($item['author'], $item['uid']);
                
                // perform a plausibility check
                if ($this->isContributorsPlausibel($contributors)) {
		     
                	$sort = $item['contributors']+1;
                	$nContributors = 0;
                	foreach($contributors as $c) {
	                // check, if contributor exists
    	            	$uids = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,surname,given_name', 'tx_pubdb_contributors', 'surname="'.
    	            		$c['surname'].'" AND given_name="'.$c['given_name'].'" AND contributor_type="'.$c['contributor_type'].'"');
                	
                		if (sizeof($uids) > 0) {
                			$id = $uids[0]['uid'];
	                	} else {
	                		$time = time();
    		           		$input = array('surname' => $c['surname'], 'given_name'=>$c['given_name'], 'contributor_type'=>$c['contributor_type'], 
    		           				'pid'=>$item['pid'], 'showinlist'=>1, 'crdate'=>$time, 'tstamp'=>$time, 'cruser_id'=>$userid);
            	    		if (isset($c['affiliation']) && strlen($c['affiliation']) > 0) $input['affiliation'] = $c['affiliation'];
    		           		$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_pubdb_contributors', $input);
                			$id = $GLOBALS['TYPO3_DB']->sql_insert_id();
                		}
    	            
                		// update pub to contributor relation
                		$time = time();
                		$input = array('pubid'=>$item['uid'], 'contributorid'=>$id, 'role'=>$c['role'],'pubsort'=>$sort,
                				'crdate'=>$time, 'tstamp'=>$time, 'pid'=>$item['pid'], 'cruser_id'=>$userid);
                		$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_pubdb_pub_contributors', $input);
                		$sort++;
                		$nContributors++;
                
                	}
                
	                // update count in pubdb_data
    	            $n = $item['contributors'] + $nContributors;
        	        $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_pubdb_data', 'uid='.$item['uid'], array('contributors'=>$n, 'author'=>''));
                
  		        	$successMsg .= $item['author'] . ' converted to ' . $this->createContributorString($contributors) . '<br />';
		} else {
			$failureMsg .= '<br />PubId '.$item['uid'].': author string "'.$item['author'].'" seems not correctly parsed: '.$this->createContributorString($contributors);
		}	
		
 		}
 		$msg['success'] = $successMsg;
 		$msg['failure'] = $failureMsg;
		return $msg;
	}
	
	
	private function updateMultipleContributorItems() {
		
		// iterate the found multiple entries
		foreach($this->multipleContributorItems as $item) {
			  // update the first entry
			  $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_pubdb_contributors', 'uid='.$item['uids'][0], 
			  		array( 'given_name' => $item['given_name'], 'affiliation' => $item['affiliation']));
			
			  $msg_updated .= 'Data for contributor with id '.$item['uids'][0].' updated to '.$item['surname'].', '.$item['given_name'].', '.$item['affiliation'].'<br />';
			  
			  $idString = '';
			  // delete the others
			  $n = count($item['uids']);
			  for ($i = 1; $i < $n; $i++) {
			  	 $GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_pubdb_contributors', 'uid='.$item['uids'][$i]);
			  	 $msg_deleted .= 'Multiple contributor with id '.$item['uids'][$i].' deleted. ('.$item['surname'].', '.$item['given_name'].', '.$item['affiliation'].')<br />';
			  	 
			  	 if ($i > 1)
			  	 	$idString .= ',';
			  	 $idString .= $item['uids'][$i];
			  	 
			  }

			  $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('contributorid,pubid', 'tx_pubdb_pub_contributors', 'contributorid IN ('.$idString.')');
			  while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			  	$msg_updatedRel .= 'Contributor for publication with id '.$row['pubid'].' updated from '.$row['contributorid'].' to '.$item['uids'][0].'<br/>';
			  }
			  // update ids in relation table
			  $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_pubdb_pub_contributors', 'contributorid IN ('.$idString.')', array('contributorid'=>$item['uids'][0]));
		}
		
		$msg['success'] = '<strong>Contibtuor table tx_pubdb_contributors:</strong><br/>';
		$msg['success'] .= $msg_updated;
		$msg['success'] .= '<br />'.$msg_deleted;
		$msg['success'] .= '<br/><strong>Relational table tx_pubdb_pub_contributors:</strong><br/>';
		$msg['success'] .= $msg_updatedRel;
		
		return $msg;
		
	}

    /**
     * Checks if the contributors array is plausible in terms of a successful parsing of the names
     * @param Array contributors - The associatove contributor array
     * @return boolean 
    **/	
	private function isContributorsPlausibel($contributors) {
		foreach ($contributors as $c) {
			if (!isset($c['surname']) || $c['surname'] === '' || !isset($c['given_name']) || $c['given_name'] === '') {
				return FALSE;
			}
		}
		return TRUE;
		
	}
	
	/**
	 * Creates a short string out of a contributor array
	 * @param Array $contributors  the contributors array
	 *
	 * @return String - Readable string
	 */
	private function createContributorString($contributors) {
		$out = '';
		$counter = 1;
		foreach ($contributors as $c) {
			$out .= 'Contributor '.$counter.': [';
			$out .= 'surname: '.$c['surname'];
			$out .= '; given_name: '.$c['given_name'];
			$out .= '; role: '.$c['role'];
			if (strlen($c['affiliation']) > 0) 
				$out .= '; affiliation: '.$c['affiliation'];
			$out .= ']; ';
			$counter++;
		}
		return $out;
	}
	
	/**
	 *  Function parsing an author list string. Detects keywords for editors, strings in paranthesis are taken as affiliation.
	 *  The function tries to split the string into separate authors by ';', if none found, it takes ','. 
	 *  @param String $field - the author string
	 *  @param String $uid - the publication id, used only for debugging.
	 *  
	 *  @return an assosiative array with the parsed contributors
	 **/

	private function parseAuthorField($field, $uid = 0) {
		
		// trim first
		$field = trim($field);
		
	    $fieldLow = strtolower($field);

	    // Check, if field is decleared as editor
	    if (strpos($fieldLow, 'hrsg') !== FALSE || strpos($fieldLow, 'editor') !== FALSE || strpos($fieldLow, 'eds')) {
		$role = 'editor';
  	    } else {
		$role = 'author';
            }
	          
        // remove editor from field string
  	    $field = trim(preg_replace('/[\s\(]*(Hrsg|Editors|Eds|Editor)[\,\s\.\:\)\b]*/i', '', $field));

 	    // fill in missing spaces
	    $field = preg_replace('/([\p{L}]*)(\.)([\p{L}]+)/', '$1. $3', $field);	
	    
	    // remove unnecessary spaces around commas
	    $field = preg_replace('/[\s]+\,[\s]+/', ', ', $field);
	    
	    // relace commas in paranthesis (taken as affiliation) by a marker 
	    $field = preg_replace('/(\([^\,\(\)\;]*)([\;\,])([^\,\;\(\)]*\))/', '$1###MARKER###$3', $field);
	    
        // count commas, semmicolons and spaces
	    $commas = substr_count($field, ',');
	    $semicolons = substr_count($field, ';');
	    $spaces = substr_count($field, ' ');
	    
	    /*if ($uid === '10') {
	    	debug($field, 'preprocessed string');
	    	debug($semicolons, 'semicolons');
	    	debug($commas, 'commas');
	    	debug($spaces, 'spaces');
	    }
		*/
	    // If semicolons found, assume that they separate authors
	    if ($semicolons > 0) {
		$fields = explode(';', $field); 	
	    
            // if we have more commas or an equal number than spaces, we probably have the format surname1, given_name1, surname2, given_name2,...
 	    } else if ($commas >= $spaces) {
  	        $tempFields = explode(',', $field);
		$n = sizeof($tempFields);
		$c = 0;
		for ($i = 0; $i < $n; $i++) {

	          if ($i % 2 === 0) {		
		    $fields[$c] .= $tempFields[$i];
		  } else {
		    $fields[$c] = $tempFields[$i].' '.$fields[$c];
		    $c++;
		  }
		}
            } else {
		$fields = explode(',', $field);
	    }  
		
	    $c = 0;  
	    $contributors = array();  	
	    foreach ($fields as $item) { 
			
	    	// parse potential affiliation (everything between paranthesis)
	    	preg_match('/\((.*)\)/', $item, $matches);
	    	
	    	// remove the potential affiliation from the item string
	    	$item = trim(preg_replace('/\(.*\)/', '', $item));
	    	// unset full match (with paranthesis)
	    	unset($matches[0]);
	    	
			$contributors[$c] = tx_pubdb_utils::parseFullname($item);
			// re-replace the marker by ,
			$contributors[$c]['affiliation'] =  preg_replace('/###MARKER###/', ',', implode(',', $matches));
			$contributors[$c]['role'] = $role;
			$contributors[$c]['contributor_type'] = 'person';
			$c++;
	    }			    	

	   return $contributors;             			

	}
		
	
	private function displayUpdateOption($k, $count, $func) {
	
		$msg = $GLOBALS['LANG']->sL($this->ll . 'msg_' . $k) . ' ';
		$msg .= '<br><strong>' . str_replace('###COUNT###', $count, $GLOBALS['LANG']->sL($this->ll . 'foundMsg_' . $k)) . '</strong>';
		if ($count == 0) {
			$i = 'ok';
	
		} else {
			$i = 'warning2';
		}
		$msg .= ' <img ' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/icon_' . $i . '.gif', 'width="18" height="16"') . '>';
	
		if ($count) {
			$msg .= '<p style="margin:5px 0;">' . $GLOBALS['LANG']->sL($this->ll . 'question_' . $k) . '<p>';
			$msg .=  '<p style="margin-bottom:10px;"><em>'.$GLOBALS['LANG']->sL($this->ll . 'questionInfo_' . $k) . '</em><p>';
			$msg .= $this->getButton($func);
		} else {
			$msg .= '<br>' . $GLOBALS['LANG']->sL($this->ll . 'nothingtodo');
	
		}
	
		$out = $this->wrapForm($msg, $GLOBALS['LANG']->sL($this->ll . 'lbl_' . $k));
		$out .= '<br><br>';
	
		return $out;
	}
	
	private function displayWarning() {
		$out = '
		<div style="padding:15px 15px 20px 0;">
		<div class="typo3-message message-warning">
		<div class="message-header">' . $GLOBALS['LANG']->sL($this->ll . 'warningHeader') . '</div>
		<div class="message-body">
		' . $GLOBALS['LANG']->sL($this->ll . 'warningMsg') . '
		</div>
		</div>
		</div>';
	
		return $out;
	}
	
	private function getButton($func, $lbl = 'DO IT') {
	
		$params = array('do_update' => 1, 'func' => $func);
	
		$onClick = "document.location='" . t3lib_div::linkThisScript($params) . "'; return false;";
		$button = '<input type="submit" value="' . $lbl . '" onclick="' . htmlspecialchars($onClick) . '">';
	
		return $button;
	}
	
	private function wrapForm($content, $fsLabel) {
		$out = '<form action="">
		<fieldset style="background:#f4f4f4;margin-right:15px;">
		<legend>' . $fsLabel . '</legend>
		' . $content . '
	
		</fieldset>
		</form>';
		return $out;
	}
	 /**
         * Checks how many rows are found and returns true if there are any
         * (this function is called from the extension manager)
         *
         * @param       string          $what: what should be updated
         * @return      boolean
         */
        public function access($what = 'all') {
                return TRUE;
        }

}

?>
