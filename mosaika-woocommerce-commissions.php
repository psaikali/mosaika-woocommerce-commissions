<?php
/**
 * Plugin Name:       Mosaika : commissions WooCommerce
 * Plugin URI:        https://mosaika.fr/guide/woocommerce-creer-systeme-commission-prescription/
 * Description:       Plugin permettant la création d'un système de prescription de produit / versement et usage de commissions
 * Version:           1.0
 * Author:            Mosaika
 * Author URI:        https://mosaika.fr
 * Text Domain:       mosaika
 * Domain Path:       /languages
 */

define('MSK_WC_COMMISSION_DIR_PATH', plugin_dir_path(__FILE__));

function msk_woocommerce_commissions_activation() {
	// Partie 1 : créer une table SQL custom
	require MSK_WC_COMMISSION_DIR_PATH . 'inc/part11_sql_table.php';	
}
register_activation_hook(__FILE__, 'msk_woocommerce_commissions_activation');

// Fonctions utilitaires
require MSK_WC_COMMISSION_DIR_PATH . 'inc/utils.php';

// Partie 1 : créer une table SQL custom
require MSK_WC_COMMISSION_DIR_PATH . 'inc/part12_sql_functions.php';

// Partie 2 : ajouter des champs au back-office des produits WooCommerce
require MSK_WC_COMMISSION_DIR_PATH . 'inc/part2_wc_fields.php';

// Partie 3 : formulaire front-end pour proposer un produit
require MSK_WC_COMMISSION_DIR_PATH . 'inc/part31_form_shortcode.php';
require MSK_WC_COMMISSION_DIR_PATH . 'inc/part32_form_process.php';

// Partie 4 : versement d'une commission au parrain après l'achat d'un de ses produits proposés
require MSK_WC_COMMISSION_DIR_PATH . 'inc/part4_commission_gain.php';

// Partie 5 : permettre au parrain d'utiliser ses gains de commissions comme bons d'achats
require MSK_WC_COMMISSION_DIR_PATH . 'inc/part5_commission_use.php';

// Partie 6 : créer un cron WordPress pour envoyer quotidiennement à chaque parrain un résumé des achats de ses produits
require MSK_WC_COMMISSION_DIR_PATH . 'inc/part6_cron_email.php';

// Partie 7 : ajouter un onglet dans l'espace Mon Compte de WooCommerce pour lister les commissions
require MSK_WC_COMMISSION_DIR_PATH . 'inc/part7_my_account_commissions.php';