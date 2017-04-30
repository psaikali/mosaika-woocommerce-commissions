<?php

/*******************
 ******************* Tutoriel expliquant ce fichier : https://mosaika.fr/utiliser-cagnotte-reduction-parrain-woocommerce
 *******************/

/**
 * On affiche la checkbox "J'utilise mes X points" sur la page de commande
 */
function msk_display_use_points_checkbox() {
	// On récupère le nombre de points de l'utilisateur
	$user_points = msk_get_customer_commission_balance(get_current_user_id())['balance'];

	// Si l'acheteur a des points...
	if ($user_points > 0) {
		if (isset($_POST['post_data'])) {
			parse_str($_POST['post_data'], $form_data);
		} else {
			$form_data = $_POST;
		} 

		if (empty($form_data)) $form_data['use-points'] = 'on';

		// Si il a plus de points que le total de la commande à payer, il utilisera moins de points
		if ($user_points > WC()->cart->subtotal_ex_tax) {
			$user_points = WC()->cart->subtotal_ex_tax;
		} ?>
		
		<!-- Idéalement, placer ce bout de Javascript dans un fichier .js de votre thème/plugin -->
		<script>jQuery('form.checkout').on('change', '#use-points', function(){ jQuery('body').trigger('update_checkout'); });</script>

		<fieldset class="use-points">
			<label for="use-points">
				<input type="hidden" name="use-points" value="off" />
				<input type="checkbox" <?php checked($form_data['use-points'], 'on'); ?> id="use-points" name="use-points" value="on" />
				<span><?php printf(__('J\'utilise mes %1$s points.', 'mosaika'), msk_money_to_points_value($user_points)); ?></span>
			</label>	
		</fieldset>
	<?php }
}
add_action('woocommerce_checkout_before_order_review', 'msk_display_use_points_checkbox');

/**
 * On applique une réduction sur la commande si le client a coché l'usage de ses points
 */
function msk_add_discount_to_cart_total($cart) {
	if (!$_POST || (is_admin() && !is_ajax())) {
		return;
	}

	if (isset($_POST['post_data'])) {
		parse_str($_POST['post_data'], $form_data);
	} else {
		$form_data = $_POST; // fallback for final checkout (non-ajax)
	}

	// Si l'acheteur a coché la checkbox pour utiliser ses points...
	if (isset($form_data['use-points']) && $form_data['use-points'] == 'on') {
		// On récupère son nombre de points
		$discount = msk_get_customer_commission_balance(get_current_user_id())['balance'];

		// Si il a des points...
		if ($discount > 0) {
			$cart_subtotal = WC()->cart->subtotal_ex_tax;

			// Si il a plus de points que le montant de la commande, on limite
			if ($discount > $cart_subtotal) {
				$discount = $cart_subtotal;
			}

			// On ajoute un frais négatif (réduction) sur sa commande
			WC()->cart->add_fee(__('Utilisation de vos points', 'mosaika'), -$discount, false, '');
		}
	}
}
add_action('woocommerce_cart_calculate_fees', 'msk_add_discount_to_cart_total');


/**
 * Lorsqu'un parrain utilise ses points gagnés via commissions, on enregistre leur usage dans la BDD
 */
function msk_save_commissions_use_from_order($order_id, $old_status, $new_status) {
	global $wpdb;
	$commissions_table_name = $wpdb->prefix . 'commissions';

	// On récupère les infos de la commande
	$order = wc_get_order($order_id);
	$order_status = $new_status;
	$order_data = $order->get_data();
	$type = 'use';

	// Si la commande était "terminée", on supprime tout usage de points enregistré dans notre table SQL
	if ($old_status == 'completed') {
		$wpdb->delete(
			$commissions_table_name,
			array('order_id' => $order_id, 'type' => $type),
			array('%d', '%s')
		);
	}

	// Si la commande devient "terminée"...
	if ($order_status == 'completed' && isset($order_data['fee_lines'])) {
		foreach ($order_data['fee_lines'] as $fee) {
			// Si la commande possède des "frais"
			if (is_a($fee, 'WC_Order_Item_Fee')) {
				// Si la commande possède un frais "Utilisation de vos points" ajouté par notre plugin
				if ($fee->get_name() == __('Utilisation de vos points', 'mosaika')) {
					// On récupère le nombre de points utilisés
					$commission_used = abs($fee->get_total());

					// Si il a utilisé des points, on enregistre l'usage de ces points dans notre table SQL
					if ($commission_used > 0) {
						$data = array(
							'type' => $type,
							'amount' => $commission_used,
							'user_id' => $order->get_customer_id(),
							'order_id' => $order_id,
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
add_action('woocommerce_order_status_changed', 'msk_save_commissions_use_from_order', 10, 3);