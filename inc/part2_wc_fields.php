<?php

/**
 * On ajoute un onglet "Parrainage" dans le back-office d'un produit WooCommerce
 */
function msk_add_commission_product_tab($tabs) {
	$tabs = array_insert_after('general', $tabs, 'commission', array(
		'label' => __('Parrainage', 'mosaika'),
		'target' => 'commission_product_data',
		'class' => array()
	));

	return $tabs;
}
add_filter('woocommerce_product_data_tabs', 'msk_add_commission_product_tab');


/**
 * On ajoute les champs spécifiques aux commissions/prescripteur
 */
function msk_add_commission_product_fields() { 
	global $post;
	$commission_user_id = get_post_meta($post->ID, 'commission_user_id', true); ?>

	<div id="commission_product_data" class="panel woocommerce_options_panel">
		<h4 style="padding-left:12px;"><?php _e('Parrainage et commission', 'mosaika'); ?></h4>

		<?php woocommerce_wp_text_input(
			array(
				'id' => 'commission_user_id',
				'label' => __('Identifiant utilisateur', 'mosaika'),
				'placeholder' => __('Sélectionnez l\'ID du parrain', 'mosaika'),
			)
		);

		// On affiche sous le champ le login et l'e-mail de l'utilisateur relié à l'ID défini dans le champ ci-dessus
		if (isset($commission_user_id) && $commission_user_id != '') {
			$commission_user_data = get_userdata((int)$commission_user_id);
			printf(
				'<p style="padding-left:12px;font-style:italic;margin-top: -14px;">' . __('Cet identifiant correspond à l\'utilisateur %1$s ayant l\'e-mail %2$s.', 'mosaika') . '</p>',
				'<strong>' . $commission_user_data->user_login . '</strong>',
				'<strong>' . $commission_user_data->user_email . '</strong>'
			);
		}

		woocommerce_wp_text_input(
			array(
				'id' => 'commission_rate',
				'label' => __('Commission (en %)', 'mosaika'),
				'placeholder' => __('5', 'mosaika'),
				'description' => __('Informez le pourcentage de commission qui sera calculé à partir du montant HT d\'une commande et reversé au parrain.', 'mosaika'),
				'desc_tip' => true,
				'class' => 'wc_input_price'
			)
		);

		woocommerce_wp_text_input(
			array(
				'id' => 'commission_date_start',
				'label' => __('Début de récompense', 'mosaika'),
				'placeholder' => __('01/01/2016', 'mosaika'),
				'description' => __('Indiquez ici la date de DÉBUT de période à partir de laquelle une commission sera reversée à l\'utilisateur parrain.', 'mosaika'),
				'desc_tip' => true,
				'class' => 'short input-date'
			)
		);

		woocommerce_wp_text_input(
			array(
				'id' => 'commission_date_end',
				'label' => __('Fin de récompense', 'mosaika'),
				'placeholder' => __('31/12/2016', 'mosaika'),
				'description' => __('Indiquez ici la date de FIN de période à partir de laquelle le parrainage ne sera plus effectif.', 'mosaika'),
				'desc_tip' => true,
				'class' => 'short input-date'
			)
		); ?>
	</div>

	<style>#woocommerce-product-data ul.wc-tabs li.commission_options a::before, .woocommerce ul.wc-tabs li.commission_options a::before { content: '\f155'; }</style>
	
	<?php
}
add_action('woocommerce_product_data_panels', 'msk_add_commission_product_fields');


/**
 * On enregistre les valeurs des champs lorsque le produit est enregistré
 */
function msk_save_commission_product_fields_data($product_id, $post, $update) {
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

	if ($post->post_type == 'product') {
		if (isset($_POST['commission_user_id'])) {
			$commission_user_id = wc_clean($_POST['commission_user_id']);
			update_post_meta($product_id, 'commission_user_id', $commission_user_id);
		}

		if (isset($_POST['commission_user'])) {
			$commission_user = intval($_POST['commission_user']);
			update_post_meta($product_id, 'commission_user', $commission_user);
		}

		if (isset($_POST['commission_rate'])) {
			$commission_rate = floatval($_POST['commission_rate']);
			update_post_meta($product_id, 'commission_rate', $commission_rate);
		}

		if (isset($_POST['commission_date_start'])) {
			$commission_date_start = wc_clean($_POST['commission_date_start']);
			update_post_meta($product_id, 'commission_date_start', $commission_date_start);
		}

		if (isset($_POST['commission_date_end'])) {
			$commission_date_end = wc_clean($_POST['commission_date_end']);
			update_post_meta($product_id, 'commission_date_end', $commission_date_end);
		}
	}
}
add_action('save_post', 'msk_save_commission_product_fields_data', 10, 3);