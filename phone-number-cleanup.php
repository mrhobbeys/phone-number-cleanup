<?php
/*
Plugin Name: Phone Number Clean Up
Description: Extracts phone numbers from pasted text on a public page.
Version: 1.0
Author: mrhobbeys
*/

function pnc_extract_phone_numbers($atts) {
    $output = '<form method="post">';
    $output .= '<textarea name="pnc_input" rows="8" cols="50" placeholder="Paste your text here..."></textarea><br>';
    $output .= '<input type="submit" name="pnc_submit" value="Extract Phone Numbers">';
    $output .= '</form>';

    if (isset($_POST['pnc_submit']) && !empty($_POST['pnc_input'])) {
        $input = sanitize_textarea_field($_POST['pnc_input']);
        // Simple regex for US numbers (customize as needed)
        preg_match_all('/\b(?:\+?1[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}\b/', $input, $matches);
        if (!empty($matches[0])) {
            $output .= '<h3>Found Phone Numbers:</h3><ul>';
            foreach ($matches[0] as $number) {
                $output .= '<li>' . esc_html($number) . '</li>';
            }
            $output .= '</ul>';
        } else {
            $output .= '<p>No phone numbers found.</p>';
        }
    }
    return $output;
}
add_shortcode('phone_number_cleanup', 'pnc_extract_phone_numbers');