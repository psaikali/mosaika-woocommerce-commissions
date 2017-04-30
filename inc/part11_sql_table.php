<?php

/*******************
 ******************* Tutoriel expliquant ce fichier : https://mosaika.fr/creer-table-sql-wordpress-commission
 *******************/

/**
 * Si inexistante, on créée la table SQL "commissions" après l'activation du thème
 */
global $wpdb;
$charset_collate = $wpdb->get_charset_collate();

$commissions_table_name = $wpdb->prefix . 'commissions';

$commissions_sql = "CREATE TABLE IF NOT EXISTS $commissions_table_name (
	id mediumint(9) NOT NULL AUTO_INCREMENT,
	type varchar(45) DEFAULT NULL,
	amount decimal(10,2) DEFAULT NULL,
	user_id mediumint(9) DEFAULT NULL,
	order_id mediumint(9) DEFAULT NULL,
	line_product_id mediumint(9) DEFAULT NULL,
	line_product_rate decimal(10,2) DEFAULT NULL,
	line_product_quantity mediumint(9) DEFAULT NULL,
	line_subtotal decimal(10,2) DEFAULT NULL,
	user_notified varchar(45) DEFAULT NULL,
	time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
	PRIMARY KEY  (id)
) $charset_collate;";

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

dbDelta($commissions_sql);