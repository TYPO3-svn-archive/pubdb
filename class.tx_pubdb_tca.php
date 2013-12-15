<?php


class tx_pubdb_tca {

     
    // Generates a list of Categories for the Muliselection for the list view
    function genList($config) {
  //      t3lib_div::debug($config);
    	//$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,pers_vn,pers_fn','fe_users',"FIND_IN_SET('16',usergroup)>0 OR FIND_IN_SET('17',usergroup)>0");
         $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_pubdb_categories','','','name'); // SORT BY pers_fn");
        $counter = 0;
       
   if (mysql_num_rows($result) > 0) {
        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
            $config['items'][$counter][0] = $row['name'];
            $config['items'][$counter][1] = $row['uid'];
            $counter += 1;
        }
      }

       return $config;
      }


}

?>
