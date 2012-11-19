<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}
$tempColumns = array (
	'tx_passwordexpiry_expires' => array (		
		'exclude' => 1,		
		'label' => 'LLL:EXT:passwordexpiry/locallang_db.xml:be_users.tx_passwordexpiry_expires',		
		'config' => array (
			'type'     => 'input',
			'size'     => '8',
			'max'      => '20',
			'eval'     => 'date',
			'checkbox' => '0',
			'default'  => '0'
		)
	),
	'tx_passwordexpiry_blacklist' => array (		
		'exclude' => 1,		
		'label' => 'LLL:EXT:passwordexpiry/locallang_db.xml:be_users.tx_passwordexpiry_blacklist',		
		'config' => array (
			'type' => 'input',	
			'size' => '30',
		)
	),
);


t3lib_div::loadTCA('be_users');
t3lib_extMgm::addTCAcolumns('be_users',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('be_users','tx_passwordexpiry_expires;;;;1-1-1, tx_passwordexpiry_blacklist');
?>