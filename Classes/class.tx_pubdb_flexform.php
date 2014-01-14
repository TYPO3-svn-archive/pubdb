<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Johannes Kropf <johannes@kropf.at>
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

class tx_pubdb_flexform {


      function extendedParentList($config) {
		$optionList = array();
		//debug($config);
		if ($config['row']['pubtype'] === 'journal_article') {
			$result=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_pubdb_data','pubtype="journal"');
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))  {
 		  	  $label = $row['title'].', '.$row['year'].', '.$row['number'].'('.$row['issue'].')';
		   	  $value = $row['uid'];	
		    	  $optionList[] = array(0 => $label, 1=> $value);
			}
		}
		
		if ($config['row']['pubtype'] === 'conference_proceedings') {
			$result=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_pubdb_data','pubtype="conference"');
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))  {
			   $dateAr = getdate($row['startdate']);
 		 	   $label = $row['title'].', '.$row['number'].', '.$dateAr['year'];
		  	  $value = $row['uid'];	
		  	  $optionList[] = array(0 => $label, 1=> $value);
			}
		}

		if ($config['row']['pubtype'] === 'conference_paper') {
			$result=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_pubdb_data','pubtype="conference_proceedings"');
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))  {
	 		    $label = $row['title'].', '.$row['year'];
			    $value = $row['uid'];	
			    $optionList[] = array(0 => $label, 1=> $value);
			}
			
		}
		
		if ($config['row']['pubtype'] === 'book_chapter') {
			$result=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_pubdb_data','pubtype="book"');
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))  {
	 		    $label = $row['title'].', '.$row['edition'].', '.$row['year'];
			    $value = $row['uid'];	
			    $optionList[] = array(0 => $label, 1=> $value);
			}
			
		}

		$config['items'] = array_merge($config['items'], $optionList);
		return $config;
	}


	/*function subtitleField($PA, $fobj) {

		if (isset($PA['parameters']['cols'])) {
			$cols = $PA['parameters']['cols'];
		} else { $cols = '50'; }

		$formField.='<table class="wrapperTable" width="100%" cellspacing="0" cellpadding="0">';
		$formField.='<tbody><tr class="class-main12"><td class="formField-header" colspan="2">';
		if ($PA['row']['pubtype'] === 'conference') {
			$formField .= '<span class="class-main14" style="color:;"><strong>'.$GLOBALS['LANG']->sL('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.theme').'</strong></span>';
		} else {
			$formField .= '<span class="class-main14" style="color:;"><strong>subtitle</strong></span>';
		}
		$formField .='</td></tr><tr class="class-main11"><td class="formField-field" valign="top" colspan="2">';
		$formField .='<span class="t3-form-field-container">';
		$formField  .= '<input class="tceforms-textfield formField3" type="text" cols="'.$cols.'"></input>';
		$formField .= '</span></td></tr>';
		$formField .= '</tbody></table>';

		return $formField;	
	} */
	
	function xmlField($PA, $fobj) {
		$xml = new tx_pubdb_toxml();
		$uid = $PA['row']['uid'];
		if (isset($PA['parameters']['rows'])) {
			$rows = $PA['parameters']['rows'];
		} else { $rows = '10'; }

		if (isset($PA['parameters']['cols'])) {
			$cols = $PA['parameters']['cols'];
		} else { $cols = '50'; }

		$formField.='<table class="wrapperTable" width="100%" cellspacing="0" cellpadding="0">';
		$formField.='<tbody><tr class="class-main12"><td class="formField-header" colspan="2">';
		if (isset($PA['parameters']['label'])) {
			$formField .= '<span class="class-main14" style="color:;"><strong>'.$GLOBALS['LANG']->sL($PA['parameters']['label']).'</strong></span>';
		}
		if (isset($PA['parameters']['hint'])) {
			$formField .= '<br/><span class="class-main14" style="color:;"><i>'.$GLOBALS['LANG']->sL($PA['parameters']['hint']).'</i></span>';
		}
		$formField .='</td></tr><tr class="class-main11"><td class="formField-field" valign="top" colspan="2">';
		$formField .='<span class="t3-form-field-container">';
		if (!isset($uid) || $uid === 0) {
		        $formField .= '<span style="color:red">No record UID given or an error occurred</span>';
		} else {
			
			$xmlStr = $xml->entryToXML($PA['row']);
			$formField  .= '<textarea class="tceforms-textarea formField2 resizable" rows="'.$rows.'" cols="'.$cols.'" readonly> '.$xmlStr.'</textarea>';
			
		}
		$formField .= '</span></td></tr>';

		// create file with doi as name
		$fileName = 'uid_'.$PA['row']['uid'].'_'.str_replace('/', '_', $PA['row']['doi']);
		

		$path = 'typo3temp/tx_pubdb/'.$fileName.'.xml';
		t3lib_div::writeFile(PATH_site.$path, $xmlStr);
		
		$formField .= '<tr><td class="formField-field" colspan="2" style="padding-top: 10px; padding-bottom: 10px"><span class="class-main14"><a style="text-decoration: underline" href="../'.$path.'">'.
		$GLOBALS['LANG']->sl('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.xml.downloadfile').'</a></span></tr></td>';		

		$hasChildren = array('journal','conference_proceedings','book');
		
		// if pub includes children show also a link to a meta-only xml file
		if (tx_pubdb_utils::typeHasChildren($PA['row']['pubtype'])) {
		  $xmlMetaStr = $xml->entryToXML($PA['row'],1);
		  $path = 'typo3temp/tx_pubdb/'.$fileName.'_meta.xml';	
		  t3lib_div::writeFile(PATH_site.$path, $xmlMetaStr);
		$formField .= '<tr><td class="formField-field" colspan="2" style="padding-top: 10px; padding-bottom: 10px"><span class="class-main14"><a style="text-decoration: underline" href="../'.$path.'">'.
		$GLOBALS['LANG']->sl('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.xml.downloadfileMeta').'</a></span></tr></td>';		
		}


		$formField .= '</tbody></table>';

		return $formField;
	}

}

?>
