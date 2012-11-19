<?php

########################################################################
# Extension Manager/Repository config file for ext "passwordexpiry".
#
# Auto generated 08-04-2011 01:27
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Password Expiry and Reset',
	'description' => 'Checks password for expiry upon login and sends user to a password change form in case of an expired password.',
	'category' => 'be',
	'author' => 'Christian Lerrahn (Cerebrum)',
	'author_email' => 'christian.lerrahn@cerebrum.com.au',
	'shy' => '',
	'dependencies' => 'cms',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.0.1',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:14:{s:9:"ChangeLog";s:4:"c04e";s:10:"README.txt";s:4:"ee2d";s:39:"class.tx_passwordexpiry_checkexpiry.php";s:4:"1f6d";s:21:"ext_conf_template.txt";s:4:"1482";s:12:"ext_icon.gif";s:4:"1bdc";s:17:"ext_localconf.php";s:4:"db9b";s:14:"ext_tables.php";s:4:"ee6c";s:14:"ext_tables.sql";s:4:"20f3";s:16:"locallang_db.xml";s:4:"57e1";s:20:"locallang_errors.xml";s:4:"4696";s:19:"doc/wizard_form.dat";s:4:"b829";s:20:"doc/wizard_form.html";s:4:"de48";s:24:"res/typo3_src-4.4.6.diff";s:4:"8f88";s:24:"res/typo3_src-4.5.2.diff";s:4:"ac7c";}',
	'suggests' => array(
	),
);

?>