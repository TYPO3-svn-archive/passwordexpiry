<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Cerebrum (Aust) Pty Ltd <info@cerebrum.com.au>
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
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
 *
 * @author	Christian Lerrahn <christian.lerrahn@cerebrum.com.au>
 * @package TYPO3
 * @subpackage passwordexpiry
 */

/**
 * Checks for expired password
 */
class tx_passwordexpiry_checkexpiry {

  var $extKey = 'passwordexpiry';

  /**
   * Checks if user's password is expired (login screen)
   *
   * @param	object		$pObj: The parent object
   * @return	boolean	authentication status
   */
  function checkExpired($params) {

    // Extension config
    $conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['passwordexpiry']);
	
    $pObj = $params['pObj']; // parent object (BE user object)
    $userArray = $params['tempuserArr'];
    $loginData = $params['loginData'];

    // load language file for error messages
    $labels = t3lib_div::makeInstance('language');
    $labels->init($pObj->uc['lang']);
    $labels->includeLLFile('EXT:'.$this->extKey.'/locallang_errors.xml');

    $valid = TRUE; // default to not expired

    $blacklisted = FALSE; // password is blacklisted
		
    foreach ($userArray as $userRec) {

      if (isset($userRec['tx_passwordexpiry_expires'])) { // only if field exists (can't update otherwise)
	if ($pObj->writeDevLog) {
	  t3lib_div::devLog('User ('.$userRec['uid'].') password expires '.strftime('%e-%m-%Y',$userRec['tx_passwordexpiry_expires']), 'tx_passwordexpiry');
	}
	if ($userRec['tx_passwordexpiry_expires'] < time()) {
	  $valid = FALSE;

	  // if RSA encrypted, decrypt now
	  //if ($pObj->pObj->security_level == 'rsa' && t3lib_extMgm::isLoaded('rsaauth')) {
	  require_once(t3lib_extMgm::extPath('rsaauth') . 'sv1/backends/class.tx_rsaauth_backendfactory.php');
	  require_once(t3lib_extMgm::extPath('rsaauth') . 'sv1/storage/class.tx_rsaauth_storagefactory.php');
	    
	  $backend = tx_rsaauth_backendfactory::getBackend();
	  $storage = tx_rsaauth_storagefactory::getStorage();
	  // Preprocess the password
	  $opassword = $loginData['uident'];
	  $npassword = $loginData['nuident'];
	  $npassword2 = $loginData['nuident2'];
	  $key = $storage->get();
	  if ($key != NULL && substr($npassword, 0, 4) == 'rsa:' && substr($npassword2, 0, 4) == 'rsa:') {
	    // Decode password and pass to parent
	    $loginData['uident'] = $backend->decrypt($key, substr($opassword, 4));
	    $loginData['nuident'] = $backend->decrypt($key, substr($npassword, 4));
	    $loginData['nuident2'] = $backend->decrypt($key, substr($npassword2, 4));
	  }

	  if (!$loginData['nuident']) {
	    $pObj->externalErrors['ERROR_MESSAGE'] = $labels->getLL('error.expired', true);	
	    $pObj->externalErrors['ERROR_LOGIN_TITLE'] = $labels->getLL('error.expired.title', true);
	    $pObj->externalErrors['ERROR_LOGIN_DESCRIPTION'] = $labels->getLL('error.expired.description', true);
	  } elseif (($loginData['nuident'] != $loginData['nuident2'])) {
	    $pObj->externalErrors['ERROR_MESSAGE'] = $labels->getLL('error.pwmismatch', true);
	    $pObj->externalErrors['ERROR_LOGIN_TITLE'] = $labels->getLL('error.pwmismatch.title', true);
	    $pObj->externalErrors['ERROR_LOGIN_DESCRIPTION'] = $labels->getLL('error.pwmismatch.description', true);
	  } else {

	    if ($loginData['nuident'] == $loginData['uident']) {
	      $valid = FALSE;
	      $blacklisted = TRUE;
	    }
	    else {

	      // default to password rejected
	      $valid = FALSE;

	      // check against blacklist
	      $oldPasswords = unserialize($userRec['tx_passwordexpiry_blacklist']);
	      $oldPasswords[] = $userRec['password'];
	      $blacklisted = FALSE;
	      foreach ($oldPasswords as $password) {
		$blacklisted = $blacklisted?$blacklisted:$this->performCheck($loginData['nuident'],$password);
	      }

	      if ($blacklisted === FALSE) {
		// Copy user record to set for processing
		$backupBEUser = $GLOBALS['BE_USER'];
		$GLOBALS['BE_USER']->user = $userRec;
		$GLOBALS['BE_USER']->user['admin'] = 1; // This is so the user can actually update his user record.

		// New record
		$newUser['be_users'][$userRec['uid']] = $userRec;
		$newUser['be_users'][$userRec['uid']]['password'] = $loginData['nuident'];
		
		// LANG object might not exist
		$OLD_LANG = $GLOBALS['LANG'];
		$GLOBALS['LANG'] = t3lib_div::makeInstance('language');
		$GLOBALS['LANG']->init($GLOBALS['BE_USER']->uc['lang']);
		
		t3lib_div::loadTCA('be_users');
		
		// Make instance of TCE for storing the changes.
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values=0;
		$tce->start($newUser,array());
		$tce->bypassWorkspaceRestrictions = TRUE;       // This is to make sure that the users record can be updated even if in another workspace. This is tolerated.
		$tce->process_datamap();
		unset($tce);

		// Restore LANG object
		$GLOBALS['LANG'] = $OLD_LANG;
		$GLOBALS['BE_USER'] = $backupBEUser;
	    
		$valid = TRUE;
	      }
	    }
	    if ($valid === FALSE && $blacklisted === TRUE) {
	      $pObj->externalErrors['ERROR_MESSAGE'] = sprintf($labels->getLL('error.pwblacklisted', true),intval($conf['blacklistLength'])+1);
	      $pObj->externalErrors['ERROR_LOGIN_TITLE'] = $labels->getLL('error.pwblacklisted.title', true);
	      $pObj->externalErrors['ERROR_LOGIN_DESCRIPTION'] = sprintf($labels->getLL('error.pwblacklisted.description', true),intval($conf['blacklistLength'])+1);   
	    }
	  }
	}
      }
    }
    return $valid;
  }

  /**
   * Checks if user's password is expired (BE password change)
   *
   * @param     array           $incomingArray: array of fields to be updated/inserted
   * @param     string          $table: table name
   * @param     int/string      $id: Id of record to be updated/inserted
   * @param	object		$pObj: The parent object
   * @return	void
   */
  function processDatamap_preProcessFieldArray(&$incomingArray,$table,$id,&$pObj) {
    $conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['passwordexpiry']);

    if (($table == 'be_users') && isset($incomingArray['password']) && is_int($id)) {

      // check against blacklist
      $oldPasswords = unserialize($GLOBALS['BE_USER']->user['tx_passwordexpiry_blacklist']);
      $curPassword = $GLOBALS['BE_USER']->user['password'];
      $blacklisted = FALSE;
      // not changing the password does not touch expiry time
      $noreset = $this->performCheck($incomingArray['password'],$curPassword);
      if (is_array($oldPasswords) && $oldPasswords != array()) {
	foreach ($oldPasswords as $password) {
	  $blacklisted = $blacklisted?$blacklisted:$this->performCheck($incomingArray['password'],$password);
	}
      }

      if ($blacklisted == FALSE) {
	if ($noreset === FALSE) { // password changed successfully
	  $incomingArray['tx_passwordexpiry_expires'] = time()+$conf['expiryPeriod']*86400;
	  $GLOBALS['BE_USER']->user['tx_passwordexpiry_expires'] = $incomingArray['tx_passwordexpiry_expires']; // set for current session
	  $oldPasswords[] = $curPassword;
	  if (count($oldPasswords) > $conf['blacklistLength']) { // cut blacklist to maximum length if needed
	    $oldPasswords = array_slice($oldPasswords,(-$conf['blacklistLength']));
	  }
	  $incomingArray['tx_passwordexpiry_blacklist'] = serialize($oldPasswords);
	  $GLOBALS['BE_USER']->user['tx_passwordexpiry_blacklist'] = $incomingArray['tx_passwordexpiry_blacklist']; // set for current session
	}
      } else {
	$incomingArray['tx_passwordexpiry_expires'] = time();
	$GLOBALS['BE_USER']->user['tx_passwordexpiry_expires'] = $incomingArray['tx_passwordexpiry_expires']; // set for current session
	unset($incomingArray['tx_passwordexpiry_blacklist']);
      }
    }
    return;
  }

  /**
   * Checks if user's password is expired
   *
   * @param     string          $newPassword: Password to be set
   * @param     string          $comparePassword: Password from blacklist
   *
   * @return	bool            Password blacklisted or not
   */
  function performCheck($newPassword,$comparePassword) {

    $match = FALSE;
    if ($newPassword == $comparePassword) { // plain text comparison
      $match = TRUE;
    } elseif (t3lib_extMgm::isLoaded('saltedpasswords')) {
      if (tx_saltedpasswords_div::isUsageEnabled('BE')) {
	$objSalt = tx_saltedpasswords_salts_factory::getSaltingInstance($comparePassword);
	// check current password first (if same, don't reset expiry time)
	if (is_object($objSalt)) {
	  $match = $objSalt->checkPassword($newPassword,$comparePassword);
	}
      }
      else { // md5 passwords (superchallenged)
	$match = (md5($newPassword) == $comparePassword); 
      }
    }

    return $match;
  }

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/passwordexpiry/class.tx_passwordexpiry_checkexpiry.php'])    {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/passwordexpiry/class.tx_passwordexpiry_checkexpiry.php']);
}
?>
