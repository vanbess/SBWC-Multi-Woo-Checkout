<?php
global $woocommerce;

$cart_items            = $woocommerce->cart->get_cart();
$currency              = get_woocommerce_currency_symbol();
$package_product_ids   = self::$package_product_ids;
$package_number_item_2 = self::$package_number_item_2;

if (!empty($package_product_ids)) :

	// count package products
	$product_count = count($package_product_ids);

	// ********************************************
	// IMPRESSIONS TRACKING CACHE WP CACHE & REDIS
	// ********************************************

	// retrieve current impressions cache/transient
	$curr_impressions = maybe_unserialize(get_transient('mwco_bundle_impressions'));

	// if impressions exist
	if ($curr_impressions !== false) :

		// setup new impressions
		$new_impressions = [];

		// update impressions
		foreach ($curr_impressions as $uid => $views) :
			$new_impressions[$uid] = $views + 1;
		endforeach;

		set_transient('mwco_bundle_impressions', maybe_serialize($new_impressions), 360);

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

		set_transient('mwco_bundle_impressions', maybe_serialize($impressions), 360);

	endif;

?>

	<div class="mwc_items_div mwc_package_items_div i_clearfix theme_color_<?= self::$package_theme_color ?>" id="mwc_checkout">

		<input type="hidden" id="shortcode_type" value="op_c">

		<input type="hidden" id="step_2_of_3" value="<?php pll_e('Step 2 of 3: Customer Information', 'woocommerce') ?>">
		<input type="hidden" id="step_3_of_3" value="<?php pll_e('Step 3 of 3: Payment Option', 'woocommerce') ?>">

		<?php if (empty($cart_items)) : ?>
			<input type="hidden" id="mwc_package_is_empty" value="1">
		<?php endif;

		// current currency
		$current_curr = function_exists('alg_get_current_currency_code') ? alg_get_current_currency_code() : get_option('woocommerce_currency');

		// get default currency
		$default_currency = get_option('woocommerce_currency');

		// get alg exchange rate
		$ex_rate = get_option("alg_currency_switcher_exchange_rate_{$default_currency}_{$current_curr}") ? get_option("alg_currency_switcher_exchange_rate_{$default_currency}_{$current_curr}") : 1;

		?>

		<div>
			<div class="row">

				<?php
				$addon_products = self::$package_addon_product_ids;
				$addon_products = explode(',', $addon_products);
				$total_products = count($cart_items);
				$p_i            = 0;
				?>

				<div class="col pb-0">
					<h3 class="mwc_checkout_title" style="font-size: calc(1.8rem * var(--rio-typo-ratio,1));"><?php pll_e('Select Package:', 'woocommerce') ?></h3>
				</div>

				<div class="col-lg-7 col-md-7 col-sm-4 col-xs-4">

					<?php
					// create array variations data
					$var_data = MWC::$mwc_product_variations;

					// create array variation custom price
					$variation_price = [];

					foreach ($package_product_ids as $opt_i => $prod_data) :

						// output contents of $prod to plugin directory
						// file_put_contents(MWC_PLUGIN_DIR . 'prod.txt', print_r($prod_data, true));

						$bundle_title           = '';
						$cus_bundle_total_price = 0;

						// if product type is free
						if ($prod_data['type'] == 'free') :

							// $type = 'free';

							$p_id = $prod_data['id'];

							if ($prod_data['qty_free'] == 0) :
								$bundle_title = sprintf(__('Buy %s', 'woocommerce'), $prod_data['qty']);
							else :
								$bundle_title = sprintf(__('Buy %s + Get %d FREE', 'woocommerce'), $prod_data['qty'], $prod_data['qty_free']);
							endif;

						// if product type is % off/discount
						elseif ($prod_data['type'] == 'off') :

							// $type    = 'off';
							$p_id = $prod_data['id'];

							if (0 == $prod_data['coupon']) :
								$bundle_title = sprintf(__('Buy %s', 'woocommerce'), $prod_data['qty']);
							else :
								$bundle_title = sprintf(__('Buy %s + Get %d&#37;', 'woocommerce'), $prod_data['qty'], $prod_data['coupon']) . ' ' . __('Off', 'woocommerce');
							endif;

						// if product type bundle (default apparently)
						else :
							// $type    = 'default';
							$p_id = $prod_data['prod'][0]['id'];
							$bundle_title = $prod_data['title_header'] ?: __('Bundle option', 'woocommerce');
						endif;

						// retrieve product object
						$prod_obj = wc_get_product($p_id);

						// if no product object returned, continue
						if (!is_object($prod_obj)) :
							continue;
						endif;

						// if product count === 1
						if ($product_count == 1) :	?>

							<div class="mwc_package_radio_div">
								<input type="radio" name="mwc_package_checkbox" data-product_id="<?php echo ($p_id) ?>" value="<?php echo ($p_id) ?>" <?php echo ($radio_checked) ?> class="mwc_package_checkbox">
							</div>

							<?php

						// if product count is bigger than 1
						else :

							$product_separate   = 1;
							$product_title      = isset($prod_data['title_package']) ? $prod_data['title_package'] : $prod_obj->get_title();
							$product_name       = isset($prod_data['product_name']) && $prod_data['product_name'] !== '' ? $prod_data['product_name'] : $prod_obj->get_title();

							// get product object
							$prod_obj = wc_get_product($p_id);

							// set prod price to regular price
							$prod_price = $prod_obj->get_regular_price();

							// mwc product option has custom price
							if (isset($prod_data['custom_price']) && !empty($prod_data['custom_price'])) :
								if (current($prod_data['custom_price'])[$current_curr]) :
									$prod_price = current($prod_data['custom_price'])[$current_curr];
								endif;
							endif;

							// get mwc price variation
							if ($prod_obj->is_type('variable')) :

								foreach ($prod_obj->get_available_variations() as $key => $value) :

									$variation_price[trim($prod_data['bun_id'])][$value['variation_id']]['variation_id'] = $value['variation_id'];

									// custom price if set
									if ($prod['custom_price'] && isset($prod['custom_price'][$var_data['variation_id']][$current_curr])) :
										$variation_price[trim($prod['bun_id'])][$var_data['variation_id']]['price'] = $prod['custom_price'][$var_data['variation_id']][$current_curr];
									else :

										// get variation product object
										$var_prod_obj = wc_get_product($value['variation_id']);

										// get regular price
										$var_data['display_regular_price'] = $var_prod_obj->get_regular_price();

									endif;

								endforeach;

							endif;

							// get price reg, sale product of package free, off
							// ALG mod added 08 Feb 2023
							if ($prod_obj->is_type('variable')) :

								// get children
								$children = $prod_obj->get_children();

								foreach ($children as $vid) :
									foreach ($children as $vid) :

										// get product object
										$prod_obj = wc_get_product($vid);

										// retrieve regular price
										$prod_price = $prod_obj->get_regular_price();

									endforeach;
								endforeach;

							else :

								// get product object
								$prod_obj = wc_get_product($p_id);

								// retrieve regular price
								$prod_price = $prod_obj->get_regular_price();

							endif;

							/********************
							 * CALCULATE PRICES
							 ********************/

							// Buy x get x free
							if ($prod_data['type'] == 'free') :

								$total_prod_qty       = (int)$prod_data['qty'] + (int)$prod_data['qty_free'];
								$bundle_coupon        = ((int)$prod_data['qty_free'] / $total_prod_qty) * 100;
								$to_subtract          = ($prod_price * $bundle_coupon) / 100;
								$bundle_price_product = $prod_price - $to_subtract;
								$sum_price_regular    = $prod_price * $total_prod_qty;
								$bundle_price         = $bundle_price_product * $total_prod_qty;
								$price_discount       = ($prod_price * $total_prod_qty) - $bundle_price_total;

							// buy x get x off
							elseif ($prod_data['type'] == 'off') :

								$total_prod_qty       = $prod_data['qty'];
								$i_tt                 = $prod_price * $prod_data['qty'];
								$bundle_coupon        = $prod_data['coupon'];
								$bundle_price_product = $prod_price - ($prod_price * ($bundle_coupon / 100));
								$sum_price_regular    = $prod_price * $prod_data['qty'];
								$bundle_price         = $bundle_price_product * $prod_data['qty'];
								$price_discount       = $i_tt - $bundle_price_total;

							// Product bundle
							else :

								// total product qty
								foreach ($prod_data['prod'] as $i => $i_prod) :
									$total_prod_qty = $total_prod_qty + $i_prod['qty'];
								endforeach;

								// get bundle id
								$bun_id = $prod_data['bun_id'];

								// get bundle data
								$bundle_data = get_post_meta($bun_id, 'product_discount', true);

								// echo '<pre>';
								// print_r($bundle_data);
								// echo '</pre>';

								// get bundle products
								$bundle_products = array_column($bundle_data, 'post')[0];

								// get pricing array
								$pricing_array = $bundle_data['selValue_bun']['price_currency'];

								// bundle price (if custom defined for current currency, prioritize that, else calculate based on exchange rate)
								$bundle_price = $pricing_array[$current_curr] != '' ? $pricing_array[$current_curr] : $pricing_array[$default_currency] * $ex_rate;

								// calculate total regular price of bundle
								$sum_price_regular = 0;

								// loop through products
								foreach ($bundle_products as $b_prod) :

									//  get product object
									$prod_obj = wc_get_product($b_prod['id']);

									// get product type
									$prod_type = $prod_obj->get_type();

									// if product is variable
									if ($prod_type == 'variable') :

										// get first child
										$var_id = $prod_obj->get_children()[0];

										// get child object
										$var_obj = wc_get_product($var_id);

										// get regular price
										$prod_price = $var_obj->get_regular_price() * $b_prod['quantity'];

										// add price to total
										$sum_price_regular += $prod_price;

									// if product is simple
									else :

										// retrieve regular price
										$prod_price = $prod_obj->get_regular_price() * $b_prod['quantity'];

										// add price to total
										$sum_price_regular += $prod_price;

									endif;

								endforeach;

								// calculate discount %
								$bundle_coupon = (($sum_price_regular - $bundle_price) / $sum_price_regular) * 100;

								// js input data package
								$js_discount_type  = 'percentage';
								$js_discount_qty   = 1;
								$js_discount_value = $bundle_coupon;

							endif;

							//prevent Addon product to display here
							if (in_array($p_id, $addon_products)) :
								continue;
							endif;

							if ($prod_data['type'] == 'free' || $prod_data['type'] == 'off') : ?>

								<div data-bundle-data="<?php echo base64_encode(json_encode($prod_data)) ?>" data-nonce="<?php echo wp_create_nonce('get update bundle pricing'); ?>" class="template_a item-selection col-hover-focus mwc_item_div mwc_item_div_<?php echo $prod_data['bun_id'] ?> op_c_package_option <?= (self::$package_default_id == $prod_data['bun_id']) ? 'mwc_selected_default_opt' : '' ?>" data-type="<?php echo ($prod_data['type']) ?>" data-bundle_id="<?php echo ($prod_data['bun_id']) ?>" data-coupon="<?= $bundle_coupon ?>">
								<?php
							else : ?>

									<div data-bundle-data="<?php echo base64_encode(json_encode($prod_data)) ?>" data-nonce="<?php echo wp_create_nonce('get update bundle pricing'); ?>" class="template_a item-selection col-hover-focus mwc_item_div mwc_item_div_<?php echo ($prod_data['bun_id']) ?> op_c_package_option <?= (self::$package_default_id == $prod_data['bun_id']) ? 'mwc_selected_default_opt' : '' ?>" data-type="<?php echo ($prod_data['type']) ?>" data-bundle_id="<?php echo ($prod_data['bun_id']) ?>" data-coupon="<?= $bundle_coupon ?>">
									<?php
								endif; ?>

									<!-- js input hidden data package -->
									<input type="hidden" class="js-input-discount_package" data-type="<?php echo $js_discount_type ?>" data-qty="<?php echo $js_discount_qty ?>" value="<?php echo $js_discount_value ?>">
									<input type="hidden" class="js-input-cus_bundle_total_price" value="<?php echo $cus_bundle_total_price ?>">
									<!-- results -->
									<input type="hidden" class="js-input-price_package" value="">
									<input type="hidden" class="js-input-price_summary" value="">

									<div class="col-inner box-shadow-2 box-shadow-3-hover box-item" style="border: 1px solid var(--rio-secondary-color);">

										<div class="mwc_item_title_div">
											<div class="mwc_package_radio_div">
											</div>
											<div class="package-info">

												<?php
												if ($p_i == 0 && isset($_GET['unit'])) :

													// get unit price
													$unit_price = (strlen($_GET['unit']) > 2) ? number_format(($_GET['unit'] / 100), 2) : $_GET['unit'];

												?>
													<br>
													<span class="discount">( <?php echo (floatval(preg_replace('#[^\d.]#', '', $unit_price * $ex_rate))) ?> / Unit )</span>
												<?php endif; ?>

											</div>
										</div>

										<div class="mwc_item_infos_div mwc_collapser_inner i_row i_clearfix">

											<div class="op_c_package_header">

												<!-- package title -->
												<div class="op_c_header_first">
													<div class="op_c_package_title">
														<span><?php echo ($bundle_title) ?></span>
													</div>
												</div>

												<!-- label -->
												<div class="op_c_label">
													<?php if ($bundle_coupon > 0) : ?>
														<span class="s_save">
															<?php pll_e('Save ', 'woocommerce'); ?>
															<?php echo wc_price($sum_price_regular - $bundle_price) ?>
														</span>
													<?php endif; ?>

													<!-- custom label header -->
													<?php if (isset($prod_data['label_item']) && !is_bool($prod_data['label_item']) && !is_string($prod_data['label_item'])) :
														foreach ($prod_data['label_item'] as $value) :
															if (isset($value->name)) : ?>
																<span style="background-color:<?php echo ($value->color) ?>"><?php echo ($value->name) ?></span>
													<?php endif;
														endforeach;
													endif;
													?>

												</div>

												<!-- select button -->
												<div class="op_c_select_package">
													<button class="select_button">
													</button>
												</div>

												<!-- end op_c_package_header -->
											</div>

											<div class="op_c_package_content" data-hover-image="<?php echo isset($prod_data['image_package_hover']) ? $prod_data['image_package_hover'] : '' ?>">

												<!-- checkbox cont -->
												<div>
													<label class="check_box">
														<?php if ($prod_data['type'] == 'free' || $prod_data['type'] == 'off') : ?>
															<input type="radio" name="mwc_package_checkbox" data-product_id="<?php echo ($p_id) ?>" data-index="<?php echo ($opt_i) ?>" value="<?php echo ($p_id) ?>" class="mwc_package_checkbox product_id">
														<?php else : ?>
															<input type="radio" name="mwc_package_checkbox" data-product_id="<?php echo ($prod_data['bun_id']) ?>" data-index="<?php echo ($opt_i) ?>" value="<?php echo ($prod_data['bun_id']) ?>" class="mwc_package_checkbox product_id">
														<?php endif; ?>
														<span class="checkmark"></span>
													</label>
												</div>

												<!-- package imagery -->
												<div class="op_c_package_image">
													<?php if (wp_is_mobile() && $prod_data['image_package_mobile']) : ?>
														<img src="<?php echo ($prod_data['image_package_mobile']) ?>" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" alt="">
													<?php elseif ($prod_data['image_package_desktop']) :	?>
														<img src="<?php echo ($prod_data['image_package_desktop']) ?>" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" alt="">
													<?php
													else :
														echo ($prod_obj->get_image("woocommerce_thumbnail"));
													endif;

													// show discount label
													if ($prod_data['show_discount_label']) : ?>
														<span class="show_discount_label"><?php echo (sprintf(__('%s&#37; OFF', 'woocommerce'), $bundle_coupon, 0)) ?></span>
													<?php endif; ?>
												</div>

												<!-- package info -->
												<div class="op_c_package_info">

													<!-- product title -->
													<div class="pi-1"><?php echo $product_title ?></div>

													<!-- package type off or free -->
													<?php if ($prod_data['type'] == 'free' || $prod_data['type'] == 'off') : ?>
														<div class="op_c_package_subtitle">
															<span><?php echo (($prod_data['qty'] + (isset($prod_data['qty_free']) ? $prod_data['qty_free'] : 0)) . 'x ' . $product_name) ?></span>
														</div>
													<?php endif; ?>

													<!-- package type bun -->
													<div class="pi-info">
														<?php if ($prod_data['type'] == 'bun') : ?>

															<div class="pi-price-sa pt-1"><?= __('Old price', 'woocommerce') ?>:</div>
															<div class="pi-price-pricing">
																<div class="pi-price-each pl-lg-1">
																	<span class="js-label-price_total"><del><?php echo wc_price($sum_price_regular); ?></del></span>
																</div>
															</div>
															<div class="pi-price-total">
																<strong><?php pll_e('Total', 'woocommerce') ?>:</strong>
																<span class="js-label-price_total"><?php echo wc_price($bundle_price); ?></span>
															</div>

															<!-- get prices bundle -->
															<input type="hidden" class="mwc_bundle_price_hidden" data-label="<?= $bundle_title ?>" value="<?= $bundle_price ?>">
															<input type="hidden" class="mwc_bundle_price_regular_hidden" data-label="<?= __('Old Price', 'woocommerce') ?>" value="<?= $sum_price_regular ?>">
															<input type="hidden" class="mwc_bundle_price_sale_hidden" data-label="<?= __('Sale Price', 'woocommerce') ?>" value="<?= $bundle_price ?>">
															<input type="hidden" class="mwc_bundle_product_qty_hidden" value="1">

															<?php
														else : // free and off option

															// show old price or same as
															if (isset($prod_data['show_original_price']) && is_bool($prod_data['show_original_price']) && $prod_data['type'] == 'off') : ?>
																<div class="pi-price-sa pt-1"><?= __('Same as', 'woocommerce') ?>:</div>
																<div class="pi-price-pricing">
																	<div class="pi-price-each pl-lg-1">
																		<span><?php echo wc_price($bundle_price_product); ?></span>
																		<span class="pi-price-each-txt">/<?php echo __('each', 'woocommerce'); ?></span>
																	</div>
																</div>
															<?php else : ?>
																<div class="pi-price-sa pt-1"><?= __('Same as', 'woocommerce') ?>:</div>
																<div class="pi-price-pricing">
																	<div class="pi-price-each pl-lg-1">
																		<span><?php echo wc_price($bundle_price_product); ?></span>
																		<span class="pi-price-each-txt">/<?php echo __('each', 'woocommerce'); ?></span>
																	</div>

																</div>
															<?php endif; ?>

															<div class="pi-price-total">
																<strong><?php pll_e('Total', 'woocommerce') ?>:</strong>
																<span><?php echo wc_price($bundle_price); ?></span>
															</div>

															<!-- get prices bundle -->
															<input type="hidden" class="mwc_bundle_price_hidden" data-label="<?= $bundle_title ?>" value="<?= $sum_price_bundle ?>">
															<input type="hidden" class="mwc_bundle_price_regular_hidden" data-label="<?= __('Old Price', 'woocommerce') ?>" value="<?= $sum_price_regular ?>">
															<input type="hidden" class="mwc_bundle_price_sale_hidden" data-label="<?= __('Sale Price', 'woocommerce') ?>" value="<?= $sum_price_bundle ?>">
															<input type="hidden" class="mwc_bundle_product_qty_hidden" value="<?= $total_prod_qty ?>">
														<?php
														endif; ?>

													</div>
												</div> <!-- end op_c_package_info -->
											</div> <!-- end op_c_package_content -->

											<?php
											if ($prod_data['feature_description']) :
												$feature_desc = $prod_data['feature_description'];
											?>
												<div class="op_c_desc" hidden>
													<div class="op_c_package_description">
														<?php foreach ($feature_desc as $value) : ?>
															<div class="desc-item">
																<li><?php echo ($value) ?></li>
															</div>
														<?php endforeach; ?>
													</div>
												</div>
											<?php endif; ?>

											<div class="op_c_package_bullet_wrapper">
												<!-- sell out risk -->
												<?php if (($prod_data['sell_out_risk']) && $prod_data['sell_out_risk'] != 'none') : ?>
													<span class="bullet-item"><?php pll_e('Sell-Out Risk', 'woocommerce') ?> :
														<span style="color:red;">
															<?php
															if ($prod_data['sell_out_risk'] == 'high') :
																echo __('High', 'woocommerce');
															elseif ($prod_data['sell_out_risk'] == 'medium') :
																echo __('Medium', 'woocommerce');
															else :
																echo __('Low', 'woocommerce');
															endif;
															?>
														</span>
													</span>
												<?php endif; ?>

												<!-- free shipping -->
												<?php if ($prod_data['free_shipping']) : ?>
													<span class="bullet-item free-shipping"><?php pll_e('FREE SHIPPING', 'woocommerce') ?></span>
												<?php endif; ?>

												<!-- discount percent -->
												<?php if (0 != $bundle_coupon) : ?>
													<span class="bullet-item"><?php pll_e('Discount', 'woocommerce') ?> : <?= round($bundle_coupon, 0) ?>%</span>
												<?php endif; ?>

												<!-- show popular -->
												<?php if ($prod_data['popularity'] && $prod_data['popularity'] != 'none') : ?>
													<span class="bullet-item font-weight-bold">
														<svg style="width: 20px;" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="signal" class="svg-inline--fa fa-signal fa-w-20" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
															<path fill="currentColor" d="M216 288h-48c-8.84 0-16 7.16-16 16v192c0 8.84 7.16 16 16 16h48c8.84 0 16-7.16 16-16V304c0-8.84-7.16-16-16-16zM88 384H40c-8.84 0-16 7.16-16 16v96c0 8.84 7.16 16 16 16h48c8.84 0 16-7.16 16-16v-96c0-8.84-7.16-16-16-16zm256-192h-48c-8.84 0-16 7.16-16 16v288c0 8.84 7.16 16 16 16h48c8.84 0 16-7.16 16-16V208c0-8.84-7.16-16-16-16zm128-96h-48c-8.84 0-16 7.16-16 16v384c0 8.84 7.16 16 16 16h48c8.84 0 16-7.16 16-16V112c0-8.84-7.16-16-16-16zM600 0h-48c-8.84 0-16 7.16-16 16v480c0 8.84 7.16 16 16 16h48c8.84 0 16-7.16 16-16V16c0-8.84-7.16-16-16-16z"></path>
														</svg>
														<?php
														if ($prod_data['popularity'] == 'best-seller') :
															echo __('Best Seller', 'woocommerce');
														elseif ($prod_data['popularity'] == 'popular') :
															echo __('Popular', 'woocommerce');
														else :
															echo __('Moderate', 'woocommerce');
														endif;
														?>
													</span>
												<?php endif; ?>
											</div>

										</div> <!-- end mwc_item_infos_div -->
									</div>

									<?php
									// echo '<pre>';
									// print_r($prod_obj);
									// echo '</pre>';
									?>

									<?php if ($prod_obj->is_type('variable') && 2 > count($prod_obj->get_available_variations())) :
										echo '<div class="d-none">';
									endif;	?>

									<!-- Product variations form ------------------------------>
									<div class="mwc_product_variations mwc_product_variations_<?php echo (trim($prod_data['bun_id'])) ?> info_products_checkout <?= (($prod_data['type'] == 'free' || $prod_data['type'] == 'off') && $prod_obj->is_type('variable')) ? 'is_variable' : '' ?>" data-bundle_id="<?php echo (trim($prod_data['bun_id'])) ?>" style="display: none;">
										<h4 class="title_form"><?= __('Please choose', 'woocommerce') ?>:</h4>
										<table class="product_variations_table">
											<tbody>
												<?php
												//package selection variations free and off
												if ($prod_data['type'] == 'free' || $prod_data['type'] == 'off') :

													// retrieve product object
													$prod_obj = wc_get_product($prod_data['id']);

													// get variation images product
													if (!isset($var_data[$p_id]) && $prod_obj->is_type('variable')) :

														$variation_arr = [];

														foreach ($prod_obj->get_available_variations() as $key => $value) :

															array_push($variation_arr, [
																'id'         => $value['variation_id'],
																'price'      => isset($prod_data['custom_price'][$value['variation_id']]) ? $prod_data['custom_price'][$value['variation_id']][$current_curr] : '',
																'attributes' => $value['attributes'],
																'image'      => $value['image']['url']
															]);

														endforeach;

														$var_data[$p_id] = $variation_arr;

													endif;

												?>

													<?php for ($i = 0; $i < $prod_data['qty']; $i++) :

														// check if has size chart
														$has_size_chart = get_post_meta($prod_data['id'], 'sbarray_chart_data', true) ? 'true' : 'false';

													?>
														<!-- c prod item -->
														<tr class="c_prod_item" has-size-chart="<?php echo $has_size_chart; ?>" data-id="<?php echo ($p_id) ?>" <?= (!$prod_obj->is_type('variable')) ? 'hidden' : '' ?>>

															<?php if ($prod_obj->is_type('variable')) : ?>

																<!-- index -->
																<td class="variation_index"><?= $i + 1 ?></td>

																<!-- variation image -->
																<td class="variation_img">
																	<img class="mwc_variation_img" src="<?= wp_get_attachment_image_src($prod_obj->get_image_id())[0] ?>">
																</td>

																<!-- variation selectors -->
																<td class="variation_selectors">

																	<?php

																	// show variations linked by variations
																	echo MWC::mwc_return_linked_variations_dropdown([
																		'product_id'		=> $prod_data['id'],
																		'class' 			=> 'var_prod_attr checkou_prod_attr select-variation-' . $prod_data['type'],
																	], $var_data, $prod_data);

																	$prod_variations = $prod_obj->get_variation_attributes();

																	foreach ($prod_variations as $attribute_name => $options) :
																		$default_opt =  key_exists($attribute_name, $prod_obj->get_default_attributes()) ? $prod_obj->get_default_attributes()[$attribute_name] : '';
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
														<?php endfor;
												else :
													//package selection bundle
													$_index = 1;

													foreach ($prod_data['prod'] as $i => $i_prod) :

														$p_id      = $i_prod['id'];
														$b_product = wc_get_product($p_id);

														// has size chart
														$has_size_chart = get_post_meta($p_id, 'sbarray_chart_data', true) ? 'true' : 'false';

														// get variation images product
														if (!isset($var_data[$p_id]) && $b_product->is_type('variable')) :

															$variation_arr = [];

															foreach ($b_product->get_available_variations() as $key => $value) :

																array_push($variation_arr, [
																	'id'         => $value['variation_id'],
																	'price'      => key_exists('custom_price', $prod_data) ? $prod_data['custom_price'][$value['variation_id']][$current_curr] : '',
																	'attributes' => $value['attributes'],
																	'image'      => $value['image']['url']
																]);

															endforeach;

															$var_data[$p_id] = $variation_arr;
														endif;

														for ($i = 1; $i <= $i_prod['qty']; $i++) :	?>

															<tr class="c_prod_item" has-size-chart="<?php echo $has_size_chart; ?>" data-id="<?php echo ($p_id) ?>" <?= (!$b_product->is_type('variable')) ? 'hidden' : '' ?>>

																<?php if ($b_product->is_type('variable')) : ?>

																	<td class="variation_index"><?= $_index++ ?></td>
																	<td class="variation_img">
																		<img id="prod_image" class="mwc_variation_img" src="<?= wp_get_attachment_image_src($b_product->get_image_id())[0] ?>">
																	</td>
																	<td class="variation_selectors">
																		<?php

																		// show variations linked by variations
																		echo MWC::mwc_return_linked_variations_dropdown([
																			'product_id'		=> $p_id,
																			'class' 			=> 'var_prod_attr checkou_prod_attr select-variation-' . $prod_data['type'],
																		], $var_data, $prod_data);

																		$prod_variations = $b_product->get_variation_attributes();
																		foreach ($prod_variations as $attribute_name => $options) :
																			$default_opt =  key_exists($attribute_name, $b_product->get_default_attributes()) ? $b_product->get_default_attributes()[$attribute_name] : '';
																		?>
																			<div class="variation_item">
																				<p class="variation_name"><?= wc_attribute_label($attribute_name) ?>: </p>

																				<!-- load dropdown variations -->
																				<?php
																				echo MWC::mwc_return_onepage_checkout_variation_dropdown([
																					'product_id'		=> $p_id,
																					'options' 			=> $options,
																					'attribute_name'	=> $attribute_name,
																					'default_option'	=> $default_opt,
																					'var_data'			=> $var_data[$p_id],
																					'class' 			=> 'var_prod_attr checkou_prod_attr select-variation-' . $prod_data['type'],
																				]);
																				?>

																			</div>

																			<?php
																			// Size chart
																			if (defined('SBHTML_VERSION')) :
																				do_action('mwc_size_chart', $p_id);
																			endif;
																			?>

																		<?php endforeach; ?>
																	</td>
																<?php endif; ?>
															</tr>
												<?php endfor;
													endforeach;
												endif; ?>
											</tbody>
										</table>

										<!-- variations free products -->
										<?php if ($prod_data['type'] == 'free' && isset($prod_data['qty_free']) && $prod_data['qty_free'] > 0) : 
											
											// has size chart
											$has_size_chart = get_post_meta($p_id, 'sbarray_chart_data', true) ? 'true' : 'false';

											?>

											<h5 class="title_form" style="font-size: calc(1.8rem * var(--rio-typo-ratio,1));"><?= __('Select Free Product', 'woocommerce') ?>:</h5>

											<table class="product_variations_table">
												<tbody>
													<?php for ($i = 0; $i < $prod_data['qty_free']; $i++) : ?>

														<tr class="c_prod_item free-item"  has-size-chart="<?php echo $has_size_chart; ?>" data-id="<?php echo ($p_id) ?>" <?= (!$prod_obj->is_type('variable')) ? 'hidden' : '' ?>>
															<?php if ($prod_obj->is_type('variable')) :
															?>
																<td class="variation_index"><?= $i + 1 ?></td>
																<td class="variation_img">
																	<img class="mwc_variation_img" src="<?= wp_get_attachment_image_src($prod_obj->get_image_id())[0] ?>">
																</td>
																<td class="variation_selectors">
																	<?php

																	// show variations linked by variations
																	echo MWC::mwc_return_linked_variations_dropdown([
																		'product_id'		=> $p_id,
																		'class' 			=> 'var_prod_attr checkou_prod_attr select-variation-' . $prod_data['type'],
																	], $var_data, $prod_data);

																	$prod_variations = $prod_obj->get_variation_attributes();

																	foreach ($prod_variations as $attribute_name => $options) :
																		$default_opt =  key_exists($attribute_name, $prod_obj->get_default_attributes()) ? $prod_obj->get_default_attributes()[$attribute_name] : '';
																	?>
																		<div class="variation_item">
																			<p class="variation_name"><?= wc_attribute_label($attribute_name) ?>: </p>

																			<!-- load dropdown variations -->
																			<?php
																			echo MWC::mwc_return_onepage_checkout_variation_dropdown([
																				'product_id'		=> $p_id,
																				'options' 			=> $options,
																				'attribute_name'	=> $attribute_name,
																				'default_option'	=> $default_opt,
																				'var_data'			=> $var_data[$p_id],
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

										?>
									</div>
									<!-- end product variations form -->
									<?php

									// Size chart
									if (defined('SBHTML_VERSION')) :
										do_action('mwc_size_chart', $p_id);
									endif;

									if ($prod_obj->is_type('variable') && 2 > count($prod_obj->get_available_variations())) :
										echo '</div>';
									endif;
									?>
									</div>
							<?php
						endif;
						$p_i++;
					endforeach;	?>

							<div id="order_summary" hidden>
								<div class="row">
									<div class="col large-4 small-12" id="s_image" style="text-align: center;">
										<img width="247" height="296" src="" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" alt="">
									</div>

									<div class="col large-8 small-12">
										<div class="col-inner box-shadow-2 box-shadow-3-hover box-item">
											<div class="op_c_summary mwc_collapser_inner i_row i_clearfix">
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

			<script>
				var opc_variation_data = <?= json_encode($var_data) ?>;
				const mwc_variation_price = <?= json_encode($variation_price) ?>;

				var mwc_products_variations = <?php echo (json_encode(MWC::$mwc_products_variations)) ?>;
				var mwc_products_variations_prices = <?php echo (json_encode(MWC::$mwc_products_variations_prices)) ?>;
			</script>

			<!-- checkbox/selection fixes -->
			<script>
				jQuery(document).ready(function($) {

					// set default package
					setTimeout(() => {
						if ($('.op_c_package_option').hasClass('mwc_active_product')) {
							$(document).find('.select_button').text('<?php pll_e('Select') ?>');
							$('.mwc_active_product').find('.select_button').text('<?php pll_e('Selected') ?>');
						}
					}, 500);

					// select button on click
					$('.select_button').click(function() {
						$('.select_button').text('<?php pll_e('Select') ?>').removeClass('op_c_btn_selected');
						$(this).text('<?php pll_e('Selected') ?>').addClass('op_c_btn_selected');
						$(this).parents('.mwc_item_infos_div').find('.mwc_package_checkbox').prop("checked", true);
					});

					// box item on click
					$('.box-item').click(function() {
						$(this).find('.mwc_package_checkbox').prop("checked", true);
						$('.select_button').text('<?php pll_e('Select') ?>').removeClass('op_c_btn_selected');
						$(this).find('.select_button').text('<?php pll_e('Selected') ?>').addClass('op_c_btn_selected');
					});

				});
			</script>

		<?php
	endif;
