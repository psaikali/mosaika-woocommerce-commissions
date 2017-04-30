<?php

/*******************
 ******************* Tutoriel expliquant ce fichier : https://mosaika.fr/creer-cron-wordpress-envoyer-email-commission
 *******************/
 
/**
 * On enregistre le cron
 */
function msk_cron_register_cron_jobs() {
	// Si le cron n'est pas encore défini, on l'initialise
	if (!wp_next_scheduled('msk_cron_notify_parrain_of_new_points')) {
		wp_schedule_event(time(), 'hourly', 'msk_cron_notify_parrain_of_new_points');
	}
}
add_action('init', 'msk_cron_register_cron_jobs');


/**
 * On hooke une fonction sur l'action du cron
 */
add_action('msk_cron_notify_parrain_of_new_points', 'msk_send_email_notification_to_parrain_with_new_points');


/**
 * La fonction qui sera lancée par le cron, pour envoyer les e-mails
 */
function msk_send_email_notification_to_parrain_with_new_points() {
	global $wpdb;
	$commissions_table_name = $wpdb->prefix . 'commissions';

	// On va récupérer toutes les lignes de commissions de notre table SQL où la colonne 'user_notified' est définie sur 'no'
	$parrains_to_notify = $wpdb->get_results(
		$wpdb->prepare("
			SELECT id, user_id, SUM(amount) as new_points, SUM(line_product_quantity) new_products
			FROM $commissions_table_name 
			WHERE user_notified = %s 
			AND type = %s
			GROUP BY user_id
			ORDER BY time DESC
			",
			'no',
			'gain'
		)
	);

	// Pour chaque parrain à notifier...
	foreach ($parrains_to_notify as $parrain) {
		// On prépare les valeurs à envoyer
		$user = get_userdata($parrain->user_id);
		$user_email = $user->user_email;
		$user_login = $user->user_login;
		$total_points = msk_money_to_points_value(msk_get_customer_commission_balance($parrain->user_id)['balance']);
		$new_points = msk_money_to_points_value($parrain->new_points);
		$new_products = $parrain->new_products;
		$my_account = wc_get_account_endpoint_url('points');
		$shop_url = get_permalink(get_option('woocommerce_shop_page_id'));

		// On envoie un e-mail au parrain
		$subject = 'Vous avez gagné de nouveaux points !';
		$body = sprintf(
			__('Bonjour %1$s, vous avez gagné %2$s nouveaux points grâce à l\'achat de %3$s produits que vous nous avez proposés.', 'mosaika'),
			$user_login,
			$new_points,
			$new_products
		);

		if (wp_mail($user_email, $subject, $body, array('Content-Type: text/html; charset=UTF-8'))) {
			// Si l'e-mail est envoyé, on met à jour la colonne 'user_notified' pour ne plus le notifier au sujet de ces nouvelles lignes
			$total_row_updated = $wpdb->update(
				$commissions_table_name,
				array(
					'user_notified' => current_time('mysql')
				),
				array(
					'user_id' => $parrain->user_id,
					'user_notified' => 'no',
				)
			);
		}
	}
}