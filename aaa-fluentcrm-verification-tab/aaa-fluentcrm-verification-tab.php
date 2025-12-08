<?php
/**
 * Plugin Name: AAA FluentCRM Verification Tab (Phase 1)
 * Description: Adds a read-only "Verification" tab to FluentCRM contact pages showing DOB, ID fields, and embedded uploads with placeholders.
 * Version: 1.0.1
 * Author: Webmaster Workflow
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ---- Config / Slugs (guarded constant) ---- */
if ( ! defined( 'AAA_FCRM_UPLOAD_SUBDIR' ) ) {
    define( 'AAA_FCRM_UPLOAD_SUBDIR', 'addify_registration_uploads' );
}
const AAA_FCRM_FIELD_DOB           = 'date_of_birth';
const AAA_FCRM_FIELD_ID_TYPE       = 'contact_id_type';
const AAA_FCRM_FIELD_ID_NUMBER     = 'contact_id_number';
const AAA_FCRM_FIELD_ID_EXPIRY     = 'contact_id_expiration';
const AAA_FCRM_FIELD_ID_UPLOAD     = 'contact_id_upload';
const AAA_FCRM_FIELD_SELFIE_UPLOAD = 'contact_selfie_upload';
const AAA_FCRM_FIELD_REC_UPLOAD    = 'contact_rec';

/* ---- Helpers ---- */
function aaa_fcrm_fmt_date( $ymd, $with_age = false ) {
    $ymd = trim( (string) $ymd ); if ( $ymd === '' ) return '';
    try {
        $dt = new DateTime( $ymd );
        $out = $dt->format( 'F j, Y' );
        if ( $with_age ) {
            $age = ( new DateTime('now', wp_timezone() ) )->diff( $dt )->y;
            $out .= " (Age {$age})";
        }
        return $out;
    } catch ( Throwable $e ) { return esc_html( $ymd ); }
}

function aaa_fcrm_extract_urls( $raw ) {
    $raw = (string) $raw;
    if ( $raw === '' ) return [];
    $parts = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
    if ( ! $parts ) return [];
    $uploads = wp_get_upload_dir();
    $base    = rtrim( $uploads['baseurl'], '/' ) . '/' . trim( AAA_FCRM_UPLOAD_SUBDIR, '/' ) . '/';
    $urls = [];
    foreach ( $parts as $p ) {
        if ( preg_match( '#^https?://#i', $p ) ) { $urls[] = esc_url_raw( $p ); }
        else {
            $file = basename( $p );
            if ( $file !== '' ) { $urls[] = esc_url_raw( $base . ltrim( $file, '/' ) ); }
        }
    }
    return array_values( array_unique( $urls ) );
}

function aaa_fcrm_render_img_gallery( array $urls ) {
    if ( ! $urls ) return '<span class="aaa-fcrm-missing">NOT PROVIDED</span>';
    $out = '<div class="aaa-fcrm-gallery">';
    foreach ( $urls as $u ) {
        $out .= '<a class="aaa-fcrm-thumb" href="' . esc_url( $u ) . '" target="_blank" rel="noopener noreferrer">'
             .  '<img src="' . esc_url( $u ) . '" alt="" loading="lazy" />'
             .  '</a>';
    }
    $out .= '</div>';
    return $out;
}
function aaa_fcrm_render_text_value( $text ) {
    $text = trim( (string) $text );
    return $text !== '' ? '<span class="aaa-fcrm-text">' . esc_html( $text ) . '</span>' : '<span class="aaa-fcrm-missing">NOT PROVIDED</span>';
}

/* ---- UI Section ---- */
add_action( 'fluent_crm/after_init', function () {
    if ( ! function_exists( 'FluentCrmApi' ) ) return;
    FluentCrmApi( 'extender' )->addProfileSection( 'aaa_verification', 'Verification', function( $content, $subscriber ) {
        $cf = is_object( $subscriber ) && is_callable( [ $subscriber, 'custom_fields' ] ) ? (array) $subscriber->custom_fields() : [];
        $val_dob       = isset( $subscriber->{AAA_FCRM_FIELD_DOB} ) ? (string) $subscriber->{AAA_FCRM_FIELD_DOB} : '';
        $val_id_type   = $cf[ AAA_FCRM_FIELD_ID_TYPE ]       ?? '';
        $val_id_num    = $cf[ AAA_FCRM_FIELD_ID_NUMBER ]     ?? '';
        $val_id_exp    = $cf[ AAA_FCRM_FIELD_ID_EXPIRY ]     ?? '';
        $val_id_up     = $cf[ AAA_FCRM_FIELD_ID_UPLOAD ]     ?? '';
        $val_selfie_up = $cf[ AAA_FCRM_FIELD_SELFIE_UPLOAD ] ?? '';
        $val_rec_up    = $cf[ AAA_FCRM_FIELD_REC_UPLOAD ]    ?? '';

        $html_dob   = $val_dob ? '<span class="aaa-fcrm-text">' . esc_html( aaa_fcrm_fmt_date( $val_dob, true ) ) . '</span>' : '<span class="aaa-fcrm-missing">NOT PROVIDED</span>';
        $html_type  = aaa_fcrm_render_text_value( $val_id_type );
        $html_num   = aaa_fcrm_render_text_value( $val_id_num );
        $html_exp   = $val_id_exp ? '<span class="aaa-fcrm-text">' . esc_html( aaa_fcrm_fmt_date( $val_id_exp ) ) . '</span>' : '<span class="aaa-fcrm-missing">NOT PROVIDED</span>';
        $html_id    = aaa_fcrm_render_img_gallery( aaa_fcrm_extract_urls( $val_id_up ) );
        $html_self  = aaa_fcrm_render_img_gallery( aaa_fcrm_extract_urls( $val_selfie_up ) );
        $html_rec   = aaa_fcrm_render_img_gallery( aaa_fcrm_extract_urls( $val_rec_up ) );

        $style = '<style>
            .aaa-fcrm-verif-wrap{padding:8px 12px}
            .aaa-fcrm-table{width:100%;border-collapse:collapse}
            .aaa-fcrm-table th,.aaa-fcrm-table td{padding:8px 10px;border-bottom:1px solid #e6e6e6;vertical-align:top}
            .aaa-fcrm-table th{width:220px;text-align:left;color:#555;font-weight:600}
            .aaa-fcrm-text{color:#222}
            .aaa-fcrm-missing{color:#c00;font-weight:600}
            .aaa-fcrm-gallery{display:flex;flex-wrap:wrap;gap:10px}
            .aaa-fcrm-thumb{display:inline-block;border:1px solid #ddd;padding:4px;border-radius:6px;background:#fafafa}
            .aaa-fcrm-thumb img{display:block;max-width:200px;height:auto}
        </style>';

        $rows = [
            [ 'Date of Birth',          $html_dob ],
            [ 'ID Type',                $html_type ],
            [ 'ID Number',              $html_num ],
            [ 'ID Expiration',          $html_exp ],
            [ 'Government ID Upload',   $html_id ],
            [ 'Selfie with ID Upload',  $html_self ],
            [ 'Medical Recommendation', $html_rec ],
        ];
        $tbody=''; foreach($rows as $r){ $tbody.='<tr><th>'.esc_html($r[0]).'</th><td>'.$r[1].'</td></tr>'; }

        $content['heading'] = 'Verification';
        $content['content_html'] = $style.'<div class="aaa-fcrm-verif-wrap"><table class="aaa-fcrm-table"><tbody>'.$tbody.'</tbody></table></div>';
        return $content;
    } );
} );
