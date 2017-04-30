<?php

/* ####################################################################
#######################################################################
		Soumission d'un produit : traitement du formulaire
#######################################################################
#####################################################################*/

/**
 * On intercepte les données lorsque le formulaire de proposition d'un produit est soumis par un utilisateur
 */
function msk_process_product_submission() {
	if (isset($_POST['submit']) && $_POST['submit'] == 'submit-product') {
		check_admin_referer('msk_submit_product');

		$data = (!empty($_POST)) ? $_POST : array();

		$data['errors'] = array();

		$data = apply_filters('msk_do_product_submission', $data);
	}
}
add_action('template_redirect', 'msk_process_product_submission');


/**
 * On valide les données et on prépare un nouveau tableau mieux organisé pour la suite
 */
function msk_preprocess_data_for_product_submission($data) {
	$validation_rules = array(
		'product-title' => array('required'),
		'is-admin' => array('required', 'is_admin')
	);

	$errors = msk_validate_data($data, $validation_rules);

	if (!is_user_logged_in()) $errors[] = 'user:not_logged_in';

	if (empty($errors)) {
		$new_data['product'] = array(
			'title' => sanitize_text_field($data['product-title']),
			'content' => sanitize_text_field($data['product-description']),
			'product_meta' => array()
		);

		$new_data['product']['product_meta']['user_submitted'] = 'on';
		$new_data['product']['product_meta']['commission_user_id'] = get_current_user_id();
		$new_data['product']['product_meta']['commission_rate'] = 5;
		$new_data['product']['product_meta']['commission_date_start'] = date('Y-m-d', strtotime('now'));
		$new_data['product']['product_meta']['commission_date_end'] = date('Y-m-d', strtotime('+6 months'));

		$data = $new_data;
	}

	$data['errors'] = $errors;

	return $data;
}
add_filter('msk_do_product_submission', 'msk_preprocess_data_for_product_submission', 10, 1);


/**
 * On créée un produit WooCommerce au statut 'brouillon'
 */
function msk_create_product_for_product_submision($data) {
	if (empty($data['errors']) && array_key_exists('product', $data)) {
		$product_id = wp_insert_post(
			array(
				'post_type' => 'product',
				'post_content' => $data['product']['content'],
				'post_title' => $data['product']['title'],
				'post_status' => 'pending',
				'post_author' => (current_user_can('manage_options')) ? get_current_user_id() : 1,
			)
		);

		if (!is_wp_error($product_id)) {
			$data['product']['ID'] = $product_id;

			$wc_product = wc_get_product($product_id);

			if ($wc_product && is_array($data['product']['product_meta'])) {
				foreach ($data['product']['product_meta'] as $meta_key => $meta_value) {
					$wc_product->update_meta_data($meta_key, $meta_value);
				}

				$wc_product->save();
			}
		} else {
			$data['errors'][] = 'cant_create_product';
			msk_log('error', 'Impossible de créer un produit', $product_id->get_error_messages());
		}
	}

	return $data;
}
add_filter('msk_do_product_submission', 'msk_create_product_for_product_submision', 20, 1);


/**
 * On uploade les photos et on les assigne au produit
 */
function msk_upload_product_photos_for_product_submision($data) {
	if (empty($data['errors'])) {
		if (!empty($_FILES)) {
			$nb_photos = 0;

			foreach ($_FILES as $key => $file) {
				if ($key == 'product-photo' && !empty($file['name'][0]) && $nb_photos < 5) {
					$photos = array();
					$photos_ids = msk_upload_file($_FILES['product-photo'], true, $data['product']['ID']);

					foreach ($photos_ids as $photo_id) {
						$photo_url = get_post_meta($photo_id, '_wp_attached_file', true);
						update_post_meta($photo_id, 'attachment_type', 'product_submitted_photo');

						$photos[] = array('id' => $photo_id, 'url' => $photo_url);
					}

					$data['photos'] = $photos;

					$nb_photos++;
				}
			}

			if (isset($photos) && count($photos) > 0) {
				$wc_product = wc_get_product($data['product']['ID']);
				$wc_product->set_gallery_image_ids(array_column($photos, 'id'));
				$wc_product->save();
			}
		}
	}

	return $data;
}
add_filter('msk_do_product_submission', 'msk_upload_product_photos_for_product_submision', 30, 1);


/**
 * On envoie des notifications e-mail à l'admin et au parrain
 */
function msk_send_email_notifications_for_product_submision($data) {
	if (empty($data['errors']) && isset($data['product']['ID']) && isset($data['product']['product_meta']['user_submitted'])) {
		if ($data['product']['product_meta']['user_submitted'] == 'on') {
			$product_title = $data['product']['title'];
			$product_description = $data['product']['content'];
			$product_backoffice_url = admin_url(sprintf('post.php?post=%1$d&action=edit', $data['product']['ID']));
			$user_data = get_userdata($data['product']['product_meta']['commission_user_id']);
			$user_login = $user_data->user_login;
			$user_email = $user_data->user_email;
			$home_url = home_url();

			$email_data = compact('product_title', 'product_description', 'product_backoffice_url', 'user_login', 'user_email', 'home_url');

			$subject_admin = 'Nouvelle proposition de produit';
			$body_admin = sprintf(
				__('Bonjour, un nouveau produit %1$s a été proposé par l\'utilisateur %2$s. Découvrez le sur <a href="%3$s">%3$s</a>.', 'mosaika'),
				$product_title,
				$user_login,
				$product_backoffice_url
			);

			wp_mail(get_option('admin_email'), $subject_admin, $body_admin, array('Content-Type: text/html; charset=UTF-8'));

			$subject = 'Merci !';
			$body = sprintf(
				__('Bonjour %1$s, nous avons bien reçu votre produit %2$s. Vous recevrez un e-mail dès qu\'il sera mis en vente dans notre boutique.', 'mosaika'),
				$user_login,
				$product_title
			);

			wp_mail($user_email, $subject, $body, array('Content-Type: text/html; charset=UTF-8'));

			unset($data['user']['user_pass']);
		}
	}

	return $data;
}
add_filter('msk_do_product_submission', 'msk_send_email_notifications_for_product_submision', 50, 1);


/**
 * On redirige vers la page du formulaire
 */
function msk_redirect_after_product_submission($data) {
	if (empty($data['errors'])) {
		$redirect_url = add_query_arg(
			array(
				'notice' => 'product_submitted'
			),
			remove_query_arg(array('product-title', 'product-description', 'is-admin', '_wpnonce', 'errors', 'notice'), wp_get_referer())
		);
	} else {
		unset($data['submit']);
		unset($data['_wp_http_referer']);

		$data = array_map('urlencode', array_merge($data, array('errors' => multi_implode(',', $data['errors']))));

		$redirect_url = add_query_arg(
			$data,
			wp_get_referer()
		);
	}

	wp_redirect($redirect_url);
	exit;
}
add_filter('msk_do_product_submission', 'msk_redirect_after_product_submission', 60, 1);


/**
 * Un peu plus tard : lorsque l'admin publie le produit, on envoie un e-mail au parrain
 */
function msk_notify_parrain_when_product_is_published($post_id) {
	$product = wc_get_product($post_id);
	$user_id = wc_get_product($post_id)->get_meta('commission_user_id', true);
	$user_data = get_userdata($user_id);

	if ($user_data) {
		$user_login = $user_data->user_login;
		$user_email = $user_data->user_email;
		$product_title = $product->get_title();
		$product_url = get_permalink($post_id);

		$subject = 'Votre produit est ene vente dans notre boutique !';
		$body = sprintf(
			__('Bonjour %1$s, votre produit %2$s est en vente sur <a href="%3$s">%3$s</a>.', 'mosaika'),
			$user_login,
			$product_title,
			$product_url
		);

		wp_mail($user_email, $subject, $body, array('Content-Type: text/html; charset=UTF-8'));
	}
}
add_action('publish_product', 'msk_notify_parrain_when_product_is_published', 10, 1);