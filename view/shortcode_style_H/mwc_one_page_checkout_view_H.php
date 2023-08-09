<?php

global $woocommerce;

$cart_items            = $woocommerce->cart->get_cart();
$currency              = get_woocommerce_currency_symbol();
$package_product_ids   = self::$package_product_ids;
$package_number_item_2 = self::$package_number_item_2;

if (!empty($package_product_ids)) {

	// ********************************************
	// IMPRESSIONS TRACKING CACHE WP CACHE & REDIS
	// ********************************************

	// retrieve current impressions cache
	$curr_impressions = get_transient('mwco_bundle_impressions');

	// if impressions exist
	if ($curr_impressions) :

		// setup new impressions
		$new_impressions = [];

		// update impressions
		foreach ($curr_impressions as $uid => $views) :
			$new_impressions[$uid] = $views + 1;
		endforeach;

		set_transient('mwco_bundle_impressions', $new_impressions);

	// if impressions do not exist
	else :

		// setup initial impressions array
		$impressions = [];

		// push impressions
		foreach ($package_product_ids as $opt_i => $prod_data) :

			// retrieve correct product id
			if ($prod_data['type'] == 'free') :
				$p_id = $prod_data['id'];
			elseif ($prod_data['type'] == 'off') :
				$p_id = $prod_data['id'];
			else :
				$p_id = $prod_data['prod'][0]['id'];
			endif;

			$impressions[$p_id] = 1;
		endforeach;

		set_transient('mwco_bundle_impressions', $impressions);

	endif;
?>

	<div class="mwc_items_div mwc_package_items_div i_clearfix theme_color_<?= self::$package_theme_color ?>" id="mwc_checkout">

		<input type="hidden" id="shortcode_type" value="op_c">

		<input type="hidden" id="step_2_of_3" value="<?php pll_e('Step 2 of 3: Customer Information', 'woocommerce') ?>">
		<input type="hidden" id="step_3_of_3" value="<?php pll_e('Step 3 of 3: Payment Option', 'woocommerce') ?>">

		<?php if (empty($cart_items)) : ?>
			<input type="hidden" id="mwc_package_is_empty" value="1">
		<?php endif;

		// get product count
		$product_count = count($package_product_ids);

		// current currency
		$current_curr = function_exists('alg_get_current_currency_code') ? alg_get_current_currency_code() : get_option('woocommerce_currency');

		// get default currency
		$default_currency = get_option('woocommerce_currency');

		// get alg exchange rate
		$ex_rate = get_option("alg_currency_switcher_exchange_rate_USD_$current_curr") ? get_option("alg_currency_switcher_exchange_rate_USD_$current_curr") : 1;

		// if product count is 1
		if ($product_count == 1) : ?>
			<div style="display:none">
			<?php endif; ?>

			<div class="row">

				<?php
				$addon_products = self::$package_addon_product_ids;
				$addon_products = explode(',', $addon_products);
				$total_products = count($cart_items);
				$p_i            = 0;
				?>

				<!-- heading top -->
				<div class="col pb-0">
					<h2 class="mwc_checkout_title" style="font-size: 22px;"><?php pll_e('Select Package:', 'woocommerce'); ?></h2>
				</div>

				<div class="col large-7 small-12">

					<?php
					// create array variations data
					$var_data = MWC::$mwc_product_variations;

					// create array variation custom price
					$variation_price = [];
					?>

					<div class="mwc-nav-wrapper row">

						<?php foreach ($package_product_ids as $opt_i => $prod_data) :

							// output contents of $prod to plugin directory
							file_put_contents(MWC_PLUGIN_DIR . 'prod.txt', print_r($prod_data, true), FILE_APPEND);

							$bundle_title           = '';
							$cus_bundle_total_price = 0;

							// free type bundle
							if ($prod_data['type'] == 'free') :

								$p_id = $prod_data['id'];

								if ($prod_data['qty_free'] == 0) :
									$bundle_title = sprintf(__('Buy %s',  'woocommerce'), $prod_data['qty']);
								else :
									$bundle_title = sprintf(__('Buy %s + Get %d FREE', 'woocommerce'), $prod_data['qty'], $prod_data['qty_free']);
								endif;

							// off type bundle
							elseif ($prod_data['type'] == 'off') :

								$p_id = $prod_data['id'];

								if (0 == $prod_data['coupon']) :
									$bundle_title = sprintf(__('Buy %s', 'woocommerce'), $prod_data['qty']);
								else :
									$bundle_title = sprintf(__('Buy %s + Get %d&#37;', 'woocommerce'), $prod_data['qty'], $prod_data['coupon']) . ' ' . __('Off', 'woocommerce');
								endif;

							// product type bundle
							else :
								$p_id         = $prod_data['prod'][0]['id'];
								$bundle_title = $prod_data['title_header'] ?: __('Bundle option', 'woocommerce');
							endif;

							// get product object
							$prod_obj = wc_get_product($p_id);

							// if product count is 1
							if ($product_count == 1) : ?>

								<div class="mwc_package_radio_div">
									<input type="radio" name="mwc_package_checkbox" data-product_id="<?php echo ($p_id) ?>" value="<?php echo ($p_id) ?>" <?php echo ($radio_checked) ?> class="mwc_package_checkbox">
								</div>

								<?php else :

								$product_separate   = 1;

								// package title
								$package_title = isset($prod_data['title_package']) ?
									$prod_data['title_package'] :
									$prod_obj->get_title();

								// product name
								$product_name = isset($prod_data['product_name']) ?
									$prod_data['product_name'] :
									$prod_obj->get_title();

								// product price
								$prod_price = get_post_meta($p_id, '_regular_price', true) ?
									get_post_meta($p_id, '_regular_price', true) :
									get_post_meta($p_id, '_price', true);

								// calculate pricing based on current currency as needed 
								$prod_price =
									get_post_meta($p_id, "_alg_currency_switcher_per_product_regular_price_{$current_curr}", true) ?
									get_post_meta($p_id, "_alg_currency_switcher_per_product_regular_price_{$current_curr}", true) :
									$prod_price * $ex_rate;

								// mwc product option has custom price
								if (key_exists('custom_price', $prod_data)) :

									if (is_array($prod_data['custom_price']) && !empty($prod_data['custom_price'])) :
										if (!is_null(current($prod_data['custom_price'])[$current_curr])) :
											$prod_price      = current($prod_data['custom_price'])[$current_curr];
										endif;
									endif;

								endif;

								// get mwc price variation
								if ($prod_obj->is_type('variable')) :

									// variations loop
									foreach ($prod_obj->get_available_variations() as $key => $value) :

										$variation_price[trim($prod_data['bun_id'])][$value['variation_id']]['variation_id'] = $value['variation_id'];

										// if custom price is set
										if ($prod['custom_price'] && isset($prod['custom_price'][$var_data['variation_id']][$current_curr])) :
											$variation_price[trim($prod['bun_id'])][$var_data['variation_id']]['price'] = $prod['custom_price'][$var_data['variation_id']][$current_curr];

										// if custom price is not set
										else :

											// get regular variation price
											$prod_price = get_post_meta($value['variation_id'], '_regular_price', true) ?
												get_post_meta($value['variation_id'], '_regular_price', true) :
												get_post_meta($value['variation_id'], '_price', true);

											// if current currency is not default currency, get alg price, else calculate price based on exchange rate
											if ($current_curr !== $default_currency) :

												$prod_price =
													get_post_meta($var_data['variation_id'], "_alg_currency_switcher_per_product_regular_price_{$current_curr}", true) ?
													get_post_meta($var_data['variation_id'], "_alg_currency_switcher_per_product_regular_price_{$current_curr}", true) :
													$product_price * $ex_rate;

											// if current currency is default currency, get regular price
											else :
												$variation_price[trim($prod['bun_id'])][$var_data['variation_id']]['price'] = $var_data['display_regular_price'];
											endif;

										endif;

									endforeach;

								endif;

								// get price reg, sale product of package free, off
								// ALG mod added 08 Feb 2023
								if ($prod_obj->is_type('variable')) :

									// get children
									$children = $prod_obj->get_children();

									// loop through children
									foreach ($children as $vid) :

										// get regular price
										$reg_price =
											get_post_meta($vid, '_regular_price', true) ?
											get_post_meta($vid, '_regular_price', true) :
											get_post_meta($vid, '_price', true);

										// get alg price if defined, else calculate price based on exchange rate
										$prod_price =
											get_post_meta($vid, "_alg_currency_switcher_per_product_regular_price_{$current_curr}", true) ?
											get_post_meta($vid, "_alg_currency_switcher_per_product_regular_price_{$current_curr}", true) :
											$reg_price * $ex_rate;

									endforeach;

								// if is simple product
								else :

									// get regular price
									$reg_price =
										get_post_meta($vid, '_regular_price', true) ?
										get_post_meta($vid, '_regular_price', true) :
										get_post_meta($vid, '_price', true);

									// get alg price if defined, else calculate price based on exchange rate
									$prod_price =
										get_post_meta($vid, "_alg_currency_switcher_per_product_regular_price_{$current_curr}", true) ?
										get_post_meta($vid, "_alg_currency_switcher_per_product_regular_price_{$current_curr}", true) :
										$reg_price * $ex_rate;

								endif;

								// *****************
								// CALCULATE PRICES
								// *****************

								// buy x get x free
								if ($prod_data['type'] == 'free') :

									$total_prod_qty     = $prod_data['qty'] + $prod_data['qty_free'];
									$bundle_price       = ($prod_price * $prod_data['qty']) / $total_prod_qty;
									$sum_price_regular  = $prod_price * $total_prod_qty;
									$bundle_price_total = $bundle_price * $total_prod_qty;
									$price_discount     = ($prod_price * $total_prod_qty) - $bundle_price_total;
									$bundle_coupon      = ($prod_data['qty_free'] * 100) / $total_prod_qty;

									// js input data package
									$js_discount_type  = 'free';
									$js_discount_qty   = $prod_data['qty_free'];
									$js_discount_value = $prod_data['id_free'];

								// buy x get x off
								elseif ($prod_data['type'] == 'off') :

									$total_prod_qty     = $prod_data['qty'];
									$i_tt               = $prod_price * $prod_data['qty'];
									$bundle_coupon      = $prod_data['coupon'];
									$bundle_price       = $prod_price - ($prod_price * ($bundle_coupon / 100));
									$sum_price_regular  = $prod_price * $prod_data['qty'];
									$bundle_price_total = $bundle_price * $prod_data['qty'];
									$price_discount     = $i_tt - $bundle_price_total;

									// js input data package
									$js_discount_type  = 'percentage';
									$js_discount_qty   = 1;
									$js_discount_value = $prod_data['coupon'];

								// buy product bundle
								else :

									// total product qty
									$total_prod_qty = count($prod_data['prod']);

									// bundle price
									$bundle_price   = $prod_data['total_price'] * $ex_rate;

									// js input data package
									$js_discount_type  = 'percentage';
									$js_discount_qty   = 1;
									$js_discount_value = $prod_data['discount_percentage'];

									// holds sum of regular prices
									$sum_price_regular = 0;

									// loop through products
									foreach ($prod_data['prod'] as $i => $i_prod) :

										// retrieve regular price
										$prod_price =
											get_post_meta($i_prod['id'], '_regular_price', true) ?
											get_post_meta($i_prod['id'], '_regular_price', true) :
											get_post_meta($i_prod['id'], '_price', true) * $i_prod['qty'];

										// retrieve product object
										$prod_obj = wc_get_product($i_prod['id']);

										// if not default currency, retrieve converted price, or calculate converted price
										if ($current_curr !== $default_currency) :

											// if alg price is defined, use that, else calculate price based on exchange rate
											$prod_price =
												get_post_meta($i_prod['id'], "_alg_currency_switcher_per_product_regular_price_{$current_curr}", true) ?
												get_post_meta($i_prod['id'], "_alg_currency_switcher_per_product_regular_price_{$current_curr}", true) * $i_prod['qty'] : ($prod_price * $ex_rate) * $i_prod['qty'];

											// add price to total
											$sum_price_regular = $sum_price_regular + $prod_price;

										// if default currency, add price to total
										else :
											$sum_price_regular = $sum_price_regular + $prod_price;
										endif;

									endforeach;

									// discount percent
									$bundle_coupon = $prod_data['discount_percentage'];

									// get price total bundle
									if ($bundle_price) :
										$sum_price_regular      = $bundle_price;
										$cus_bundle_total_price = $bundle_price;
									endif;

									// bundle price total
									$subtotal_bundle = $sum_price_regular;

									// apply discount percentage
									if ($prod_data['discount_percentage'] > 0) :
										$subtotal_bundle = $subtotal_bundle - ($subtotal_bundle * ($bundle_coupon / 100));
									endif;

									// calculate discount
									$price_discount = $sum_price_regular - $subtotal_bundle;
								endif;

								//prevent Addon product to display here
								if (in_array($p_id, $addon_products)) :
									continue;
								endif;

								// if bundle type is free or off set
								if ($prod_data['type'] == 'free' || $prod_data['type'] == 'off') : ?>

									<div data-bundle-data="<?php echo base64_encode(json_encode($prod_data)) ?>" class="col-lg-4 col-md-4 col-xs-12 col-sm-12 item-selection item-selection-h col-hover-focus mwc_item_div template_h mwc_item_div_<?php echo (trim($prod_data['bun_id'])) ?> op_c_package_option <?= (self::$package_default_id == $prod_data['bun_id']) ? 'mwc_selected_default_opt' : '' ?>" data-type="<?php echo ($prod_data['type']) ?>" data-bundle_id="<?php echo (trim($prod_data['bun_id'])) ?>" data-coupon="<?= round($bundle_coupon, 0) ?>">

									<?php
								// if bundle type is product bundle
								else : ?>

										<div data-bundle-data="<?php echo base64_encode(json_encode($prod_data)) ?>" class="col-lg-4 col-md-4 col-xs-12 col-sm-12 item-selection item-selection-h col-hover-focus mwc_item_div template_h mwc_item_div_<?php echo (trim($prod_data['bun_id'])) ?> op_c_package_option <?= (self::$package_default_id == $prod_data['bun_id']) ? 'mwc_selected_default_opt' : '' ?>" data-type="<?php echo ($prod_data['type']) ?>" data-bundle_id="<?php echo (trim($prod_data['bun_id'])) ?>" data-coupon="<?= round($bundle_coupon, 0) ?>">

										<?php endif; ?>

										<!-- js input hidden data package -->
										<input type="hidden" class="js-input-discount_package" data-type="<?php echo $js_discount_type ?>" data-qty="<?php echo $js_discount_qty ?>" value="<?php echo $js_discount_value ?>">
										<input type="hidden" class="js-input-cus_bundle_total_price" value="<?php echo $cus_bundle_total_price ?>">

										<!-- results -->
										<input type="hidden" class="js-input-price_package" value="">
										<input type="hidden" class="js-input-price_summary" value="">

										<div class="col-inner">

											<div class="op_c_package_content" data-hover-image="<?php echo isset($prod_data['image_package_hover']) ? $prod_data['image_package_hover'] : '' ?>">

												<div class="mwc-checkbox-wrapper">
													<label class="check_box">
														<?php if ($prod_data['type'] == 'free' || $prod_data['type'] == 'off') :	?>
															<input type="radio" name="mwc_package_checkbox" data-product_id="<?php echo ($p_id) ?>" data-index="<?php echo ($opt_i) ?>" value="<?php echo ($p_id) ?>" class="mwc_package_checkbox product_id">
														<?php else :	?>
															<input type="radio" name="mwc_package_checkbox" data-product_id="<?php echo ($prod_data['bun_id']) ?>" data-index="<?php echo ($opt_i) ?>" value="<?php echo ($prod_data['bun_id']) ?>" class="mwc_package_checkbox product_id">
														<?php endif; ?>
														<span class="checkmark"></span>
													</label>
												</div>

												<!-- show popular -->
												<?php if ($prod_data['popularity'] && $prod_data['popularity'] != 'none') : ?>
													<h3 class="mwc-popularity-text mb-0">
														<?php
														if ($prod_data['popularity'] == 'best-seller') :
															echo __('Best Seller', 'woocommerce');
														elseif ($prod_data['popularity'] == 'popular') :
															echo __('Popular', 'woocommerce');
														else :
															echo __('Moderate', 'woocommerce');
														endif;
														?>
													</h3>
												<?php endif; ?>

												<h2 class="mwc-title mt-2 mb-0"><?php echo $package_title ?></h2>

												<!-- discount percent -->
												<?php if (0 != $bundle_coupon) : ?>
													<h4 class="mwc-discount-text mt-0 mb-0"><?php pll_e('Save', 'woocommerce') ?>: <?php echo (round($bundle_coupon, 0)) ?>%</h4>
												<?php endif; ?>

												<div class="op_c_package_image mt-2 mb-0">
													<?php if (wp_is_mobile() && $prod_data['image_package_mobile']) : ?>
														<img src="<?php echo $prod_data['image_package_mobile'] ?>" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" alt="">
													<?php elseif ($prod_data['image_package_desktop']) :	?>
														<img src="<?php echo $prod_data['image_package_desktop'] ?>" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" alt="">
														<?php if ($prod_data['image_package_hover']) : ?>
															<img class="hover-image" src="<?php echo $prod_data['image_package_hover']; ?>" alt="hover image" style="display: none;">
														<?php endif; ?>
														<?php else :
														if ($prod_data['image_package_hover']) : ?>
															<img class="hover-image" src="<?php echo $prod_data['image_package_hover']; ?>" alt="hover image" style="display: none;">
													<?php endif;
													endif;
													?>
												</div>

												<?php if ($prod_data['type'] == 'bun') : ?>
													<h3 class="mwc-sub-price mt-4 mb-0">
														<strong><?= __('Bundle price', 'woocommerce') ?>:</strong>
														<span class="js-label-price_total"><?php echo wc_price($subtotal_bundle, ['ex_tax_label' => false, $current_curr]); ?></span>
													</h3>

													<h4 class="mwc-total-price mt-2">
														<strong><?php pll_e('Total', 'woocommerce') ?>:</strong>
														<span class="js-label-price_total"><?php echo wc_price($subtotal_bundle, ['ex_tax_label' => false, $current_curr]); ?></span>
													</h4>

													<!-- get prices bundle -->
													<input type="hidden" class="mwc_bundle_price_hidden" data-label="<?= $bundle_title ?>" value="<?= $subtotal_bundle ?>">
													<input type="hidden" class="mwc_bundle_price_regular_hidden" data-label="<?= __('Old Price', 'woocommerce') ?>" value="<?= $sum_price_regular ?>">
													<input type="hidden" class="mwc_bundle_price_sale_hidden" data-label="<?= __('Old Price', 'woocommerce') ?>" value="<?= $sum_price_regular ?>">
													<input type="hidden" class="mwc_bundle_product_qty_hidden" value="1">

												<?php else :	?>

													<h3 class="mwc-sub-price mt-4 mb-0">
														<strong><?php echo wc_price($bundle_price, ['ex_tax_label' => false, 'currency' => $current_curr]); ?></strong>
														<span class="pi-price-each-txt"> / <?php echo __('each', 'woocommerce'); ?></span>
													</h3>

													<h4 class="mwc-total-price mt-2">
														<?php echo wc_price($sum_price_regular, ['ex_tax_label' => false, 'currency' => $current_curr]); ?> <?php echo wc_price($bundle_price_total, ['ex_tax_label' => false, 'currency' => $current_curr]); ?>
													</h4>

													<!-- get prices bundle -->
													<input type="hidden" class="mwc_bundle_price_hidden" data-label="<?= $bundle_title ?>" value="<?= $bundle_price_total ?>">
													<input type="hidden" class="mwc_bundle_price_regular_hidden" data-label="<?= __('Old Price', 'woocommerce') ?>" value="<?= $sum_price_regular ?>">
													<input type="hidden" class="mwc_bundle_price_sale_hidden" data-label="<?= __('Sale Price', 'woocommerce') ?>" value="<?= $sum_price_regular - $price_discount ?>">
													<input type="hidden" class="mwc_bundle_product_qty_hidden" value="<?= $total_prod_qty ?>">

												<?php endif; ?>

											</div> <!-- end op_c_package_content -->
										</div>
										</div>
								<?php endif;
							$p_i++;
						endforeach;
								?>
									</div>
									<div class="mwc-tab-content">
										<?php

										// Tab content (variation dropdowns)
										foreach ($package_product_ids as $opt_i => $prod_data) :

											/**
											 * Code which checks for presence of variable
											 */
											$bundle_ptypes_string = '';

											$bun_type = $prod_data['type'];

											if ($bun_type === 'bun') :

												$prods = $prod_data['prod'];

												foreach ($prods as $index => $pdata) :
													$product = wc_get_product($pdata['id']);
													$bundle_ptypes_string .= $product->get_type();
												endforeach;

											else :

												$product = wc_get_product($prod_data['id']);
												$bundle_ptypes_string .= $product->get_type();

											endif;

											// only display variations dropdown if variable product present 
											if (is_int(strpos($bundle_ptypes_string, 'variable'))) :
										?>

												<!-- Product variations form ------------------------------>
												<div hidden class="mwc_product_variations mwc_product_variations_<?php echo (trim($prod_data['bun_id'])) ?> info_products_checkout" data-bundle_id="<?php echo (trim($prod_data['bun_id'])) ?>">
													<h4 class="title_form"><?= __('Please choose:', 'woocommerce') ?> <h4>
															<table class="product_variations_table">
																<tbody>
																	<?php

																	//package selection variations free and off
																	if ($prod_data['type'] === 'free' || $prod_data['type'] === 'off') :

																		// retrieve product object
																		$prod_obj = wc_get_product($prod_data['id']);

																		// get variation images product
																		if (!isset($var_data[$prod_data['id']]) && $prod_obj->is_type('variable')) :

																			$var_arr = [];

																			foreach ($prod_obj->get_available_variations() as $key => $value) :

																				array_push($var_arr, [
																					'id'         => $value['variation_id'],
																					'price'      => isset($prod_data['custom_price'][$value['variation_id']]) ? $prod_data['custom_price'][$value['variation_id']][$current_curr] : '',
																					'attributes' => $value['attributes'],
																					'image'      => $value['image']['url']
																				]);

																			endforeach;

																			$var_data[$prod_data['id']] = $var_arr;
																		endif;

																		for ($i = 0; $i < $prod_data['qty']; $i++) :
																	?>
																			<tr class="c_prod_item" data-id="<?php echo ($prod_data['id']) ?>" <?= (!$prod_obj->is_type('variable')) ? 'hidden' : '' ?>>
																				<?php if ($prod_obj->is_type('variable')) : ?>
																					<td class="variation_index"><?= $i + 1 ?></td>
																					<td class="variation_img">
																						<img class="mwc_variation_img" src="<?= wp_get_attachment_image_src($prod_obj->get_image_id())[0] ?>">
																					</td>
																					<td class="variation_selectors">
																						<?php

																						// show variations linked by variations
																						echo MWC::mwc_return_linked_variations_dropdown([
																							'product_id'		=> $prod_data['id'],
																							'class' 			=> 'var_prod_attr checkou_prod_attr select-variation-' . $prod_data['type'],
																						], $var_data, $prod_data);

																						$prod_variations = $prod_obj->get_variation_attributes();

																						foreach ($prod_variations as $attribute_name => $options) :
																							// $default_opt = $prod_obj->get_variation_default_attribute($attribute_name);
																							$default_opt = '';
																							try {
																								$default_opt =  key_exists($attribute_name, $prod_obj->get_default_attributes()) ? $prod_obj->get_default_attributes()[$attribute_name] : '';
																							} catch (\Throwable $th) {
																							}
																						?>

																							<div class="variation_item">
																								<p class="variation_name"><?= wc_attribute_label($attribute_name) ?>: </p>

																								<!-- load dropdown variations -->
																								<?php
																								echo MWC::mwc_return_onepage_checkout_variation_dropdown([
																									'product_id'		=> $prod_data['id'],
																									'options' 			=> $options,
																									'attribute_name'	=> $attribute_name,
																									'default_option'	=> $default_opt,
																									'var_data'			=> $var_data[$p_id],
																									'class' 			=> 'var_prod_attr checkou_prod_attr select-variation-' . $prod_data['type'],
																								]);
																								?>

																							</div>
																						<?php endforeach; ?>
																					</td>
																				<?php endif; ?>
																			</tr>
																			<?php
																		endfor;
																	else : //package selection bundle

																		$_index = 1;

																		foreach ($prod_data['prod'] as $i => $i_prod) :

																			$p_id       = $i_prod['id'];
																			$b_prod_obj = wc_get_product($i_prod['id']);

																			// get variation images product
																			if (!isset($var_data[$i_prod['id']]) && $b_prod_obj->is_type('variable')) :

																				$var_arr = [];

																				foreach ($b_prod_obj->get_available_variations() as $key => $value) :

																					array_push($var_arr, [
																						'id'         => $value['variation_id'],
																						'price'      => isset($prod_data['custom_price']) ? $prod_data['custom_price'][$value['variation_id']][$current_curr] : '',
																						'attributes' => $value['attributes'],
																						'image'      => $value['image']['url']
																					]);

																				endforeach;

																				$var_data[$i_prod['id']] = $var_arr;

																			endif;

																			for ($i = 1; $i <= $i_prod['qty']; $i++) : ?>
																				<tr class="c_prod_item" data-id="<?php echo ($i_prod['id']) ?>" <?= (!$b_prod_obj->is_type('variable')) ? 'hidden' : '' ?>>
																					<?php if ($b_prod_obj->is_type('variable')) {
																					?>
																						<td class="variation_index"><?= $_index++ ?></td>
																						<td class="variation_img">
																							<img id="prod_image" class="mwc_variation_img" src="<?= wp_get_attachment_image_src($b_prod_obj->get_image_id())[0] ?>">
																						</td>
																						<td class="variation_selectors">
																							<?php

																							// show variations linked by variations
																							echo MWC::mwc_return_linked_variations_dropdown([
																								'product_id'		=> $i_prod['id'],
																								'class' 			=> 'var_prod_attr checkou_prod_attr select-variation-' . $prod_data['type'],
																							], $var_data, $prod_data);

																							$prod_variations = $b_prod_obj->get_variation_attributes();

																							foreach ($prod_variations as $attribute_name => $options) :
																								$default_opt = '';
																								try {
																									$default_opt =  key_exists($attribute_name, $prod_obj->get_default_attributes()) ? $prod_obj->get_default_attributes()[$attribute_name] : '';
																								} catch (\Throwable $th) {
																									$default_opt = '';
																								}
																							?>
																								<div class="variation_item">
																									<p class="variation_name"><?= wc_attribute_label($attribute_name) ?>: </p>

																									<!-- load dropdown variations -->
																									<?php
																									echo MWC::mwc_return_onepage_checkout_variation_dropdown([
																										'product_id'		=> $i_prod['id'],
																										'options' 			=> $options,
																										'attribute_name'	=> $attribute_name,
																										'default_option'	=> $default_opt,
																										'var_data'			=> $var_data[$i_prod['id']],
																										'class' 			=> 'var_prod_attr checkou_prod_attr select-variation-' . $prod_data['type'],
																									]);
																									?>

																								</div>
																							<?php endforeach; ?>
																						</td>
																					<?php
																					}
																					?>
																				</tr>
																	<?php endfor;
																		endforeach;
																	endif;
																	?>
																</tbody>
															</table>

															<!-- variations free products -->
															<?php if ($prod_data['type'] == 'free' && isset($prod_data['qty_free']) && $prod_data['qty_free'] > 0) :	?>

																<h5 class="title_form"><?= __('Select Free Product:', 'woocommerce') ?>:</h5>

																<table class="product_variations_table">
																	<tbody>

																		<?php for ($i = 0; $i < $prod_data['qty_free']; $i++) :	?>
																			<tr class="c_prod_item free-item" data-id="<?php echo ($prod_data['id']) ?>" <?= (!$prod_obj->is_type('variable')) ? 'hidden' : '' ?>>
																				<?php if ($prod_obj->is_type('variable')) : ?>
																					<td class="variation_index"><?= $i + 1 ?></td>
																					<td class="variation_img">
																						<img class="mwc_variation_img" src="<?= wp_get_attachment_image_src($prod_obj->get_image_id())[0] ?>">
																					</td>
																					<td class="variation_selectors">
																						<?php

																						// show variations linked by variations
																						echo MWC::mwc_return_linked_variations_dropdown([
																							'product_id'		=> $prod_data['id'],
																							'class' 			=> 'var_prod_attr checkou_prod_attr select-variation-' . $prod_data['type'],
																						], $var_data, $prod_data);

																						$prod_variations = $prod_obj->get_variation_attributes();

																						foreach ($prod_variations as $attribute_name => $options) :
																							// $default_opt = $prod_obj->get_variation_default_attribute($attribute_name);
																							$default_opt = '';
																							try {
																								$default_opt =  key_exists($attribute_name, $prod_obj->get_default_attributes()) ? $prod_obj->get_default_attributes()[$attribute_name] : '';
																							} catch (\Throwable $th) {
																								$default_opt = '';
																							}
																						?>
																							<div class="variation_item">
																								<p class="variation_name"><?= wc_attribute_label($attribute_name) ?>: </p>

																								<!-- load dropdown variations -->
																								<?php
																								echo MWC::mwc_return_onepage_checkout_variation_dropdown([
																									'product_id'		=> $prod_data['id'],
																									'options' 			=> $options,
																									'attribute_name'	=> $attribute_name,
																									'default_option'	=> $default_opt,
																									'var_data'			=> $var_data[$prod_data['id']],
																									'class' 			=> 'var_prod_attr checkou_prod_attr select-variation-' . $prod_data['type'] . ' free-prod',
																								]);
																								?>

																							</div>
																						<?php endforeach; ?>
																					</td>
																				<?php endif; ?>
																			</tr>
																		<?php endfor; ?>
																	</tbody>
																</table>

															<?php
															endif;

															// Size chart
															if (defined('SBHTML_VERSION')) :
																do_action('mwc_size_chart', $prod_data['id']);
															endif;
															?>
												</div>

										<?php endif;
										endforeach; ?>

									</div><!-- .mwc-tab-content -->

									<div id="order_summary" hidden>
										<div class="row">
											<div class="col-lg-4 col-md-3 col-sm-12 col-xs-12" id="s_image" style="text-align: center;">
												<img width="247" height="296" src="" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" alt="">
											</div>

											<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
												<div class="col-inner box-shadow-2 box-shadow-3-hover box-item">
													<div class="op_c_summary i_row i_clearfix">
														<div class="sumary_header">
															<span class="header_text"><?php pll_e('Order Summary', 'woocommerce') ?></span>
														</div>

														<div class="op_c_package_content" style="display: block; text-align: left">
															<div style="width: 100%;border-bottom: solid 1px #E9E9E9; padding-right: 10px">
																<strong><?php pll_e("Item", 'woocommerce') ?></strong>
																<strong style="float: right;"><?php pll_e('Price', 'woocommerce') ?></strong>
															</div>

															<div class="mwc_summary_table"></div>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div> <!-- end col order summary -->

					</div> <!-- end col large-7 packages -->


					<!-- checkout form woo -->
					<div id="op_c_loading" class="col large-5 small-12" style="text-align:center;">
						<img src="<?php echo (MWC_PLUGIN_URL . 'images/loading.gif') ?>" id="i_loading_img">
					</div>
					<div class="col large-5 small-12 op_c_checkout_form" hidden>

						<?php
						// Get checkout object for WC 2.0+
						$checkout = WC()->checkout();
						wc_get_template('checkout/form-checkout.php', array('checkout' => $checkout));
						?>
					</div>
				</div>
			</div>

			<!-- see more button script -->
			<script id="mwc_see_more">
				$ = jQuery;

				// if more than 4 items, append See More button to main container
				if ($('.mwc_item_div').length > 3) {
					$('.mwc-nav-wrapper').append('<div class="col-lg-12 col-xs-12 col-sm-12"><button id="mwc_template_h_see_more" class="button alt button-small">' + '<?php _e('See More', 'woocommerce') ?>' + '</button></div>')
				}

				// hide 4th item onwards on load
				$('.mwc_item_div').each(function(index, element) {
					if (index > 2) {
						$(element).hide();
					}
				});

				// see more/see less button on click
				$('#mwc_template_h_see_more').click(function(e) {

					e.preventDefault();

					// if all bundles are showing
					if ($(this).hasClass('see_more_open')) {

						// hide shown items
						$('.mwc_item_div').each(function(index, element) {
							if (index > 2) {
								$(element).slideUp(200);
							}
						});

						// change text
						$(this).text('<?php _e('See More', 'woocommerce') ?>').removeClass('see_more_open');

						// if not all bundles are showing
					} else {

						// remove active class from any active bundles and show hidden bundles
						$('.mwc_item_div').slideDown(200);

						// change button text
						$(this).text('<?php _e('See Less', 'woocommerce') ?>').addClass('see_more_open');
					}
				});
			</script>

		<?php
	}
