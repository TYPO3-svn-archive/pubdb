<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

include_once(t3lib_extMgm::extPath($_EXTKEY).'Classes/class.tx_pubdb_flexform.php');
//include_once(t3lib_extMgm::extPath($_EXTKEY).'class.tx_pubdb_tca.php');
include_once(t3lib_extMgm::extPath($_EXTKEY).'Classes/class.tx_pubdb_compatibility.php');

$TCA['tx_pubdb_data'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data',		
		'label' => 'title',	
		'label_alt' => 'number,issue,year',
		'label_alt_force' => 'true',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY year DESC',	
		'delete' => 'deleted',	
		'filter' => 'filter_for_all_fields',	
		'enablecolumns' => Array (		
			'disabled' => 'hidden',
		),
		'requestUpdate' => 'pubtype',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_pubdb_data.gif',
	),
	'interface' => Array( 
		'always_description' => 'true',
		'showRecordFieldList' => 'title,subtitle,author,coauthors,contributors,publisher,location,year,number,doi,category,hascrossrefentry',
	),		
);

$TCA['tx_pubdb_categories'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_categories',		
		'label' => 'name',	
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY name',	
		'delete' => 'deleted',	
		'enablecolumns' => Array (		
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_pubdb_categories.gif',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'hidden, name, fegroup',
	)
);
$TCA['tx_pubdb_contributors'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_contributors',		
		'label' => 'surname',	
		'label_alt' => 'given_name,affiliation,organization',
		'label_alt_force' => TRUE,
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY surname',
		'requestUpdate' => 'contributor_type',	
		'delete' => 'deleted',	
		'enablecolumns' => Array (		
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_pubdb_contributors.gif',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'name',
	)
);

$TCA['tx_pubdb_pub_contributors'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_pub_contributors',
		'label' => 'uid',
		'label_alt' => 'pubid,contributorid',
		'label_alt_force' => TRUE,		
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',	
		'enablecolumns' => Array (		
			'disabled' => 'hidden',
		),
		//'hideTable' => TRUE,	
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_pubdb_contributors.gif',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'hidden, pubid, contributorid',
	)
);


$tempColumns = Array (
	'tx_pubdb_newslink' => Array (		
		'exclude' => 1,		
		'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_newslink.publication',		
		'config' => Array (
			'type' => 'select',	
			'items' => Array (
				Array('',0),
			),
			'foreign_table' => 'tx_pubdb_data',	
			'foreign_table_where' => 'ORDER BY tx_pubdb_data.uid',	
			'size' => 1,	
			'minitems' => 0,
			'maxitems' => 1,
		)
	),
        'tx_pubdb_link_title' => Array (		
		'exclude' => 1,		
		'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_newslink.linktitle',		
		'config' => Array (
			'type' => 'input',	
			'size' => '30',
		)
	),
);



//tt_news erweitern
t3lib_div::loadTCA('tt_news');
t3lib_extMgm::addTCAcolumns('tt_news', $tempColumns, 1);
t3lib_extMgm::addToAllTCAtypes('tt_news', ',--div--;Publication,tx_pubdb_link_title,tx_pubdb_newslink;;;;1-1-1');


//tt_content muss vor jeder Änderung eines $TCA Beriches im frontend geladen werden
t3lib_div::loadTCA('tt_content');

//nicht benötigte felder ausblenden
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key,pages,recursive';

// Get the typo3 version
$typo3Version = tx_pubdb_compatibility::getInstance()->int_from_ver(TYPO3_version);

//for typo3 > 6.0 
if ($typo3Version >= 6000000) {
		//debug("typo3 6",'debug ver');
		\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
		$_EXTKEY,
		'pi1',
		'Publication database FE plugin');
	$pluginSignature = str_replace('_', '', $_EXTKEY) . '_pi1';
	$TCA['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $_EXTKEY . '/flexform_ds.xml');
 } else {
	//flexform feld einblenden
	$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY . '_pi1']='pi_flexform';
	
	//xml datei laden
	t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:' . $_EXTKEY . '/flexform_ds.xml');
 }

t3lib_extMgm::addPlugin(Array('LLL:EXT:pubdb/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY . '_pi1'), 'list_type');

//t3lib_extMgm::addStaticFile($_EXTKEY,'pi1/static/','Pubdb list');
t3lib_extMgm::addStaticFile($_EXTKEY, 'static/css/', 'default CSS-styles');

//if (TYPO3_MODE=='BE') {
	t3lib_extMgm::addLLrefForTCAdescr('tx_pubdb_data', 'EXT:pubdb/csh/locallang_csh_data.xml');
	t3lib_extMgm::addLLrefForTCAdescr('tx_pubdb_data', 'EXT:pubdb/csh/locallang_csh_contributors.xml');
//}
?>
