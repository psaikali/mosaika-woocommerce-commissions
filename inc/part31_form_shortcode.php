<?php

/*******************
 ******************* Tutoriel expliquant ce fichier : https://mosaika.fr/creer-formulaire-frontend-creation-produit-woocommerce
 *******************/

/* ####################################################################
#######################################################################
		Soumission d'un produit : shortcode
#######################################################################
#####################################################################*/

/**
 * Shortcode pour afficher le formulaire pour proposer un produit
 */
add_shortcode('msk_formulaire_proposer_produit', 'msk_shortcode_product_submission');

function msk_shortcode_product_submission($atts) {
	// On ajoute un champ caché pour savoir si c'est un utilisateur lambda ou l'admin qui remplit le formulaire
	$is_admin_hidden_field = (is_user_logged_in() && current_user_can('manage_options')) ? 'on' : 'off';

	// Valeurs par défaut des champs
	$form_values_default = array(
		'product-title' => '',
		'product-description' => ''
	);

	// On boucle pour nettoyer les valeurs, si elles sont renvoyées par le système en cas d'erreur
	$form_values = array_map('sanitize_text_field', wp_parse_args($_GET, $form_values_default));

	$errors = msk_get_current_errors($_GET);

	ob_start(); ?>

	<form id="form-submit-product" class="row" enctype="multipart/form-data" method="post" action="#">
		<?php msk_display_errors($errors); ?>

		<section class="data">
			<fieldset class="affiliate-data">
				<?php if (!is_user_logged_in()) { ?>
				<p>
					<a href="<?php echo get_permalink(get_option('woocommerce_myaccount_page_id')); ?>"><?php _e('Identifiez-vous ou créez un compte afin de gagner des points à chaque vente d\'un de vos produits proposés.'); ?></a>
				</p>
				<?php } else { $user_data = get_userdata(get_current_user_id()); ?>
				<p>
					<?php printf(
						__('Vous êtes connecté en tant que %1$s (e-mail %2$s) : vos points seront reversés sur ce compte parrain.', 'mosaika'),
						'<strong>' . $user_data->user_login . '</strong>',
						'<strong>' . $user_data->user_email . '</strong>'
					); ?>
				</p>
				<?php } ?>
			</fieldset>
			
			<?php if (is_user_logged_in()) { ?>
			<fieldset class="product-data">
				<div class="input">
					<label for="product-title"><?php _e('Nom du produit', 'mosaika'); ?><span class="required">*</span></label>
					<input type="text" required id="product-title" name="product-title" placeholder="<?php esc_attr_e('Nom du produit', 'mosaika'); ?>" value="<?php esc_attr_e($form_values['product-title']); ?>" />
				</div>

				<div class="input">
					<label for="product-description"><?php _e('Description du produit', 'mosaika'); ?><span class="required">*</span></label>
					<textarea id="product-description" name="product-description" placeholder="<?php esc_attr_e('Description du produit', 'mosaika'); ?>" rows="7"><?php echo esc_textarea($form_values['product-description']); ?></textarea>
				</div>

				<p class="required-text"><span class="required">*</span><?php _e('Champs obligatoires'); ?></p>
			</fieldset>

			<fieldset class="photos">
				<input type="file" id="product-photo" name="product-photo[]" accept="image/jpeg, image/jpg, image/png" multiple class="jfilestyle" data-buttonText="<i class='fa fa-camera'></i> Ajouter une photo" />
			</fieldset>

			<fieldset class="footer">
				<?php wp_nonce_field('msk_submit_product'); ?>
				<input type="hidden" name="is-admin" value="<?php echo $is_admin_hidden_field; ?>" />
				<button class="button" type="submit" name="submit" value="submit-product"><?php _e('Proposer un produit', 'mosaika'); ?></button>
			</fieldset>
			<?php } ?>
		</section>
	</form>

<?php return ob_get_clean();
}