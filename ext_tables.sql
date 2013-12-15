# TYPO3 Extension Manager dump 1.1
#
# Host: localhost    Database: typo3_argesim
#--------------------------------------------------------


#
# Table structure for table "tx_pubdb_data_category_mm"
#
CREATE TABLE tx_pubdb_data_category_mm (
  uid_local int(11) default '0',
  uid_foreign int(11) default '0',
  tablenames varchar(30) default '',
  sorting int(11) default '0',
  KEY uid_local (uid_local),
  KEY uid_foreign (uid_foreign)
);


#
# Table structure for table "tx_pubdb_category_fegroups_mm"
#
CREATE TABLE tx_pubdb_category_fegroups_mm (
  uid_local int(11) default '0',
  uid_foreign int(11) default '0',
  tablenames varchar(30) default '',
  sorting int(11) default '0',
  KEY uid_local (uid_local),
  KEY uid_foreign (uid_foreign)
);


#
# Table structure for table "tx_pubdb_data"
#
CREATE TABLE tx_pubdb_data (
  uid int(11) auto_increment,
  pid int(11) default '0',
  tstamp int(11) default '0',
  crdate int(11) default '0',
  cruser_id int(11) default '0',
  deleted tinyint(4) default '0',
  hidden tinyint(4) default '0',
  title tinytext,
  abbrev_title tinytext,
  author tinytext,
  author_email tinytext,
  author_address tinytext,
  coauthors tinytext,
  pubtype tinytext,
  subpubtype tinytext,
  year int(11) default '0',
  series tinytext,
  number tinytext,
  issue tinytext,
  pages tinytext,
  isbn tinytext,
  isbn2 tinytext,
  media_type tinytext,
  doi tinytext,
  internalNumber tinytext,
  file blob,
  fileType int(11) default '0',
  openFile blob,
  openFileType int(11) default '0',
  category int(11) default '0',
  abstract text,
  subtitle tinytext,
  publisher tinytext,
  location tinytext,
  hashardcopy int(11) default '0',
  price tinytext,
  reducedprice tinytext,
  parent_pubid int(11) default '0',
  hascrossrefentry int(11) default '0', 
  sponsors tinytext,
  startdate tinytext, 
  enddate tinytext,	
  contributors int(11) default '0',
  theme tinytext,	
  booktype tinytext,
  edition int(11) default '0',
  PRIMARY KEY (uid),
  KEY parent (pid)
);


#
# Table structure for table "tx_pubdb_categories"
#
CREATE TABLE tx_pubdb_categories (
  uid int(11) auto_increment,
  pid int(11) default '0',
  tstamp int(11) default '0',
  crdate int(11) default '0',
  cruser_id int(11) default '0',
  deleted tinyint(4) default '0',
  hidden tinyint(4) default '0',
  name tinytext,
  pub blob,
  publications int(11) default '0',
  fegroup int(11) default '0',
  PRIMARY KEY (uid),
  KEY parent (pid)
);


create TABLE tx_pubdb_contributors (
	uid int(11) auto_increment,
 	pid int(11) default '0',
	tstamp int(11) default '0',
	crdate int(11) default '0',
	cruser_id int(11) default '0',
	deleted tinyint(4) default '0',
	hidden tinyint(4) default '0',
	organization tinytext,
	surname tinytext,
	given_name tinytext,
	suffix tinytext,
	affiliation1 tinytext,
	affiliation2 tinytext,
	affiliation3 tinytext,
	affiliation4 tinytext,
	affiliation5 tinytext,
	orcid tinytext,
	contributor_type tinytext,
	pubids int(11) default '0',
	showinlist int(11) default '0',
	PRIMARY KEY (uid),
	KEY parent (pid)
);
#
#	Intermediate table for N:M relations between publications and contributors with extra fields
#
create TABLE tx_pubdb_pub_contributors (
	uid int(11) auto_increment,
 	pid int(11) default '0',
	tstamp int(11) default '0',
	crdate int(11) default '0',
	cruser_id int(11) default '0',
	deleted tinyint(4) default '0',
	hidden tinyint(4) default '0',
	pubid int(11) default '0',
	contributorid int(11) default '0',
	role tinytext,
	pubsort int(10) DEFAULT '0' NOT NULL,
	contributorsort int(10) DEFAULT '0' NOT NULL,
	PRIMARY KEY (uid),
	KEY parent (pid)
);

#
# Table structure for table 'tt_news'
#
CREATE TABLE tt_news (
        tx_pubdb_link_title tinytext,
	tx_pubdb_newslink int(11) DEFAULT '0' NOT NULL
);
