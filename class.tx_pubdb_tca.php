<?php


class tx_pubdb_tca {

     
	/**
	 * @return tx_pubdb_tca
	 */
	public static function getInstance() {
		return t3lib_div::makeInstance('tx_pubdb_tca');
	}
	
    /*
     *  Generates a list of Categories for the Muliselection for the list view
     */
   public function user_categoryList($config) {
         $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_pubdb_categories', '', '', 'name'); // SORT BY pers_fn");
         $counter = 0;
   if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) > 0) {
        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
            $config['items'][$counter][0] = $row['name'];
            $config['items'][$counter][1] = $row['uid'];
            $counter += 1;
        }
      }

       return $config;
      }


}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pubdb/Classes/class.tx_pubdb_tca.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pubdb/Classes/class.tx_pubdb_tca.php']);
}

?>
