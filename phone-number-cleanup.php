<?php
/*
Plugin Name: Phone Number Clean Up
Description: Extracts phone numbers from pasted text on a public page.
Version: 1.2.0
Author: mrhobbeys
Text Domain: phone-number-clean-up
Domain Path: /languages
*/

// Basic constants
if ( ! defined( 'PNC_MAX_INPUT_CHARS' ) ) {
    define( 'PNC_MAX_INPUT_CHARS', 50000 );
}
if ( ! defined( 'PNC_RATE_LIMIT_MAX' ) ) {
    define( 'PNC_RATE_LIMIT_MAX', 30 ); // submissions
}
if ( ! defined( 'PNC_RATE_LIMIT_WINDOW' ) ) {
    define( 'PNC_RATE_LIMIT_WINDOW', 600 ); // seconds (10 min)
}

// Load text domain
function pnc_load_textdomain() {
    load_plugin_textdomain( 'phone-number-clean-up', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'pnc_load_textdomain' );

/**
 * Plugin activation: create database table
 */
function pnc_plugin_activation() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pnc_extracted_numbers';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        user_id bigint(20) DEFAULT 0 NOT NULL,
        ip varchar(45) NOT NULL,
        normalized_number varchar(20) NOT NULL,
        raw_number varchar(30) NOT NULL,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY ip (ip),
        KEY normalized_number (normalized_number)
    ) $charset_collate;";
    
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'pnc_plugin_activation' );

/**
 * Simple IP based rate limiting using transients.
 */
function pnc_rate_limit_check() {
    $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
    $key = 'pnc_rl_' . md5( $ip );
    $data = get_transient( $key );
    if ( ! is_array( $data ) ) {
        $data = [ 'count' => 0, 'start' => time() ];
    }
    // Reset window if expired.
    if ( ( time() - (int) $data['start'] ) > PNC_RATE_LIMIT_WINDOW ) {
        $data = [ 'count' => 0, 'start' => time() ];
    }
    $data['count']++;
    set_transient( $key, $data, PNC_RATE_LIMIT_WINDOW );
    if ( $data['count'] > PNC_RATE_LIMIT_MAX ) {
        return false;
    }
    return true;
}

/**
 * Extract phone numbers (US + Spain) from a block of text.
 * Returns associative array of normalized => original variants list (first occurrence kept for output display) 
 */
function pnc_extract_numbers_from_text( $text ) {
    $numbers = [];
    // Patterns:
    // US: optional +1, separators space . - parentheses; 10 digits core.
    $pattern_us = '/(?:(?:\+?1)[-\.\s]?)?\(?\d{3}\)?[-\.\s]?\d{3}[-\.\s]?\d{4}\b/';
    // Spain: optional +34 / 0034, then 9 digits starting with 6,7,8,9 possibly separated by spaces.
    $pattern_spain = '/(?:(?:\+34|0034)[\s-]?)?(?:[6789]\s?\d(?:\s?\d){7})/';

    $all_matches = [];
    preg_match_all( $pattern_us, $text, $us_matches );
    preg_match_all( $pattern_spain, $text, $es_matches );
    if ( ! empty( $us_matches[0] ) ) {
        $all_matches = array_merge( $all_matches, $us_matches[0] );
    }
    if ( ! empty( $es_matches[0] ) ) {
        $all_matches = array_merge( $all_matches, $es_matches[0] );
    }

    foreach ( $all_matches as $raw ) {
        $normalized = preg_replace( '/\D+/', '', $raw ); // digits only
        if ( preg_match( '/^(1)?(\d{10})$/', $normalized, $m ) ) {
            // US: ensure 10 digits core
            $core = $m[2];
            $normalized_e164 = '+1' . $core;
        } elseif ( preg_match( '/^(?:34)?([6789]\d{8})$/', $normalized, $m ) ) {
            // Spain
            $core = $m[1];
            $normalized_e164 = '+34' . $core;
        } else {
            continue; // skip unsupported
        }
        if ( ! isset( $numbers[ $normalized_e164 ] ) ) {
            $numbers[ $normalized_e164 ] = $raw; // keep first raw version
        }
    }
    ksort( $numbers );
    return $numbers; // normalized => example raw
}

/**
 * Store extracted phone numbers to database
 */
function pnc_store_numbers( $numbers ) {
    if ( empty( $numbers ) ) {
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'pnc_extracted_numbers';
    $user_id = get_current_user_id();
    $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
    $time = current_time( 'mysql' );
    
    // Save to database for all users
    foreach ( $numbers as $normalized => $raw ) {
        $wpdb->insert(
            $table_name,
            array(
                'time' => $time,
                'user_id' => $user_id,
                'ip' => $ip,
                'normalized_number' => $normalized,
                'raw_number' => $raw,
            ),
            array( '%s', '%d', '%s', '%s', '%s' )
        );
    }
    
    // For logged in users, also store in user meta
    if ( $user_id ) {
        $existing = get_user_meta( $user_id, 'pnc_extracted_numbers', true );
        if ( ! is_array( $existing ) ) {
            $existing = array();
        }
        
        // Merge with existing numbers, keeping the format normalized => raw
        $updated = array_merge( $existing, $numbers );
        
        // Limit to most recent 1000 numbers to prevent metadata bloat
        if ( count( $updated ) > 1000 ) {
            $updated = array_slice( $updated, -1000, 1000, true );
        }
        
        update_user_meta( $user_id, 'pnc_extracted_numbers', $updated );
    }
    
    return count( $numbers );
}

/**
 * Get previously extracted numbers for the current user
 */
function pnc_get_user_numbers() {
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        return array();
    }
    
    $numbers = get_user_meta( $user_id, 'pnc_extracted_numbers', true );
    if ( ! is_array( $numbers ) ) {
        return array();
    }
    
    return $numbers;
}

/**
 * Render the public form and handle submission.
 */
function pnc_render_form( $atts = [] ) {
    static $instance = 0;
    $instance++;
    $id_suffix = $instance;

    $atts = shortcode_atts( [ 'show_normalized' => 'yes' ], $atts, 'phone_number_cleanup' );
    $show_normalized = ( 'yes' === strtolower( $atts['show_normalized'] ) );

    $form_id = 'pnc_form_' . $id_suffix;
    $textarea_name = 'pnc_input_' . $id_suffix;
    $submit_name = 'pnc_submit_' . $id_suffix;

    $output = '';
    $output .= '<div class="pnc-container">';
    
    // Data collection notice
    $output .= '<div class="pnc-notice">';
    $output .= '<p>' . esc_html__( 'Note: Extracted phone numbers will be stored in our database. If you are logged in, they will be associated with your account.', 'phone-number-clean-up' ) . '</p>';
    $output .= '</div>';
    
    $output .= '<form method="post" id="' . esc_attr( $form_id ) . '">';
    $output .= '<div>';
    $output .= '<label for="' . esc_attr( $textarea_name ) . '">' . esc_html__( 'Paste your text here', 'phone-number-clean-up' ) . '</label><br />';
    $output .= '<textarea id="' . esc_attr( $textarea_name ) . '" name="' . esc_attr( $textarea_name ) . '" rows="8" cols="50" placeholder="' . esc_attr__( 'Paste your text here...', 'phone-number-clean-up' ) . '"></textarea><br />';
    $output .= wp_nonce_field( 'pnc_extract_action', 'pnc_nonce_' . $id_suffix, true, false );
    $output .= '<input type="hidden" name="pnc_instance" value="' . esc_attr( $id_suffix ) . '" />';
    $output .= '<input type="submit" name="' . esc_attr( $submit_name ) . '" value="' . esc_attr__( 'Extract Phone Numbers', 'phone-number-clean-up' ) . '" />';
    $output .= '</div>';
    $output .= '</form>';

    // Handle submission for this instance only.
    if ( isset( $_POST['pnc_instance'], $_POST[ $submit_name ] ) && (int) $_POST['pnc_instance'] === $id_suffix ) {
        $nonce_field = 'pnc_nonce_' . $id_suffix;
        if ( ! isset( $_POST[ $nonce_field ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $nonce_field ] ) ), 'pnc_extract_action' ) ) {
            $output .= '<p>' . esc_html__( 'Security check failed. Please reload the page and try again.', 'phone-number-clean-up' ) . '</p>';
            return $output;
        }
        if ( ! pnc_rate_limit_check() ) {
            $output .= '<p>' . esc_html__( 'Rate limit exceeded. Please wait and try again later.', 'phone-number-clean-up' ) . '</p>';
            return $output;
        }
        if ( empty( $_POST[ $textarea_name ] ) ) {
            $output .= '<p>' . esc_html__( 'No input provided.', 'phone-number-clean-up' ) . '</p>';
            return $output;
        }
        $input_raw = wp_unslash( $_POST[ $textarea_name ] );
        if ( strlen( $input_raw ) > PNC_MAX_INPUT_CHARS ) {
            $output .= '<p>' . sprintf( esc_html__( 'Input too long (max %s characters).', 'phone-number-clean-up' ), number_format_i18n( PNC_MAX_INPUT_CHARS ) ) . '</p>';
            return $output;
        }
        $input = sanitize_textarea_field( $input_raw );
        $numbers = pnc_extract_numbers_from_text( $input );
        if ( ! empty( $numbers ) ) {
            // Store numbers in database
            pnc_store_numbers( $numbers );
            
            $output .= '<h3>' . esc_html__( 'Found Phone Numbers', 'phone-number-clean-up' ) . '</h3>';
            $output .= '<ul>';
            foreach ( $numbers as $normalized => $raw ) {
                $display = $show_normalized ? $normalized . ' (' . $raw . ')' : $raw;
                $output .= '<li>' . esc_html( $display ) . '</li>';
            }
            $output .= '</ul>';
            $output .= '<p>' . sprintf( esc_html__( '%d unique phone number(s) found.', 'phone-number-clean-up' ), count( $numbers ) ) . '</p>';
        } else {
            $output .= '<p>' . esc_html__( 'No phone numbers found.', 'phone-number-clean-up' ) . '</p>';
        }
    }
    
    // Display previously saved numbers for logged-in users
    if ( is_user_logged_in() ) {
        $saved_numbers = pnc_get_user_numbers();
        if ( ! empty( $saved_numbers ) ) {
            $output .= '<div class="pnc-saved-numbers">';
            $output .= '<h3>' . esc_html__( 'Your Previously Extracted Numbers', 'phone-number-clean-up' ) . '</h3>';
            $output .= '<ul>';
            foreach ( $saved_numbers as $normalized => $raw ) {
                $display = $show_normalized ? $normalized . ' (' . $raw . ')' : $raw;
                $output .= '<li>' . esc_html( $display ) . '</li>';
            }
            $output .= '</ul>';
            $output .= '</div>';
        }
    }
    
    $output .= '</div>'; // close container
    
    return $output;
}

/**
 * Shortcode handler (maintained original shortcode name for backward compatibility)
 */
function pnc_shortcode_handler( $atts ) {
    return pnc_render_form( $atts );
}
add_shortcode( 'phone_number_cleanup', 'pnc_shortcode_handler' );

/**
 * Gutenberg Block Registration (server-side rendered)
 */
function pnc_register_block() {
    if ( ! function_exists( 'register_block_type' ) ) {
        return; // Gutenberg not available.
    }
    register_block_type( __DIR__ . '/block', [
        'render_callback' => function( $attributes ) {
            $atts = [];
            if ( isset( $attributes['showNormalized'] ) ) {
                $atts['show_normalized'] = $attributes['showNormalized'] ? 'yes' : 'no';
            }
            return pnc_render_form( $atts );
        },
    ] );
}
add_action( 'init', 'pnc_register_block' );

// Add admin page to view all stored numbers (admin only)
function pnc_admin_menu() {
    add_management_page(
        __( 'Extracted Phone Numbers', 'phone-number-clean-up' ),
        __( 'Phone Numbers', 'phone-number-clean-up' ),
        'manage_options',
        'pnc-numbers',
        'pnc_admin_page'
    );
}
add_action( 'admin_menu', 'pnc_admin_menu' );

// Admin page callback
function pnc_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'pnc_extracted_numbers';
    $numbers = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY time DESC LIMIT 1000", ARRAY_A );
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__( 'Extracted Phone Numbers', 'phone-number-clean-up' ); ?></h1>
        <p><?php echo esc_html__( 'This page shows the most recent 1000 phone numbers extracted by users.', 'phone-number-clean-up' ); ?></p>
        
        <?php if ( empty( $numbers ) ) : ?>
            <p><?php echo esc_html__( 'No phone numbers have been extracted yet.', 'phone-number-clean-up' ); ?></p>
        <?php else : ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__( 'Time', 'phone-number-clean-up' ); ?></th>
                        <th><?php echo esc_html__( 'User', 'phone-number-clean-up' ); ?></th>
                        <th><?php echo esc_html__( 'IP Address', 'phone-number-clean-up' ); ?></th>
                        <th><?php echo esc_html__( 'Number (E.164)', 'phone-number-clean-up' ); ?></th>
                        <th><?php echo esc_html__( 'Original Format', 'phone-number-clean-up' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $numbers as $row ) : ?>
                        <tr>
                            <td><?php echo esc_html( $row['time'] ); ?></td>
                            <td>
                                <?php if ( $row['user_id'] ) : ?>
                                    <?php $user = get_userdata( $row['user_id'] ); ?>
                                    <?php echo $user ? esc_html( $user->display_name ) : esc_html__( 'Unknown User', 'phone-number-clean-up' ); ?>
                                <?php else : ?>
                                    <?php echo esc_html__( 'Guest', 'phone-number-clean-up' ); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html( $row['ip'] ); ?></td>
                            <td><?php echo esc_html( $row['normalized_number'] ); ?></td>
                            <td><?php echo esc_html( $row['raw_number'] ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

// End of file