<?php
$out = array();
$p = new WC_Product_Variable(); $p->set_name('T'); $p->set_status('publish');
$a = new WC_Product_Attribute(); $a->set_name('Rozmiar'); $a->set_options(array('S','L')); $a->set_visible(true); $a->set_variation(true);
$p->set_attributes(array($a)); $id = $p->save();
foreach (array(array('S',615.99),array('L',768.99)) as $v) { $var = new WC_Product_Variation(); $var->set_parent_id($id); $var->set_attributes(array('rozmiar'=>$v[0])); $var->set_regular_price($v[1]); $var->set_status('publish'); $var->save(); }
WC_Product_Variable::sync($id); $p = wc_get_product($id);
$html = wp_strip_all_tags($p->get_price_html());
$out['html'] = $html;
$out['html_has_od_and_min'] = (false !== strpos($html,'od') && false !== strpos($html,'615,99') && false === strpos($html,'768'));
$g = $GLOBALS; $GLOBALS['product'] = $p;
$m = apply_filters('woocommerce_structured_data_product_offer', array('@type'=>'AggregateOffer','lowPrice'=>'615.99','highPrice'=>'768.99','offerCount'=>2,'priceCurrency'=>'PLN','availability'=>'https://schema.org/InStock','url'=>'x'), $p);
$out['offer_type'] = $m['@type']; $out['offer_price'] = $m['price'] ?? null; $out['no_range'] = !isset($m['lowPrice']) && !isset($m['highPrice']);
$out['policies_kept'] = isset($m['hasMerchantReturnPolicy'], $m['shippingDetails']);
$_GET['attribute_rozmiar']='L'; $m2 = apply_filters('woocommerce_structured_data_product_offer', array('@type'=>'AggregateOffer','lowPrice'=>'615.99','highPrice'=>'768.99','offerCount'=>2,'priceCurrency'=>'PLN'), $p); $out['variant_link_price']=$m2['price']; $out['variant_link_ok']=('768.99'===$m2['price']); unset($_GET['attribute_rozmiar']);
// Same price for all options keeps default.
$s = new WC_Product_Simple(); $s->set_regular_price(10); $s->save();
$out['simple_untouched'] = false === strpos(wp_strip_all_tags(wc_get_product($s->get_id())->get_price_html()), 'od');
file_put_contents('/playground/_varprice-result.json', json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
