<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "pubdb".
 *
 * Auto generated 15-01-2014 19:30
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
	'title' => 'pubdb',
	'description' => 'Publication database, especially designed to manage, store and display scientific publications. Provides list and single views in the FE with customizable templates and export of XML meta data for the CrossRef DOI database',
	'category' => 'plugin',
	'author' => 'Johannes Kropf',
	'author_email' => 'johannes@kropf.at',
	'shy' => '',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 1,
	'createDirs' => 'fileadmin/user_upload/tx_pubdb,typo3temp/tx_pubdb',
	'modify_tables' => 'tt_news',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '1.1.3',
	'constraints' => 
	array (
		'depends' => 
		array (
			'php' => '5.3.0-5.5.99',
			'typo3' => '4.7.0-6.1.99',
		),
		'conflicts' => 
		array (
		),
		'suggests' => 
		array (
		),
	),
	'_md5_values_when_last_written' => 'a:31:{s:9:"ChangeLog";s:4:"ca3f";s:22:"class.tx_pubdb_tca.php";s:4:"f26e";s:21:"ext_conf_template.txt";s:4:"e462";s:12:"ext_icon.gif";s:4:"6833";s:17:"ext_localconf.php";s:4:"bc97";s:14:"ext_tables.php";s:4:"80ca";s:14:"ext_tables.sql";s:4:"a62b";s:15:"flexform_ds.xml";s:4:"41d1";s:24:"icon_tx_pubdb_cat_mm.gif";s:4:"475a";s:28:"icon_tx_pubdb_categories.gif";s:4:"8fb8";s:30:"icon_tx_pubdb_contributors.gif";s:4:"9e10";s:22:"icon_tx_pubdb_data.gif";s:4:"4ab9";s:16:"locallang_db.xml";s:4:"d35a";s:10:"README.txt";s:4:"6358";s:7:"tca.php";s:4:"18ca";s:34:"csh/locallang_csh_contributors.xml";s:4:"c292";s:26:"csh/locallang_csh_data.xml";s:4:"6195";s:14:"doc/manual.sxw";s:4:"aaa3";s:19:"doc/wizard_form.dat";s:4:"671a";s:20:"doc/wizard_form.html";s:4:"1496";s:26:"language/template_conf.xml";s:4:"f302";s:31:"lib/class.tx_pubdb_flexform.php";s:4:"e8c0";s:28:"lib/class.tx_pubdb_toxml.php";s:4:"a7c0";s:28:"lib/class.tx_pubdb_utils.php";s:4:"206a";s:31:"pi1/class.tx_pubdb_dbaccess.php";s:4:"8b6d";s:26:"pi1/class.tx_pubdb_pi1.php";s:4:"6468";s:17:"pi1/locallang.xml";s:4:"b562";s:17:"pi1/template.tmpl";s:4:"4a94";s:24:"pi1/static/editorcfg.txt";s:4:"6ac4";s:20:"static/css/setup.txt";s:4:"8834";s:26:"sv1/class.tx_pubdb_sv1.php";s:4:"cb2a";}',
	'suggests' => 
	array (
	),
);

?>
