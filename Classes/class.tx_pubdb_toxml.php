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


require_once(PATH_tslib.'class.tslib_pibase.php');
//require_once('t3lib/class.t3lib_div.php');

class tx_pubdb_toxml {

		var $confArray;
		var $doiUrl;

function __construct() {
	
	// Get the extension configuration
	$this->confArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['pubdb']);

	// Get the doi base url
	$this->doiUrl = $this->confArray['doi.']['url'];

	// add a slash if necessary
	if (substr($this->doiUrl, -1) !== "/") {
	   $this->doiUrl .= "/";
	}
		


}

/**
*
*   Generates a crossref XML representation of the publication given
*
*/
function entryToXML($pub, $metaOnly=0) {
		//debug($pub);
    		$domtree = new DOMDocument('1.0', 'UTF-8');
		$domtree->preserveWhiteSpace = false;
		$domtree->formatOutput = true;

		$doi_batch=$domtree->createElementNS('http://www.crossref.org/schema/4.3.0','doi_batch');
		$version = $domtree->createAttribute('version');
		$version->value='4.3.0';
		$doi_batch->setAttributeNS('http://www.w3.org/2000/xmlns/','xmlns:xsi','http://www.w3.org/2001/XMLSchema-instance');
		$doi_batch->setAttributeNS('http://www.w3.org/2000/xmlns/','xmlns:mml','http://www.w3.org/1998/Math/MathML');
		$doi_batch->setAttribute('xsi:schemaLocation','http://www.crossref.org/schema/4.3.0 http://www.crossref.org/schema/deposit/crossref4.3.0.xsd');
		$doi_batch->appendChild($version);
		$domtree->appendChild($doi_batch);

		// head section
		$head=$domtree->createElement('head');
		$date = new DateTime();
		$head->appendChild($domtree->createElement('doi_batch_id',$date->getTimeStamp()));
		$head->appendChild($domtree->createElement('timestamp',$date->getTimeStamp()));
		$depositor = $domtree->createElement('depositor');

		$depositor->appendChild($domtree->createElement('name',$this->confArray['crossref.']['depositor']));
		$depositor->appendChild($domtree->createElement('email_address',$this->confArray['crossref.']['depositor_email']));
		$head->appendChild($depositor);
		$head->appendChild($domtree->createElement('registrant',$this->confArray['crossref.']['registrant']));


		$doi_batch->appendChild($head);

		// body section
		$body = $domtree->createElement('body');
		if ($pub['pubtype'] === 'journal') {
		  $journal = $this->createJournalEntry($pub, $domtree);	

		  // If false, create also child publications (articles, etc..)
		  if ($metaOnly === 0) {
		  	  $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_pubdb_data','parent_pubid='.$pub['uid']);
			  while ($article = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) 	
			  $journal->appendChild($this->createJournal_ArticleEntry($article, $domtree, $pub));
	 	  }	
		  $body->appendChild($journal);

		}
		
		if ($pub['pubtype'] === 'conference') {
		  $body->appendChild($this->createConferenceEntry($pub,$domtree));		
		}	


		if ($pub['pubtype'] === 'conference_proceedings') {
		    $conference = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_pubdb_data','uid='.$pub['parent_pubid']));
		    $conference_meta = $this->createConferenceEntry($conference, $domtree);
		    $proceedings_meta = $this->createConferenceProceedingsEntry($pub, $domtree);
		    $conference_meta->appendChild($proceedings_meta);

		     // If false, create also child publications (articles, etc..)
		    if ($metaOnly === 0) {
		  	  $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_pubdb_data','parent_pubid='.$pub['uid']);
			  while ($article = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) 	
			  $conference_meta->appendChild($this->createConferencePaperEntry($article, $domtree));
	 	    }		

		    $body -> appendChild($conference_meta);	

		}

		if ($pub['pubtype'] === 'conference_paper') {
		    $proceedings = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_pubdb_data','uid='.$pub['parent_pubid']));
		    $conference = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_pubdb_data','uid='.$proceedings['parent_pubid']));	
		    $conference_meta = $this->createConferenceEntry($conference, $domtree);
		    $proceedings_meta = $this->createConferenceProceedingsEntry($proceedings, $domtree);
		    $paper = $this->createConferencePaperEntry($pub, $domtree);
		    $conference_meta->appendChild($proceedings_meta);
		    $conference_meta->appendChild($paper);
		    $body -> appendChild($conference_meta);	

		}

                if ($pub['pubtype'] === 'journal_article') {
		    $journalOfPub = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_pubdb_data','uid='.$pub['parent_pubid']));
	            $journal = $this->createJournalEntry($journalOfPub, $domtree);
		    $journal->appendChild($this->createJournal_ArticleEntry($pub, $domtree, $journalOfPub));
		    $body->appendChild($journal);
		}		 	

		if ($pub['pubtype'] === 'book') {
		   $book=$this->createBookEntry($pub,$domtree);
  	  	   // If false, create also child publications (articles, etc..)
		    if ($metaOnly === 0) {
		  	  $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_pubdb_data','parent_pubid='.$pub['uid']);
			  while ($chapter = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) 	
			   $book->appendChild($this->createContentItemEntry($chapter, $domtree));
	 	    }		
		   $body->appendChild($book);

		}

		if ($pub['pubtype'] === 'book_chapter') {
  		    $bookOfPub = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_pubdb_data','uid='.$pub['parent_pubid']));
	            $book = $this->createBookEntry($bookOfPub, $domtree);
		    $book->appendChild($this->createContentItemEntry($pub, $domtree));
		    $body->appendChild($book);
		}

		if ($pub['pubtype'] === 'report_paper') {
		   $body->appendChild($this->createReportEntry($pub, $domtree));
			
		}		

	
		$doi_batch->appendChild($body);

		return $domtree->saveXML();
	
	}

	
	function createReportEntry($pub, $domtree) {
		$report = $domtree->createElement('report-paper');
		$report->appendChild($this->createContributorsEntry($pub['uid'], $domtree));
		return $report;
	}
	

	function createJournalEntry($pub, $domtree) {
		$journal = $domtree->createElement('journal');
		$journal_meta = $domtree->createElement('journal_metadata');
		$journal_meta->appendChild($domtree->createElement('full_title',$pub['title']));
		
		// abbrev title is optional, but may not be empty, if set		
		if (isset($pub['abbrev_title']) && strlen($pub['abbrev_title']) > 0) { 				
			$journal_meta->appendChild($domtree->createElement('abbrev_title',$pub['abbrev_title']));
		}

		if ($this->isTextValid($pub['isbn'])) {		
			$isbn = $domtree->createElement('issn',$pub['isbn']);
			$isbn->setAttribute('media_type','print');
			$journal_meta->appendChild($isbn);
		}

		if ($this->isTextValid($pub['isbn2'])) {		
			$isbn = $domtree->createElement('issn',$pub['isbn2']);
			$isbn->setAttribute('media_type','electronic');
			$journal_meta->appendChild($isbn);
		}

		$journal_issue = $domtree->createElement('journal_issue');
		$publication_date = $domtree->createElement('publication_date');	
		$publication_date->appendChild($domtree->createElement('year',$pub['year']));
		$journal_issue->appendChild($publication_date);

		if (isset($pub['number']) && strlen($pub['number']) > 0) {
			$journal_volume = $domtree->createElement('journal_volume');
			$journal_volume->appendChild($domtree->createElement('volume',$pub['number']));
			$journal_issue->appendChild($journal_volume);
		}

		if (isset($pub['issue']) && strlen($pub['issue']) > 0)
			$journal_issue->appendChild($domtree->createElement('issue', $pub['issue']));
		
		if ($this->isTextValid($pub['doi'], 6)) {
			$doidata = $this->createDoiData($pub['doi'],$pub['uid'],$domtree);
		
			// If journal has no issue, add DOI to volume, if no volume, add to metadata
			if (isset($pub['issue']) && strlen($pub['issue']) > 0)  {
				$journal_issue->appendChild($doidata);
			} elseif (isset($pub['number']) && strlen($pub['number']) > 0) {
				$journal_volume->appendChild($doidata); 	
			} else {		
				$journal_meta->appendChild($doidata);
			}
		}

		$journal->appendChild($journal_meta);
		$journal->appendChild($journal_issue);
		return($journal);

	}

	function createConferenceEntry($pub, $domtree) {
		$conference = $domtree->createElement('conference');
		
		$conference->appendChild($this->createContributorsEntry($pub['uid'],$domtree));
		
		// add metadata
		$meta = $domtree->createElement('event_metadata');
		$meta->appendChild($domtree->createElement('conference_name', $pub['title']));
		if (isset($pub['theme']) && strlen($pub['theme']) > 0) $meta->appendChild($domtree->createElement('conference_theme', $pub['theme']));
		if (isset($pub['abbrev_title']) && strlen($pub['abbrev_title']) > 0) $meta->appendChild($domtree->createElement('conference_acronym', $pub['abbrev_title']));
		if (isset($pub['sponsors']) && 	strlen($pub['sponsors']) > 0) {
			foreach (explode(';',$pub['sponsors']) as $sponsor) {
			   $meta->appendChild($domtree->createElement('conference_sponsor',$sponsor));
			}
		}
		if (isset($pub['number']) && 	strlen($pub['number']) > 0) $meta->appendChild($domtree->createElement('conference_number',$pub['number']));
		if (isset($pub['location']) && 	strlen($pub['location']) > 0) $meta->appendChild($domtree->createElement('conference_location',$pub['location']));
		if (isset($pub['startdate']) && strlen($pub['startdate']) > 0) {
			$date = $domtree->createElement('conference_date');
			$startdate = getdate($pub['startdate']);
			$enddate = getdate($pub['enddate']);
			//debug($startdate);
			$date->setAttribute('start_day',$startdate['mday']);
			$date->setAttribute('start_month',$startdate['mon']);
			$date->setAttribute('start_year',$startdate['year']);
			$date->setAttribute('end_day',$enddate['mday']);
			$date->setAttribute('end_month',$enddate['mon']);
			$date->setAttribute('end_year',$enddate['year']);
			$meta->appendChild($date);
		}
		$conference->appendChild($meta);
		return $conference;
	}


	function createConferenceProceedingsEntry($pub, $domtree) {
		$proceedings = $domtree->createElement('proceedings_metadata');
		$proceedings->appendChild($domtree->createElement('proceedings_title',$pub['title']));
		if (isset($pub['theme']) && strlen($pub['theme']) > 0) $proceedings->appendChild($domtree->createElement('proceedings_subject',$pub['theme']));
		if ($this->isTextValid($pub['publisher']))  $proceedings->appendChild($this->createPublisherData($pub['publisher'], $pub['location'], $domtree));

		if (isset($pub['year']) && strlen($pub['year']) > 0) {
		  $date = $domtree->createElement('publication_date');
		  $date->appendChild($domtree->createElement('year', $pub['year']));
	   	  $proceedings->appendChild($date);
		}
		if (isset($pub['isbn']) && strlen($pub['isbn']) > 0) $proceedings->appendChild($domtree->createElement('isbn',$pub['isbn']));
		if (isset($pub['doi']) && strlen($pub['doi']) >= 6) $proceedings->appendChild($this->createDoiData($pub['doi'],$pub['uid'],$domtree)); 
		return $proceedings;

	}


	/**
	*   Creates the contributors entry
	*  @param contributors: comma separated contributor ids
	*  @param domtree: reference to the document tree element
	**/
	function createContributorsEntry($pubid, $domtree) {

								

		$contributors = $domtree->createElement('contributors');
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'tx_pubdb_pub_contributors.contributorid,tx_pubdb_pub_contributors.pubid,tx_pubdb_pub_contributors.deleted,tx_pubdb_pub_contributors.role,
			tx_pubdb_pub_contributors.pubsort,tx_pubdb_contributors.*',
			'tx_pubdb_contributors JOIN tx_pubdb_pub_contributors ON tx_pubdb_contributors.uid=tx_pubdb_pub_contributors.contributorid',
			'tx_pubdb_pub_contributors.pubid='.$pubid.' AND tx_pubdb_pub_contributors.deleted = 0','','tx_pubdb_pub_contributors.pubsort','');

		$isfirst = true;
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
			//debug($row);
			if ($row['contributor_type'] === 'organization') {
			    $item = $domtree->createElement('organization',$row['organization']);
			} else {  
			    $item = $domtree->createElement('person_name');
			    if (isset($row['given_name']) && strlen($row['given_name']) > 0) $item->appendChild($domtree->createElement('given_name',$row['given_name']));
  			    if (isset($row['surname']) && strlen($row['surname']) > 0) $item->appendChild($domtree->createElement('surname',$row['surname']));
			    if (isset($row['suffix']) && strlen($row['suffix']) > 0) $item->appendChild($domtree->createElement('suffix',$row['suffix']));
			    if (isset($row['affiliation']) && strlen($row['affiliation']) > 0) { 
					$affiliations = explode("\n",$row['affiliation']);
					foreach ($affiliations as $a) {
						$trimmed = trim($a);
						if ($this->isTextValid($trimmed)) 
							$item->appendChild($domtree->createElement('affiliation',$trimmed));
					}
			    }
			    if (isset($row['orcid']) && strlen($row['orcid']) > 0) $item->appendChild($domtree->createElement('orcid',$row['orcid']));
			}

			$item->setAttribute('contributor_role',$row['role']);
			if ($isfirst) { 
	 			$item->setAttribute('sequence','first');
				$isfirst = false;
			} else {
				$item->setAttribute('sequence','additional');
			}
			$contributors->appendChild($item);
		}
		return $contributors;
	}


	
	function createJournal_ArticleEntry($pub, $domtree, $journalOfPub) {
		$article = $domtree->createElement('journal_article');
		$type = $domtree->createAttribute('publication_type');
		$type->value=$pub['subpubtype'];
		$article->appendChild($type);
		$titles = $domtree->createElement('titles');
		$titles->appendChild($domtree->createElement('title',$pub['title']));
		$titles->appendChild($domtree->createElement('subtitle',$pub['subtitle']));
		$article->appendChild($titles);

		$contributors = $domtree->createElement('contributors');
		$isfirst = true;
		foreach($this->parseAuthors($pub['coauthors']) as $el) {
		     $author = $domtree->createElement('person_name');
  		     if ($isfirst) {	
			     $author->setAttribute('sequence','first');
			     $isfirst = false;
		     } else {
   			     $author->setAttribute('sequence','additional');
			}
		     $author->setAttribute('contributor_role','author');	
		     $author->appendChild($domtree->createElement('given_name',$el['given_name']));
		     $author->appendChild($domtree->createElement('surname',$el['surname']));
		     $contributors->appendChild($author);
		}
		 
		$article->appendChild($contributors);
		
		$publicationdate = $domtree->createElement('publication_date');
		$publicationdate->appendChild($domtree->createElement('year',$journalOfPub['year']));
		$article->appendChild($publicationdate);
		
		$article->appendChild($this->createDoiData($pub['doi'],$pub['uid'], $domtree));

		return($article);

	}


	function createConferencePaperEntry($pub, $domtree) {
		$article = $domtree->createElement('conference_paper');
		$article->setAttribute('publication_type',$pub['subpubtype']);
		
		$contributors = $domtree->createElement('contributors');
		$isfirst = true;
		foreach($this->parseAuthors($pub['coauthors']) as $el) {
		     $author = $domtree->createElement('person_name');
  		     if ($isfirst) {	
			     $author->setAttribute('sequence','first');
			     $isfirst = false;
		     } else {
   			     $author->setAttribute('sequence','additional');
			}
		     $author->setAttribute('contributor_role','author');	
		     $author->appendChild($domtree->createElement('given_name',$el['given_name']));
		     $author->appendChild($domtree->createElement('surname',$el['surname']));
		     $contributors->appendChild($author);
		}
		 
		$article->appendChild($contributors);

		$titles = $domtree->createElement('titles');
		$titles->appendChild($domtree->createElement('title',$pub['title']));
		if (isset($pub['subtitle']) && strlen($pub['subtitle']) > 0) $titles->appendChild($domtree->createElement('subtitle',$pub['subtitle']));
		$article->appendChild($titles);


		if (isset($pub['year']) && $pub['year'] > 1000) {
			$publicationdate = $domtree->createElement('publication_date');
			$publicationdate->appendChild($domtree->createElement('year',$journalOfPub['year']));
			$article->appendChild($publicationdate);
		}
		
		if ($this->isTextValid($pub['pages'])) $article->appendChild($this->createPagesData($pub['pages'], $domtree));	

		$article->appendChild($this->createDoiData($pub['doi'],$pub['uid'], $domtree));
		return($article);

	}

	function createBookEntry($pub, $domtree) {
		$book = $domtree->createElement('book');
		$book->setAttribute('book_type',$pub['booktype']);
		
		if ($this->isNumberValid($pub['number'])) {
		   $book->appendChild($domtree->createElement('error','Book series not yet supported'));
		} else {
		   $book->appendChild($this->createBookMetaData($pub,$domtree));
		}

		return $book;
	}

		
	function createContentItemEntry($pub, $domtree) {
		$item = $domtree->createElement('content_item');
		$item->appendChild($this->createContributorsEntry($pub['uid'],$domtree));
		if ($this->isTextValid($pub['title'])) $item->appendChild($this->createTitlesData($pub['title'],$pub['subtitle'],$domtree));
		if ($this->isTextValid($pub['pages'])) $item->appendChild($this->createPagesData($pub['pages'],$domtree));
		if ($this->isTextValid($pub['doi'],6)) $item->appendChild($this->createDoiData($pub['doi'],$pub['uid'],$domtree));
		return $item;
	}



	function createBookMetaData($pub, $domtree) {
		$meta = $domtree->createElement('book_metadata');

		if ($this->isTextValid($pub['contributors'])) $meta->appendChild($this->createContributorsEntry($pub['uid'],$domtree));
		$meta->appendChild($this->createTitlesData($pub['title'], $pub['subtitle'], $domtree));
		
		if ($this->isNumberValid($pub['edition'])) $meta->appendChild($domtree->createElement('edition_number',$pub['edition']));
		
		$publication_date = $domtree->createElement('publication_date');
		$publication_date->appendChild($domtree->createElement('year',$pub['year']));
		$meta->appendChild($publication_date); 
		
		if ($this->isTextValid($pub['isbn'])) {
		  $isbn = $domtree->createElement('isbn',$pub['isbn']);
		  $isbn->setAttribute('media_type','print');
		  $meta->appendChild($isbn);
		}
	
		
		if ($this->isTextValid($pub['isbn2'])) {
		  $isbn = $domtree->createElement('isbn',$pub['isbn2']);
		  $isbn->setAttribute('media_type','electronic');
		  $meta->appendChild($isbn);
		}

		$meta->appendChild($this->createPublisherData($pub['publisher'],$pub['location'],$domtree));
		
		if ($this->isTextValid($pub['doi'],6)) $meta->appendChild($this->createDoiData($pub['doi'], $domtree));

		return $meta;
		
	}

	/*function createBookSeriesMetaData($pub, $domtree) {
		$meta = $domtree->createElement('book_series_metadata');
		$meta = 

		if ($this->isTextValid($pub['contributors'])) $meta->appendChild($this->createContributorsEntry($pub['contributors'],$domtree));
		$meta->appendChild($this->createTitlesData($pub['title'], $pub['subtitle'], $domtree));
		
		if ($this->isNumberValid($pub['edition'])) $meta->appendChild($domtree->createElement('edition_number',$pub['edition']));
		
		$publication_date = $domtree->createElement('publication_date');
		$publication_date->appendChild($domtree->createElement('year',$pub['year']));
		$meta->appendChild($publication_date); 
		
		if ($this->isTextValid($pub['isbn'])) {
		  $isbn = $domtree->createElement('isbn',$pub['isbn']);
		  $isbn->setAttribute('media_type','print');
		  $meta->appendChild($isbn);
		}
	
		
		if ($this->isTextValid($pub['isbn2'])) {
		  $isbn = $domtree->createElement('isbn',$pub['isbn2']);
		  $isbn->setAttribute('media_type','electronic');
		  $meta->appendChild($isbn);
		}

		$meta->appendChild($this->createPublisherData($pub['publisher'],$pub['location'],$domtree));
		
		if ($this->isTextValid($pub['doi'],6)) $meta->appendChild($this->createDoiData($pub['doi'], $domtree));

		return $meta;
		
	}
*/


	/**
	* Parse a ; separated list of full names
	* return: array of the form [index][surname,given_name]
	*/
	function parseAuthors($coauthors) {
	
		
		$authors = explode(';', $coauthors);
		$i = 0;
		foreach( $authors as $author) {
			$res[$i] = $this->parseFullname(trim($author));
			$i++;
		}
		return $res;
	}


	/**
	*	Parse a full name, surname and given name(s) separated by , or SPACE
	*	return: array [surname,given_name]
	*/
	function parseFullname($fullname) {
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

	function createDoiData($doi, $uid, $domtree) {
		$doidata = $domtree->createElement('doi_data');
		$doidata->appendChild($domtree->createElement('doi',$doi));
		$doidata->appendChild($domtree->createElement('resource',$this->doiUrl.$uid));
	return $doidata;
	}	

	function createPagesData($pages, $domtree) {
	   
		$pages_entry = $domtree->createElement('pages');
		$elements = explode('-', trim($pages));
	        if (isset($elements[0])) $pages_entry->appendChild($domtree->createElement('first_page',$elements[0]));
	        if (isset($elements[1])) $pages_entry->appendChild($domtree->createElement('last_page',$elements[1]));
  		return $pages_entry;

	}

	function createTitlesData($title, $subtitle, $domtree) {
		$titles = $domtree->createElement('titles');
		$titles->appendChild($domtree->createElement('title',$title));
		if ($this->isTextValid($subtitle)) $titles->appendChild($domtree->createElement('subtitle',$title));
		return $titles;
	}

	function createPublisherData($publisher, $location, $domtree) {
		$el = $domtree->createElement('publisher');
		$el->appendChild($domtree->createElement('publisher_name',$publisher));
		if ($this->isTextValid($location)) $el->appendChild($domtree->createElement('publisher_place',$location));
		return $el;
	}


	function isTextValid($text,$minlen=1) {
	    return (isset($text) && strlen($text) >= $minlen);
	}	


	function isNumberValid($number) {
	 	return (isset($number) && $number > 0);
	}
}



?>
