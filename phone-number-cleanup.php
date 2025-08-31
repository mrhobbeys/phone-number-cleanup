<?php
/*
Plugin Name: Phone Number Clean Up
Description: Extracts phone numbers from pasted text on a public page.
Version: 1.1.0
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
    $pattern_es = '/(?:(?:\+34|0034)[\s-]?)?(?:[6789]\s?\d(?:\s?\d){7})/';

    $all_matches = [];
    preg_match_all( $pattern_us, $text, $us_matches );
    preg_match_all( $pattern_es, $text, $es_matches );
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

// End of file