<?php

/**
 * On enregistre le cron
 */
function msk_cron_register_cron_jobs() {
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

	foreach ($parrains_to_notify as $parrain) {
		$user = get_userdata($parrain->user_id);
		$user_email = $user->user_email;
		$user_login = $user->user_login;
		$total_points = msk_money_to_points_value(msk_get_customer_commission_balance($parrain->user_id)['balance']);
		$new_points = msk_money_to_points_value($parrain->new_points);
		$new_products = $parrain->new_products;
		$my_account = wc_get_account_endpoint_url('points');
		$shop_url = get_permalink(get_option('woocommerce_shop_page_id'));

		$email_data = compact('user_email', 'user_login', 'total_points', 'new_points', 'new_products', 'my_account', 'shop_url');

		if (msk_send_mail('new_points_notify_user', $user_email, $email_data)) {
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