<?php

/*******************
 ******************* Tutoriel expliquant ce fichier : https://mosaika.fr/attribuer-recompense-commission-parrain-woocommerce
 *******************/

/**
 * Pour chaque ligne (produit) d'une commande, on enregistre une commission si ce produit est relié à un parrain (prescripteur)
 */
function msk_save_commissions_gains_from_order_items($order_id, $old_status, $new_status) {
	global $wpdb;
	$commissions_table_name = $wpdb->prefix . 'commissions';

	// On récupère la commande concernée et les valeurs importantes
	$order = wc_get_order($order_id);
	$order_items = $order->get_items();
	$order_customer_id = $order->get_customer_id();

	$type = 'gain';
	$order_status = $new_status;

	// Si la commande était "terminée" et change de statut, on supprime toute trace de commission concernant cette commande
	if ($old_status == 'completed') {
		$wpdb->delete(
			$commissions_table_name,
			array('order_id' => $order_id, 'type' => $type),
			array('%d', '%s')
		);
	}

	// Si la commande devient "terminée"
	if ($new_status == 'completed') {
		// On analyse chaque ligne (produit) de la commande
		foreach ($order_items as $order_item) {
			// On récupère le produit
			$line_product_id = $order_item->get_product_id();
			$line_product = wc_get_product($line_product_id);

			// On récupère les infos de commissions du produit
			$line_product_start_date = $line_product->get_meta('commission_date_start', true);
			$line_product_end_date = $line_product->get_meta('commission_date_end', true);

			// Si la date de commission est définie...
			if ($line_product_start_date != '' && !empty($line_product_start_date) && $line_product_end_date != '' && !empty($line_product_end_date)) {
				$order_timestamp = $order->get_date_created()->getOffsetTimestamp();
				$commission_start_timestamp = strtotime($line_product_start_date);
				$commission_end_timestamp = strtotime($line_product_end_date);

				// Si si la date de la commande est comprise entre la date de début et de fin de validité de commission...
				if ($order_timestamp > $commission_start_timestamp && $order_timestamp < $commission_end_timestamp) {
					// On récupère l'ID du parrain, le taux de commission
					$user_id = $line_product->get_meta('commission_user_id', true);
					$line_product_rate = $line_product->get_meta('commission_rate', true);
					// On récupère la quantité de produits achetés
					$line_product_quantity = $order_item->get_quantity();
					// On récupère le total de la ligne
					$line_subtotal = $order_item->get_subtotal();

					// Si la ligne de cette commande a un coût supérieur à 0, et si un taux de commission est défini sur ce produit...
					if ($line_product_rate > 0 && $line_subtotal > 0) {
						// On calcule la commission à reverser
						$amount = round((msk_price_to_float($line_product_rate) * $line_subtotal) / 100, 2);

						// Et si l'acheteur est le parrain, on double sa récompense
						if ($order_customer_id == $user_id) {
							$amount = $amount * 2;
						}
					}

					// Enfin, on enregistre toutes ces données dans notre table SQL
					if ($user_id != '' && !empty($user_id) && $amount > 0) {
						$data = array(
							'type' => $type,
							'amount' => $amount,
							'user_id' => $user_id,
							'order_id' => $order_id,
							'line_product_id' => $line_product_id,
							'line_product_rate' => $line_product_rate,
							'line_product_quantity' => $line_product_quantity,
							'line_subtotal' => $line_subtotal,
							'user_notified' => 'no',
							'time' => current_time('mysql')
						);

						$wpdb->insert(
							$commissions_table_name,
							$data
						);
					}
				}
			}
		}
	}
}
add_action('woocommerce_order_status_changed', 'msk_save_commissions_gains_from_order_items', 10, 3);