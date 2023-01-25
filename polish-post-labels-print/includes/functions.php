<?php
/**
 * Function include all files in folder
 *
 * @param $path   Directory address
 * @param $ext    array file extension what will include
 * @param $prefix string Class prefix
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// Add meta box
add_action( 'add_meta_boxes', 'tcg_tracking_box' );
function tcg_tracking_box() {
    add_meta_box(
        'tcg-tracking-modal',
        'The Courier Guy Tracking',
        'tcg_meta_box_callback',
        'shop_order',
        'side',
        'core'
    );
}

// Callback
function tcg_meta_box_callback( $post )
{
    $value = get_post_meta( $post->ID, '_tracking_box', true );
    $text = ! empty( $value ) ? esc_attr( $value ) : '';
    echo '<input type="text" name="tracking_box" id="tcg_tracking_box" value="' . $text . '" />';
    echo '<input type="hidden" name="tracking_box_nonce" value="' . wp_create_nonce() . '">';
}

// Saving
add_action( 'save_post', 'tcg_save_meta_box_data' );
function tcg_save_meta_box_data( $post_id ) {

    // Only for shop order
    if ( 'shop_order' != $_POST[ 'post_type' ] )
        return $post_id;

    // Check if our nonce is set (and our cutom field)
    if ( ! isset( $_POST[ 'tracking_box_nonce' ] ) && isset( $_POST['tracking_box'] ) )
        return $post_id;

    $nonce = $_POST[ 'tracking_box_nonce' ];

    // Verify that the nonce is valid.
    if ( ! wp_verify_nonce( $nonce ) )
        return $post_id;

    // Checking that is not an autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return $post_id;

    // Check the user’s permissions (for 'shop_manager' and 'administrator' user roles)
    if ( ! current_user_can( 'edit_shop_order', $post_id ) && ! current_user_can( 'edit_shop_orders', $post_id ) )
        return $post_id;

    // Saving the data
    update_post_meta( $post_id, '_tracking_box', sanitize_text_field( $_POST[ 'tracking_box' ] ) );
}

// Display To My Account view Order
add_action( 'woocommerce_order_details_after_order_table', 'tcg_display_tracking_box_in_order_view', 10, 1 );
function tcg_display_tracking_box_in_order_view( $order )
{
    $tracking_box = get_post_meta( $order->get_id(), '_tracking_box', true );
    // Output Tracking box
    if( ! empty( $tracking_box ) && is_account_page() )
        echo '<p>Tracking box: '. $tracking_box .'</p>';
}