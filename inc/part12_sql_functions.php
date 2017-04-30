<?php

/*******************
 ******************* Tutoriel expliquant ce fichier : https://mosaika.fr/creer-table-sql-wordpress-commission
 *******************/

/**
 * Permet de savoir combien de "points" un utilisateur possède
 * On compte combien de points il a gagné (récompense) et on soustrait le nombre de points qu'il a déjà utilisés (usage)
 */
function msk_get_customer_commission_balance($user_id) {
	global $wpdb;
	$commissions_table_name = $wpdb->prefix . 'commissions';

	$commission_data = $wpdb->get_row(
		$wpdb->prepare("
			SELECT 
			IFNULL(sum(IF(type = %s, amount, 0)), 0) as user_gain,
			IFNULL(sum(IF(type = %s, amount, 0)), 0) as user_use
			FROM $commissions_table_name
			WHERE user_id = %d
			", 
			'gain',
			'use',
			$user_id
		)
	);

	$commission_balance = ($commission_data->user_gain > $commission_data->user_use) ? ($commission_data->user_gain - $commission_data->user_use) : 0;

	return array(
		// Le nombre de points disponibles
		'balance' => $commission_balance,
		// Le nombre de points gagnés depuis le début
		'gain' => $commission_data->user_gain,
		// Le nombre de points utilisés en réductions de commandes
		'use' => $commission_data->user_use
	);
}


/**
 * On récupère les détails chaque ligne de commission (récompense ou usage)
 */
function msk_get_customer_commission_data($user_id) {
	global $wpdb;
	$commissions_table_name = $wpdb->prefix . 'commissions';

	$commission = msk_get_customer_commission_balance($user_id);

	$commission_data = $wpdb->get_results(
		$wpdb->prepare("
			SELECT id, type, amount, order_id, line_product_id, line_product_rate, line_product_quantity, time
			FROM $commissions_table_name 
			WHERE user_id = %d 
			ORDER BY time DESC
			",
			$user_id
		)
	);

	return array(
		// Récapitulatif des points (cf fonction précédente)
		'points' => $commission, 
		// Détails de chaque gain/dépense
		'details' => $commission_data
	);
}