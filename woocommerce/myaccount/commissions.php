<?php
/**
 * Commissions (points)
 *
 * Shows user commissions / points list / transformation in coupon code
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$points = msk_get_customer_commission_data(WC()->customer->get_id());

if ($points) { ?>

	<section class="points-summary">
		<div class="row">
			<div class="balance">
				<p>
					<?php printf(__('<strong>%1$s</strong> points disponibles', 'mosaika'), msk_money_to_points_value($points['points']['balance'])); ?>
				</p>
			</div>
			<div class="details">
				<p>
					<?php printf(__('<strong>%1$s</strong> points gagnés', 'mosaika'), msk_money_to_points_value($points['points']['gain'])); ?>
				</p>
				<p>
					<?php printf(__('<strong>%1$s</strong> points dépensés', 'mosaika'), msk_money_to_points_value($points['points']['use'])); ?>
				</p>
			</div>
		</div>
	</section>

	<table class="woocommerce-MyAccount-downloads shop_table shop_table_responsive">
		<thead>
			<tr>
				<th><span class="nobr"><?php echo _e('Type', 'mosaika'); ?></span></th>
				<th><span class="nobr"><?php echo _e('Points', 'mosaika'); ?></span></th>
				<th><span class="nobr"><?php echo _e('Détails', 'mosaika'); ?></span></th>
				<th><span class="nobr"><?php echo _e('Date', 'mosaika'); ?></span></th>
			</tr>
		</thead>
		<?php foreach ($points['details'] as $detail) { ?>
			<tr class="<?php esc_attr_e($detail->type); ?>">
				<td class="type" data-title="<?php esc_attr_e('Type', 'mosaika'); ?>">
					<?php if ($detail->type == 'gain') _e('Gain', 'mosaika');
					if ($detail->type == 'use') _e('Dépense', 'mosaika'); ?>
				</td>
				<td class="points" data-title="<?php esc_attr_e('Points', 'mosaika'); ?>">
					<?php if ($detail->type == 'gain') printf('+%1$s', msk_money_to_points_value($detail->amount));
					if ($detail->type == 'use') printf('-%1$s', msk_money_to_points_value($detail->amount)); ?>
				</td>
				<td class="details" data-title="<?php esc_attr_e('Détails', 'mosaika'); ?>">
					<?php if ($detail->type == 'gain') printf(__('Un achat de %2$d x <a href="%1$s"><em>%3$s</em></a>', 'mosaika'), get_permalink($detail->line_product_id), $detail->line_product_quantity, wc_get_product($detail->line_product_id)->get_title());
					if ($detail->type == 'use') printf(__('<a href="%1$s">Votre commande n°%2$d</a>', 'mosaika'), esc_attr(wc_get_order($detail->order_id)->get_view_order_url()), $detail->order_id); ?>
				</td>
				<td class="date" data-title="<?php esc_attr_e('Date', 'mosaika'); ?>">
					<?php echo date('d/m/Y', strtotime($detail->time)); ?>
				</td>
			</tr>
		<?php } ?>
	</table>

<?php } else {

	_e('Vous n\'avez aucun point pour l\'instant.', 'mosaika');

} ?>