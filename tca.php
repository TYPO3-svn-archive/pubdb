<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['tx_pubdb_data'] = Array (
	'ctrl' => $TCA['tx_pubdb_data']['ctrl'],
	'interface' => $TCA['tx_pubdb_data']['interface'],
	'feInterface' => Array (
		'fe_admin_fieldList' => 'hidden, author,coauthors, title, subtitle, publisher, location, year, number, abstract, file, category,hashardcopy,price'),
	'columns' => Array (
		'hidden' => Array (		
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'author' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.author',
			'displayCond' => 'FIELD:pubtype:IN:journal_article,conference_paper,report_paper,journal',		
			'config' => Array (
				'type' => 'input',	
				'size' => '30',	
				'max' => '255',
			),
			'config_filter' => Array (
				'type' => 'input',
				'size' => '10',
				'max' => '30',
				'eval' => 'trim',
			)
		),
		'author_email' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.authorEmail',	
			'displayCond' => 'FIELD:pubtype:IN:journal_article,conference_paper',	
			'config' => Array (
				'type' => 'input',	
				'size' => '30',	
				'max' => '255',
			)
		),
		'author_address' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.authorAddress',	
			'displayCond' => 'FIELD:pubtype:IN:journal_article,conference_paper',	
			'config' => Array (
				'type' => 'text',	
				'rows' => '3',	
				'cols' => '30',
			)
		),
		'coauthors' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.coauthors',	
			'displayCond' => 'FIELD:pubtype:IN:ournal_article,conference_paper',	
			'config' => Array (
				'type' => 'input',	
				'size' => '50',	
				'max' => '255',
			)
		),
		'title' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.title',	
			'config' => Array (
				'type' => 'input',	
				'size' => '50',	
				'max' => '255',
			),
			'config_filter' => Array (
				'type' => 'input',
				'size' => '10',
				'max' => '20',
				'eval' => 'trim',
			)
		),
		'abbrev_title' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.abbrev_title',	
			'displayCond' => 'FIELD:pubtype:IN:journal,conference',	
			'config' => Array (
				'type' => 'input',	
				'size' => '30',	
				'max' => '255',
			),
		),
		'subtitle' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.subtitle',		
			'displayCond' => 'FIELD:pubtype:IN:journal,book,book_chapter,journal_article,conference_paper,report_paper',
			'config' => Array (
				'type' => 'input',	
				'size' => '50',	
				'max' => '255',
			)
		),
		'theme' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.theme',		
			'displayCond' => 'FIELD:pubtype:IN:conference,conference_proceedings',
			'config' => Array (
				'type' => 'input',	
				'size' => '50',	
				'max' => '255',
			)
		),
		'publisher' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.publisher',		
			'displayCond' => 'FIELD:pubtype:!IN:conference,book_chapter',
			'config' => Array (
				'type' => 'input',	
				'size' => '30',	
				'max' => '255',
			)
		),
		'pubtype' => Array (
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.pubType',		
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.pubType.2', 'journal'),
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.pubType.1', 'journal_article'),
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.pubType.11', 'conference'),
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.pubType.12', 'conference_proceedings'),
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.pubType.3', 'conference_paper'),
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.pubType.4', 'book'),
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.pubType.13', 'book_chapter'),
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.pubType.6', 'dissertation'),
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.pubType.7', 'report_paper'),
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.pubType.8', 'standard'),
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.pubType.9', 'database'),
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.pubType.5', 'content_item'),
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.pubType.10', 'sa_component'),
					
				),
				'default' => 'journal',
			)

		),
		'subpubtype' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.subpubType',	
			'displayCond' => 'FIELD:pubtype:IN:journal_article,conference_paper,report_paper',
                         'config' => Array(
				'type' => 'select',
			 	'items' => Array(
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.subpubType.abstract','abstract_only'),
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.subpubType.fulltext','full_text'),
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.subpubType.bibliographicrecord','bibliographic_record'),
				)
			)
		),
		'booktype' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.booktype',	
			'displayCond' => 'FIELD:pubtype:IN:book',
                         'config' => Array(
				'type' => 'select',
			 	'items' => Array(
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.booktype.edited_book','edited_book'),
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.booktype.monograph','monograph'),
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.booktype.reference','reference'),
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.booktype.other','other'),
				)
			)
		),
		'parent_pubid' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.parent'.$TCA['tx_pubdb_data']['columns']['pubtype'],	
			'displayCond' => 'FIELD:pubtype:IN:journal_article,conference_proceedings,conference_paper,book_chapter',
			'config' => Array(
				'type' => 'select',
				'multiple' => '1',
				'itemsProcFunc' => 'tx_pubdb_flexform->extendedParentList',
				'items' => array(),
			),
		),
		'location' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.location',
			'displayCond' => 'FIELD:pubtype:IN:conference,journal,book',			
			'config' => Array (
				'type' => 'input',	
				'size' => '30',	
				'max' => '255',
			)
		),
		'year' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.year',	
			'displayCond' => 'FIELD:pubtype:IN:journal,book,conference_proceedings,report_paper',	
			'config' => Array (
				'type' => 'input',
				'size' => '4',
				'max' => '4',
				'eval' => 'int',
				'range' => Array (
					'upper' => '3000',
					'lower' => '1000'
				),
			'default' => 0
			),
			'config_filter' => Array (
				'type' => 'input',
				'size' => '4',
				'max' => '4',
				'eval' => 'trim',
			)
		),
		'isbn' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.isbn',		
			'displayCond' => 'FIELD:pubtype:IN:journal,book,conference_proceedings,report_paper',
			'config' => Array (
				'type' => 'input',	
				'size' => '30',
                                'max' => '30'
			)
		),
		'isbn2' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.isbn2',		
			'displayCond' => 'FIELD:pubtype:IN:journal,book,conference_proceedings,report_paper',
			'config' => Array (
				'type' => 'input',	
				'size' => '30',
                                'max' => '30'
			)
		),
		'doi' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.doi',	
			'displayCond' => 'FIELD:pubtype:!IN:conference',	
			'config' => Array (
				'type' => 'input',	
				'size' => '30',
                                'max' => '30'
			),
			'config_filter' => Array (
				'type' => 'input',	
				'size' => '10',
                                'max' => '20',
				'eval' => 'trim',
			)
		),
		/*'series' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.series',	
			'displayCond' => 'FIELD:pubtype:IN:book',	
			'config' => Array (
				'type' => 'input',	
				'size' => '30',
                                'max' => '255'
			)
		),*/
		'edition' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.edition',
			'displayCond' => 'FIELD:pubtype:IN:book,report_paper',		
			'config' => Array (
				'type' => 'input',	
				'size' => '10',
                                'max' => '10'
			)
		),
		'number' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.volume',
			'displayCond' => 'FIELD:pubtype:IN:journal,book',		
			'config' => Array (
				'type' => 'input',	
				'size' => '10',
                                'max' => '10'
			)
		),
		'issue' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.issue',		
			'displayCond' => 'FIELD:pubtype:IN:journal',
			'config' => Array (
				'type' => 'input',	
				'size' => '30',
                                'max' => '30'
			)
		),
		'pages' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.pages',		
			'displayCond' => 'FIELD:pubtype:IN:conference_paper,journal_article,book_chapter',
			'config' => Array (
				'type' => 'input',	
				'size' => '30',
                                'max' => '30'
			)
		),
		'internalNumber' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.internalNumber',
			'displayCond' => 'FIELD:pubtype:!IN:conference',				
			'config' => Array (
				'type' => 'input',	
				'size' => '4',
                                'max' => '10'
			)
		),
		'abstract' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.abstract',	
			'displayCond' => 'FIELD:pubtype:!IN:conference,conference_proceedings',	
			'config' => Array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
				'wizards' => Array(
					'_PADDING' => 2,
					'RTE' => Array(
						'notNewRecords' => 1,
						'RTEonly' => 1,
						'type' => 'script',
						'title' => 'Full screen Rich Text Editing|Formatteret redigering i hele vinduet',
						'icon' => 'wizard_rte2.gif',
						'script' => 'wizard_rte.php',
					),
				),
			)
		),
		'file' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.file',	
			'displayCond' => 'FIELD:pubtype:!IN:conference',	
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => 'pdf,eps,ps,doc,zip,tar,gz,iso',	
				'disallowed' => 'php,php3,php5,exe,com,bat,sh',	
				'max_size' => 200000,	
				'uploadfolder' => 'fileadmin/user_upload/tx_pubdb',
				'size' => 5,	
				'minitems' => 0,
				'maxitems' => 5,
			)
		),
		'fileType' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.fileType',	
			'displayCond' => 'FIELD:pubtype:!IN:conference',	
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.fileType.1', 1),
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.fileType.2', 2),
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.fileType.3', 3),
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.fileType.4', 4),
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.fileType.5', 5),
				),
				'default' => 0
			)
		),
		'openFile' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.openFile',	
			'displayCond' => 'FIELD:pubtype:!IN:conference',	
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => 'pdf,ps,eps,doc,zip,tar,gz',	
				'disallowed' => 'php,php3,php5,exe,com,bat,sh',	
				'max_size' => 200000,	
				'uploadfolder' => 'fileadmin/user_upload/tx_pubdb',
				'size' => 5,	
				'minitems' => 0,
				'maxitems' => 5,
			)
		),
		'openFileType' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.fileType',
			'displayCond' => 'FIELD:pubtype:!IN:conference',		
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.fileType.1', 1),
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.fileType.2', 2),
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.fileType.3', 3),
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.fileType.4', 4),
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.fileType.5', 5),
				),
				'default' => 0
			)
		),
		'category' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.category',		
			'config' => Array (
				'type' => 'group',	
				'internal_type' => 'db',	
				'allowed' => 'tx_pubdb_categories',	
				'size' => 5,	
				'minitems' => 0,
				'maxitems' => 20,	
				'MM' => 'tx_pubdb_data_category_mm',
			),
			'config_filter' => Array ( 
 			'items' => array(
                        array(' --- Alle --- ','')
                                ),
				'type' => 'select',
				'size' => '1',
				'minitems' => 0,
				'maxitems' => 1,
				'internal_type' => 'db',
				'foreign_table' => 'tx_pubdb_categories',
				'MM' => 'tx_pubdb_data_category_mm',
			)
		),
		'hashardcopy' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.hashardcopy',		
			'displayCond' => 'FIELD:pubtype:!IN:conference',
			'config' => Array (
				'type' => 'check',	
				'default' => '0'
			)
		),
		'price' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.price',		
			'displayCond' => 'FIELD:pubtype:!IN:conference',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				
			)
		),
               'reducedprice' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.reducedprice',	
			'displayCond' => 'FIELD:pubtype:!IN:conference',	
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				
			)
		),
		'hascrossrefentry' => Array (		
			'exclude' => 1,				   
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.hascrossrefentry',		
			'config' => Array (
				'type' => 'check',	
				'default' => '0'
			)
		),
		'xml' => Array (		
			'exclude' => 1,	
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.xml', 	
			'displayCond' => 'FIELD:pubtype:!IN:dissertation,database,standard',	
			'config' => Array (
				'type' => 'user',			
				'rows' => '8',	
				'cols' => '80',	
				'userFunc' => 'tx_pubdb_flexform->xmlField',
				'noTableWrapping' => 'false',
				'parameters' => Array (
					'rows' => '15',	
					'cols' => '80',	
					'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.xml',	
					'hint'  => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.xmlhint',
				)

			)
		),
		'startdate' => Array(
			'exclude' => 1,				   
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.startdate',
			'displayCond' => 'FIELD:pubtype:IN:conference',		
			'config' => Array (
				'type' => 'input',
				'size' => '10',	
				'eval' => 'date',
			)
		),
		'enddate' => Array(
			'exclude' => 1,				   
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.enddate',
			'displayCond' => 'FIELD:pubtype:IN:conference',		
			'config' => Array (
				'type' => 'input',	
				'size' => '10',	
				'eval' => 'date',
			)
		),
		'contributors' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_contributors',
			'displayCond' => 'FIELD:pubtype:IN:conference,book,book_chapter,report_paper',
			'config' => Array(
				'type' => 'inline',			
				'foreign_table' => 'tx_pubdb_pub_contributors',
				'foreign_field' => 'pubid',
				'foreign_sortby' => 'pubsort',
				'foreign_selector' => 'contributorid',
				'foreign_unique' => 'contributorid',
				'foreign_label' => 'contributorid',
				'maxitems' => 50,
				'size' => 1,
				'appearance' => Array(
					'collapseAll' => 1,
					'expandSingle' => 1,
					'useSortable' => 1,
					//'showSynchronizationLink' => 1,
					//'showAllLocalizationLink' => 1,
					//'showPossibleLocalizationRecords' => 1,
					//'showRemovedLocalizationRecords' => 1,
					'useCombination' => 1,
				),
				
			),

		),	
	
			
		
	),
	// styles: 0: default, 1: Meta, 2: headers; 3: main content, 4: extra,5 : advanced
	'types' => Array (
		'0' => Array('showitem' => 'pubtype;;;;1-1-1,subpubtype;;;;1-1-1,booktype;;;;1-1-1,parent_pubid,author;;;;3-3-3,author_email;;;;3-3-3,author_address;;;;3-3-3,title;;;;3-3-3,abbrev_title;;;;3-3-3, subtitle;;;;3-3-3, theme;;;;3-3-3,contributors,coauthors;;;;3-3-3,  publisher;;;;3-3-3, location;;;;3-3-3, year;;;;3-3-3, isbn;;;;3-3-3,isbn2;;;;3-3-3,doi;;;;3-3-3,series;;;;3-3-3,pages;;;;3-3-3,edition;;;;3-3-3,number;;;;3-3-3,startdate;;;;3-3-3,enddate;;;;3-3-3,issue;;;;3-3-3,internalNumber;;;;1-1-1, abstract;;;richtext[paste|bold|italic|underline|formatblock|class|left|center|right|orderedlist|unorderedlist|outdent|indent|link|image]:rte_transform[mode=ts], file;;;;4-4-4, fileType;;;;4-4-4, openFile;;;;4-4-4, openFileType;;;;4-4-4, category;;;;1-1-1, hashardcopy;;1;;1-1-1,  price;;;;4-4-4, reducedprice;;;;4-4-4,hascrossrefentry;;1;;1-1-1,xml;;;;1-1-1')
	),
);


$TCA['tx_pubdb_pub_contributors'] = Array(
	'ctrl' => $TCA['tx_pubdb_pub_contributors']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'pubid,role,contributorid'
	),
	'feInterface' => $TCA['tx_pubdb_categories']['feInterface'],
	'columns' => Array(
		'pubid' => Array(
			'label' => 'Publication',
			'config' => Array(
				'type' => 'select',
				'foreign_table' => 'tx_pubdb_data',
				'maxitems' => 1,
				//'localizeReferences' => 1,
			)

		),
		'contributorid' => Array(
			'label' => 'Contributor',
			'config' => Array(
				'type' => 'select',
				'foreign_table' => 'tx_pubdb_contributors',
				'maxitems' => 1,
				//'localizeReferences' => 1,
			)

		),
		'role' => Array (
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_contributors.contributor_role',	
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.contributor.role.1', 'author'),
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.contributor.role.2', 'editor'),
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.contributor.role.3', 'chair'),
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_data.contributor.role.4', 'translator'),
				),
				'default' => 'author',
			)

		),
		'pubsort' => Array(
			'config' => Array(
			     'type' => 'passthrough',
			)
		),
		'contributorsort' => Array(
			'config' => Array(
			     'type' => 'passthrough',
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => 'pubid, role, contributorid, pubsort, contributorsort')
	),		
);

$TCA['tx_pubdb_categories'] = Array (
	'ctrl' => $TCA['tx_pubdb_categories']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'hidden,name'
	),
	'feInterface' => $TCA['tx_pubdb_categories']['feInterface'],
	'columns' => Array (
		'hidden' => Array (		
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'name' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_categories.name',		
			'config' => Array (
				'type' => 'input',	
				'size' => '30',
			)
		),
		'fegroup' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_categories.fegroup',		
			'config' => Array (
				'type' => 'group',	
				'internal_type' => 'db',	
				'allowed' => 'fe_groups',	
				'size' => 5,	
				'minitems' => 0,
				'maxitems' => 20,	
				'MM' => 'tx_pubdb_category_fegroups_mm',
				
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => 'hidden;;1;;1-1-1, name, fegroup')
	),
	'palettes' => Array (
		'1' => Array('showitem' => '')
	)
);


$TCA['tx_pubdb_contributors'] = Array (
	'ctrl' => $TCA['tx_pubdb_contributors']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'organization,surname,given_name,suffix,role'
	),
	'feInterface' => $TCA['tx_pubdb_contributors']['feInterface'],
	'columns' => Array (
		'contributor_type' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_contributor.type',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_contributor.type.1', 'organization'),
					Array('LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_contributor.type.2', 'person'),
				),
				'default' => 'person'
			)
		),
		'organization' => Array (		
			'exclude' => 1,
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_contributor.organization',
			'displayCond' => 'FIELD:contributor_type:=:organization',
			'config' => Array (
				'type' => 'input',
				'size' => '30'
			)
		),
		'surname' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_contributor.surname',
			'displayCond' => 'FIELD:contributor_type:=:person',		
			'config' => Array (
				'type' => 'input',	
				'size' => '20',
			)
		),
		'given_name' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_contributor.given_name',	
			'displayCond' => 'FIELD:contributor_type:=:person',		
			'config' => Array (
				'type' => 'input',	
				'size' => '20',
			)
		),
		'suffix' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_contributor.suffix',
			'displayCond' => 'FIELD:contributor_type:=:person',			
			'config' => Array (
				'type' => 'input',	
				'size' => '20',
			)
		),
		'affiliation1' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_contributor.affiliation1',	
			'displayCond' => 'FIELD:contributor_type:=:person',		
			'config' => Array (
				'type' => 'input',	
				'size' => '30',
			)
		),
		'affiliation2' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_contributor.affiliation2',		
			'displayCond' => 'FIELD:contributor_type:=:person',	
			'config' => Array (
				'type' => 'input',	
				'size' => '30',
			)
		),
		'affiliation3' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_contributor.affiliation3',		
			'displayCond' => 'FIELD:contributor_type:=:person',	
			'config' => Array (
				'type' => 'input',	
				'size' => '30',
			)
		),
		'affiliation4' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_contributor.affiliation4',	
			'displayCond' => 'FIELD:contributor_type:=:person',		
			'config' => Array (
				'type' => 'input',	
				'size' => '30',
			)
		),
		'affiliation5' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_contributor.affiliation5',	
			'displayCond' => 'FIELD:contributor_type:=:person',		
			'config' => Array (
				'type' => 'input',	
				'size' => '30',
			)
		),
		'orcid' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pubdb/locallang_db.xml:tx_pubdb_contributor.orcid',	
			'displayCond' => 'FIELD:contributor_type:=:person',		
			'config' => Array (
				'type' => 'input',	
				'size' => '30',
			)
		),
			'pubids' => Array(
					'config' => Array(
							'type' => 'passthrough',
					),
						
			),

			/*'pubids' => Array(
					"exclude" => 1,
					"label" => "pubids",
					"config" => Array (
							"type" => "inline",
							"foreign_table" => "tx_pubdb_pub_contributors",
							"foreign_field" => "contributorid",
							"foreign_sortby" => "contributorsort",
							//"foreign_label" => "pubid",
							"maxitems" => 10,
							'appearance' => array(
									/*'showSynchronizationLink' => 1,
									'showAllLocalizationLink' => 1,
									'showPossibleLocalizationRecords' => 1,
									'showRemovedLocalizationRecords' => 1,
							),
							'behaviour' => array(
									'localizationMode' => 'select',
							),
					)
			),*/
	),
	'types' => Array (
		'0' => Array('showitem' => 'pubids,contributor_type,organization, surname, given_name, suffix, affiliation1, affiliation2, affiliation3, affiliation4,affiliation5,ocrid')
	),
	"palettes" => Array (
				"1" => Array("showitem" => "")
		)
);

?>
