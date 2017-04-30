<?php

/* ####################################################################
#######################################################################
		Fonctions PHP utiles ici et là
#######################################################################
#####################################################################*/

/**
 * Permet d'insérer un élément dans un tableau associatif juste après une certaine clef
 */
function array_insert_after($key, array &$array, $new_key, $new_value) {
	if (array_key_exists ($key, $array)) {
		$new = array();
	
		foreach ($array as $k => $value) {
			$new[$k] = $value;
			if ($k === $key) {
				$new[$new_key] = $new_value;
			}
		}

		return $new;
	}

	return false;
}


/**
 * Implose un tableau et ses sous-tableaux
 */
function multi_implode($glue, $array) {
	$ret = '';

	if (is_array($array)) {
		foreach ($array as $item) {
			if (is_array($item)) {
				$ret .= multi_implode($item, $glue) . $glue;
			} else {
				$ret .= $item . $glue;
			}
		}

		$ret = substr($ret, 0, 0-strlen($glue));

		return $ret;
	} else {
		return $array;
	}
}


/**
* Transforme un tableau $_FILES['key'] en multiple sous-tableau (au lieu de grouper les clés comme sur http://media.mosaika.fr/iOFQ)
*/
function diverse_array($vector) {
    $result = array();
    foreach ($vector as $key1 => $value1) {
        foreach ($value1 as $key2 => $value2) {
            $result[$key2][$key1] = $value2;
        }
    }

    return $result;
} 


/* ####################################################################
#######################################################################
		Fonctions propres à ce tutoriel
#######################################################################
#####################################################################*/

/**
 * Permet d'organiser les messages d'erreurs
 */
function msk_get_current_errors($array = false, $key = 'errors') {
	if ($array && isset($array[$key])) {
		$errors_array = explode(',', $array[$key]);

		if (is_array($errors_array)) {
			foreach ($errors_array as $error) {
				$error_array = explode(':', $error);
				$errors[$error_array[0]] = sprintf(__('Erreur sur le champ <em>%1$s</em> : <strong>%2$s</strong>.', 'mosaika'), $error_array[0], $error_array[1]);
			}
		}

		return $errors;
	}

	return false;
}


/**
 * Affiche les messages d'erreurs
 */
function msk_display_errors($errors = false) {
	if (is_array($errors) && count($errors) > 0) { ?>
	<div class="alert error">
		<h5 class="alert-title"><?php _e('Oups, petit problème !', 'mosaika'); ?></h5>
		<p><?php echo implode('</p><p>', array_values($errors)); ?></p>
	</div>
	<?php }
}


/**
 * Permet de vérifier un tableau de données selon des règles de validation
 */
function msk_validate_data($data, $validation_rules) {
	$errors = array();

	foreach ($data as $key => $value) {
		if (array_key_exists($key, $validation_rules)) {
			if (in_array('required', $validation_rules[$key])) {
				if (!isset($value) || $value == '') $errors[] = $key . ':is_required';
			}

			if (in_array('is_admin', $validation_rules[$key])) {
				if ($value == 'on' && !current_user_can('manage_options')) $errors[] = $key . ':no_admin_permission';
			}

			unset($validation_rules[$key]);
		}
	}

	if (count($validation_rules) > 0) $errors[] = implode('+', array_keys($validation_rules)) . ':inexistent_for_validation';

	return $errors;
}


/**
* Upload un fichier dans les médias WP
*/
function msk_upload_file($file = array(), $multiple = false, $attachment_parent = 0) {
	require_once(ABSPATH . 'wp-admin/includes/admin.php');

	if (!$multiple) {
		$attachment_id = msk_create_wp_file_attachment($file, $attachment_parent);

		return $attachment_id;
	} else {
		$files = diverse_array($file);
		$attachments_ids = array();

		foreach ($files as $single_file) {
			$single_attachment_id = msk_create_wp_file_attachment($single_file, $attachment_parent);
			$attachments_ids[] = $single_attachment_id;
		}

		return $attachments_ids;
	}
}


/**
* Créer un post de type "attachment" dans WP
*/
function msk_create_wp_file_attachment($file, $attachment_parent = 0) {
	$file_return = wp_handle_upload($file, array('test_form' => false));

	if (isset($file_return['error']) || isset($file_return['upload_error_handler'])) {
		return ($file_return['error']) || isset($file_return['upload_error_handler']);
	} else {
		$filename = $file_return['file'];
		$attachment = array(
			'post_mime_type' => $file_return['type'],
			'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
			'post_content' => '',
			'post_status' => 'inherit',
			'guid' => $file_return['url']
		);
		$attachment_id = wp_insert_attachment($attachment, $file_return['url'], $attachment_parent);
		
		require_once(ABSPATH . 'wp-admin/includes/image.php');

		$attachment_data = wp_generate_attachment_metadata($attachment_id, $filename);
		wp_update_attachment_metadata($attachment_id, $attachment_data);
		
		if (0 < intval($attachment_id)) {
			return $attachment_id;
		}

		return false;
	}
}


/**
 * On transforme un prix en valeur numérique PHP
 */
function msk_price_to_float($price) {
	return floatval(str_replace(',', '.', wp_strip_all_tags($price)));
}


/**
 * On transforme une valeur monétaire en nombre de points
 */
function msk_money_to_points_value($value) {
	return round($value * 10, 2);
}