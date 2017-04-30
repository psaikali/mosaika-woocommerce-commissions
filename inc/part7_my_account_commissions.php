<?php

/*******************
 ******************* Tutoriel expliquant ce fichier : https://mosaika.fr/ajouter-onglet-compte-client-woocommerce-commission
 *******************/

/**
* On ajoute un onglet "Mes points" dans Mon Compte WooCommerce
*/
function msk_add_my_account_points_tab($items) {
	$new_items = $items;

	$new_items = array_insert_after('orders', $new_items, 'points', __('Mes points', 'mosaika'));

	return $new_items;
}
add_filter('woocommerce_account_menu_items', 'msk_add_my_account_points_tab');


/**
* On déclare l'URL /points comme valide pour WordPress
*/
function msk_add_wc_points_endpoint() {
	 add_rewrite_endpoint('points', EP_ROOT | EP_PAGES);
}
add_action('init', 'msk_add_wc_points_endpoint');


/**
* On ajoute une variable de query 'points'
*/
function msk_add_wc_points_query_vars($vars) {
	$vars[] = 'points';
	return $vars;
}
add_action('query_vars', 'msk_add_wc_points_query_vars', 0);


/**
* On change le titre "Mon compte" sur la page des points
*/
function msk_change_my_account_points_page_title($title, $id) {
	global $wp;

	if (isset($wp->query_vars['points']) && in_the_loop()) {
		$title = __('Gestion de mes points', 'mosaika');
	}

	return $title;
}
add_filter('the_title', 'msk_change_my_account_points_page_title', 10, 2);


/**
* On charge le templace woocommerce 'myaccount/commissions.php' pour afficher le contenu de l'onglets 'Mes points'
*/
function msk_add_wc_points_content() {
	include MSK_WC_COMMISSION_DIR_PATH . 'woocommerce/myaccount/commissions.php';
}
add_action('woocommerce_account_points_endpoint', 'msk_add_wc_points_content');