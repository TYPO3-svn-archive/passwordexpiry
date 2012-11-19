#
# Table structure for table 'be_users'
#
CREATE TABLE be_users (
	tx_passwordexpiry_expires int(11) DEFAULT '0' NOT NULL,
	tx_passwordexpiry_blacklist tinytext
);