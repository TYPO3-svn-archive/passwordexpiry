<?php
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}

// used hooks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['postLogin'][] = 'EXT:passwordexpiry/class.tx_passwordexpiry_checkexpiry.php:tx_passwordexpiry_checkexpiry->checkExpired';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:passwordexpiry/class.tx_passwordexpiry_checkexpiry.php:tx_passwordexpiry_checkexpiry';
?>
