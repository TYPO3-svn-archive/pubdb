<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_pubdb_data=1
');

// Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY, 'editorcfg', 
		'tt_content.CSS_editor.ch.tx_pubdb_pi1 = < plugin.tx_pubdb_pi1.CSS_editor', 43);

t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_pubdb_pi1.php', '_pi1', 'list_type', 1);

if (TYPO3_MODE!='BE')	{
require_once(t3lib_extMgm::extPath('pubdb').'pi1/class.tx_pubdb_pi1.php');
}

// Define hook for the tt_news plugin
$TYPO3_CONF_VARS['EXTCONF']['tt_news']['extraItemMarkerHook'][] = 'tx_pubdb_pi1'; 

// Define parsing rule for realUrl plugin
$TYPO3_CONF_VARS['EXTCONF']['realurl']['_DEFAULT']['postVarSets']['_DEFAULT']['doi'] = array(array('GETvar'=>'tx_pubdb_pi1[doi]'),
											);

include_once(t3lib_extMgm::extPath($_EXTKEY).'class.tx_pubdb_tca.php');


//$TYPO3_CONF_VARS['EXTCONF']['tt_news']['extraCodesHook'][] = 'tx_mblnewsevent'; 

// Initialize GLOBALS['LANG']

if (t3lib_div::compat_version('6.0')) {
  require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('lang') . 'Classes/LanguageService.php';
} else {
  require_once (PATH_site . 'typo3/sysext/lang/lang.php');
}


if (!isset($GLOBALS['LANG'])) 
	$GLOBALS['LANG'] = t3lib_div::makeInstance('language'); 

?>
