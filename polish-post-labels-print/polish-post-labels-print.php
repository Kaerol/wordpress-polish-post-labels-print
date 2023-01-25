<?php

/**
 * Plugin Name: PolishPost - shipment order and envelope label
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
define( 'PPOST_WOO_ORDERS_LABELS_DIR', WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'polish-post-labels-print' . DIRECTORY_SEPARATOR );
define( 'PPOST_WOO_ORDERS_LABELS_INCLUDES', PPOST_WOO_ORDERS_LABELS_DIR . 'includes' . DIRECTORY_SEPARATOR );
define( 'FPDF_FONTPATH', PPOST_WOO_ORDERS_LABELS_DIR.'assets/fonts');
 
if ( is_file( PPOST_WOO_ORDERS_LABELS_INCLUDES . 'define.php' ) ) {
	require_once PPOST_WOO_ORDERS_LABELS_INCLUDES . 'define.php';
} 

function plugin_log($log) {
	$fp = fopen(WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'log.txt', 'a');
	fwrite($fp, $log);  
	fclose($fp);  
}

add_filter('bulk_actions-edit-shop_order', 'bulk_ppost_post_confirmation_print_pdf', 20, 1 );
function bulk_ppost_post_confirmation_print_pdf( $actions ) {
    $actions['bulk_ppost_post_confirmation_pdf'] = 'Grupowy druk - Potwierdzenie nadania';
    return $actions;
}

// Make the action from selected orders
add_filter( 'handle_bulk_actions-edit-shop_order', 'downloads_handle_bulk_action_edit_shop_order', 10, 3 );
function downloads_handle_bulk_action_edit_shop_order( $redirect_to, $action, $post_ids ) {
    if ( $action !== 'bulk_ppost_post_confirmation_pdf' )
        return $redirect_to; // Exit

	if ( is_file( PPOST_WOO_ORDERS_LABELS_INCLUDES . 'tfpdf.php' ) ) {
		require_once PPOST_WOO_ORDERS_LABELS_INCLUDES . 'tfpdf.php';
	}
	
	global $pdf;

	$pdf = new tFPDF();
	$pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
	$pdf->SetFont('DejaVu','',14);
	
    foreach ( $post_ids as $post_id ) {		
		$pdf = ppost_post_confirmation_single_page($pdf, $post_id);	
    }

	$pdf->Output('F', PPOST_WOO_ORDERS_LABELS_DIR.'/../../../ppost_order_labels/bulk_post_confirmation.pdf', true); 
	
	return 'https://zlotlagow.pl/ppost_order_labels/bulk_post_confirmation.pdf';
}

// Add meta box
add_action( 'add_meta_boxes', 'ppost_order_details_add_meta_boxes' );
function ppost_order_details_add_meta_boxes() {
    add_meta_box(
        'ppost-envelope-print-modal',
        'Print envelope',
        'ppost_print_callback',
        'shop_order',
        'side',
        'core'
    );   
}

// Callbacks
function ppost_print_callback( $post )
{
	global $post;
	$order_id = $post->ID;

    echo '<div><p style="text-align: center">
			<button type="button" class="button woo-ppost_envelope_print" data-order_id="' . $order_id . '" title="Nadruk na kopercie DL">Nadruk na kopercie DL</button>
		</p></div>';
    echo '<div><p style="text-align: center">
			<button type="button" class="button woo-ppost_post_confirmation_print" data-order_id="' . $order_id . '" title="Druk - Potwierdzenie nadania">Druk - Potwierdzenie nadania</button>
		</p></div>';		
    echo '<input type="hidden" name="tracking_box_nonce" value="' . wp_create_nonce() . '">';
}

add_action( 'admin_enqueue_scripts', 'ppost_order_details_enqueue_script');
function ppost_order_details_enqueue_script() {
	global $pagenow;
	if ( $pagenow === 'post.php' ) {
		$screen = get_current_screen();
		if ( is_a( $screen, 'WP_Screen' ) && $screen->id == 'shop_order' ) {
			wp_enqueue_script( 'ppost-admin-order-labels-js', PPOST_WOO_ORDERS_LABELS_JS . 'ppost-admin-order-labels.js', array( 'jquery' ));
			wp_localize_script( 'ppost-admin-order-labels-js',
					'ppost_admin_order_labels',
					array(
						'ajax_url'                           => admin_url( 'admin-ajax.php' ),
					)
				);
		}
	}
}

add_action('wp_ajax_ppost_envelope_print_pdf', 'ppost_envelope_print_pdf' );
function ppost_envelope_print_pdf() {
	$order_id = $_POST['order_id'];

	if ( is_file( PPOST_WOO_ORDERS_LABELS_INCLUDES . 'tfpdf.php' ) ) {
		require_once PPOST_WOO_ORDERS_LABELS_INCLUDES . 'tfpdf.php';
	}
 
	global $wpdb;
	global $pdf;
	
	$order = $wpdb->get_results(getSql($order_id));
	
	$pdf = new tFPDF('L', 'cm', array(30, 14));
	$pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
	$pdf->SetFont('DejaVu','',14);
	
	$pdf->AddPage();
	$pdf->SetXY(9, 4.5);
	$pdf->Write(1, 'Klub NINE SIX MC POLAND ZIELONA GÓRA');
	$pdf->SetXY(9, 5);
	$pdf->Write(1, 'ul. Zjednoczenia 92');
	$pdf->SetXY(9, 5.5);
	$pdf->Write(1, '65-120 Zielona Góra');
	
	$pdf->SetFontSize(8);
	
	$address = toAddress($order[0]);	
	$companyLines = null;
	
	if (isset($address['company']))	{
		$companyLines = getLongTextToLines($address['company'], 20);
	}
	
	$pdf->SetXY(20, 7.5);
	$pdf->Write(1, '('.$order_id.'.'.$order[0]->tickets.')', 1, 'L', false);
	$pdf->SetFontSize(14);
	
	$textPoxY = 8;	
	$lineHeight = 0.6;
	if (isset($companyLines[0])){
		$pdf->SetXY(20, $textPoxY );
		$pdf->Write(1, toAscii($companyLines[0]), 1, 'L', false);
		$textPoxY += $lineHeight;
	}
	if (isset($companyLines[1])){
		$pdf->SetXY(20, $textPoxY );
		$pdf->Write(1, toAscii($companyLines[1]), 1, 'L', false);
		$textPoxY += $lineHeight;
	}
	$pdf->SetXY(20, $textPoxY );
	$pdf->Write(1, toAscii($address['fullName']), 1, 'L', false);
	$textPoxY += $lineHeight;
	
	$pdf->SetXY(20, $textPoxY );
	$pdf->Write(1, toAscii($address['street']), 1, 'L', false);
	$textPoxY += $lineHeight;
	
	$pdf->SetXY(20, $textPoxY);
	$pdf->Write(1, $address['postcode'].'  '.toAscii($address['city']), 1, 'L', false);
	
	$pdf->Output('F', PPOST_WOO_ORDERS_LABELS_DIR.'/../../../ppost_order_labels/'.$order_id.'_envelope.pdf', true); 
		
	echo wp_json_encode(array('path' => 'https://zlotlagow.pl/ppost_order_labels/'.$order_id.'_envelope.pdf'));
	die();
}


add_action('wp_ajax_ppost_post_confirmation_print_pdf', 'ppost_post_confirmation_print_pdf' );
function ppost_post_confirmation_print_pdf() {
	$order_id = $_POST['order_id'];
	if ( is_file( PPOST_WOO_ORDERS_LABELS_INCLUDES . 'tfpdf.php' ) ) {
		require_once PPOST_WOO_ORDERS_LABELS_INCLUDES . 'tfpdf.php';
	}
 
	global $pdf;	
	$pdf = new tFPDF();
	$pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
	$pdf->SetFont('DejaVu','',14);
	
	$pdf = ppost_post_confirmation_single_page($pdf, $order_id);	
	
	$pdf->Output('F', PPOST_WOO_ORDERS_LABELS_DIR.'/../../../ppost_order_labels/'.$order_id.'_post_confirmation.pdf', true); 
	
	echo wp_json_encode(array('path' => 'https://zlotlagow.pl/ppost_order_labels/'.$order_id.'_post_confirmation.pdf'));
	
	die();
}

function ppost_post_confirmation_single_page($pdf, $order_id) {
	global $wpdb;
	
	$order = $wpdb->get_results(getSql($order_id));
	
	$pdf->AddPage();
	
	$pdf->Image(PPOST_WOO_ORDERS_LABELS_DIR.'assets/img/post_confirmation.jpg',0,0,210,290,'JPG');
	
	$pdf->SetXY(50, 26);
	$pdf->Write(1, 'Klub NINE SIX MC POLAND ZIELONA GÓRA');
	$pdf->SetXY(50, 38);
	$pdf->Write(1, 'ul. Zjednoczenia 92'); 
	$pdf->SetXY(50, 58);
	$pdf->Write(5, '65-120');
	$pdf->SetXY(120, 58);
	$pdf->Write(5, 'Zielona Góra');
	
	$address = toAddress($order[0]);
	$companyLines = null;
	
	if (isset($address['company']))	{
		$companyLines = getLongTextToLines($address['company'], 40);
	}
	
	$textPoxY = 85;	
	$lineHeight = 11;
	if (isset($companyLines)){
		$pdf->SetXY(50, $textPoxY );
		$pdf->Write(1, toAscii($companyLines[0]), 1, 'L', false);
		$textPoxY += $lineHeight;
		if (isset($companyLines[1])){
			$pdf->Write(1, '...', 1, 'L', false);			
		}
	}
	
	$pdf->SetXY(50, $textPoxY );
	$pdf->Write(1, toAscii($address['fullName']), 1, 'L', false);
	$textPoxY += $lineHeight;
	
	$pdf->SetXY(50, $textPoxY );
	$pdf->Write(1, toAscii($address['street']), 1, 'L', false);
	$textPoxY += $lineHeight;
	
	$pdf->SetXY(50, 118);
	$pdf->Write(1, $address['postcode'], 1, 'L', false);
	
	$pdf->SetXY(120, 118);
	$pdf->Write(1, toAscii($address['city']), 1, 'L', false);
	
	return $pdf;
}

function getSql($order_id) {
	global $wpdb;
	
	$sql = 'SELECT 
			p.id, pm1.meta_value as company, pm2.meta_value as name, pm3.meta_value as surname, pm4.meta_value as street1, pm5.meta_value as street2, pm6.meta_value as postcode, pm7.meta_value as city, im.meta_value as tickets
			FROM ' . $wpdb->prefix . 'posts p 
			left outer join ' . $wpdb->prefix . 'postmeta pm1 on pm1.post_id = p.id and pm1.meta_key = \'_shipping_company\'
			join ' . $wpdb->prefix . 'postmeta pm2 on pm2.post_id = p.id and pm2.meta_key = \'_shipping_first_name\'
			join ' . $wpdb->prefix . 'postmeta pm3 on pm3.post_id = p.id and pm3.meta_key = \'_shipping_last_name\'
			join ' . $wpdb->prefix . 'postmeta pm4 on pm4.post_id = p.id and pm4.meta_key = \'_shipping_address_1\'
			left outer join ' . $wpdb->prefix . 'postmeta pm5 on pm5.post_id = p.id and pm5.meta_key = \'_shipping_address_2\'
			join ' . $wpdb->prefix . 'postmeta pm6 on pm6.post_id = p.id and pm6.meta_key = \'_shipping_postcode\'
			join ' . $wpdb->prefix . 'postmeta pm7 on pm7.post_id = p.id and pm7.meta_key = \'_shipping_city\'
			join ' . $wpdb->prefix . 'woocommerce_order_items oi on oi.order_id = p.id and oi.order_item_type = \'line_item\'	
			join ' . $wpdb->prefix . 'woocommerce_order_itemmeta im on im.order_item_id = oi.order_item_id and im.meta_key = \'_qty\'
			WHERE id='.$order_id;
	
	return $sql;
}

function toAddress($order) {
	$address = [];
	
	$address['fullName'] = $order->name.'  '.$order->surname;
	$address['company'] = $order->company;
	$address['street'] = $order->street1;
	
	if (isset($order->street2)) {
		if (is_numeric($order->street2)) {
			if (endsWithNumber($order->street1)) {
				$address['street'] .= ' /';
			}
			$address['street'] .= ' '. $order->street2; 
		}else{
			$address['street'] .= ' '. $order->street2; 
		}
	}
	
	$address['postcode'] = $order->postcode;
	$address['city'] = $order->city;
	
	return $address;
} 

function getLongTextToLines($text, $rowLength) {
	$pieces = explode(' ', $text);
	$row = '';
	$lines = [];
	
	for ($i=0; $i < count($pieces); $i++) {
		$row .= $pieces[$i] . ' ';
		if (strlen($row) > $rowLength)
		{
			$lines[] = $row; 
			$row = '';
		}
	}
	
	$lines[] = $row; 

	return $lines;
}

function toAscii($nonAsciiText) {
	return $nonAsciiText;
	//return iconv('UTF-8', 'ASCII//TRANSLIT', $nonAsciiText);
}

function endsWithNumber($string) {
	return is_numeric(substr(trim($string), -1, 1));
}