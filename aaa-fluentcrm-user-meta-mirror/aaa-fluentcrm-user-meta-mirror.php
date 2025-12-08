<?php
/**
 * Plugin Name: AAA FluentCRM User Meta Mirror (Phase 2)
 * Description: Mirrors selected WP user_meta to FluentCRM contact fields (current site only). Robust filename/JSON→URL handling, and CLI to audit/repair/ensure custom fields.
 * Version: 1.0.3
 * Author: Webmaster Workflow
 */

if (!defined('ABSPATH')) { exit; }

/* ============== Debug ============== */
if (!defined('AAA_FCRM_MIRROR_DEBUG')) define('AAA_FCRM_MIRROR_DEBUG', true);
function aaa_fcrm_mirror_log($m){ if(AAA_FCRM_MIRROR_DEBUG){ error_log('[FCRM-MIRROR] '.(is_string($m)?$m:wp_json_encode($m))); }}

/* ============== Config ============== */
// Phase-1 may also define this; guard to avoid warnings.
if (!defined('AAA_FCRM_UPLOAD_SUBDIR')) {
    define('AAA_FCRM_UPLOAD_SUBDIR', 'addify_registration_uploads');
}
const AAA_FCRM_DOB_META_KEYS = ['afreg_additional_4625','dob','date_of_birth','birthday'];

/* Expected FluentCRM custom fields (slug, label, type, options[]) */
function aaa_fcrm_expected_fields() {
    return [
        'contact_id_type' => [
            'slug'    => 'contact_id_type',
            'label'   => 'ID Type',
            'type'    => 'select-one',
            'options' => ['Driver License or ID','Passport','Out of State ID','International ID'],
            'group'   => 'Verification'
        ],
        'contact_id_number' => [
            'slug'    => 'contact_id_number',
            'label'   => 'ID Number',
            'type'    => 'text',
            'options' => [],
            'group'   => 'Verification'
        ],
        'contact_id_expiration' => [
            'slug'    => 'contact_id_expiration',
            'label'   => 'ID Expiration',
            'type'    => 'date',
            'options' => [],
            'group'   => 'Verification'
        ],
        'contact_id_upload' => [
            'slug'    => 'contact_id_upload',
            'label'   => 'ID Upload',
            'type'    => 'text',
            'options' => [],
            'group'   => 'Verification'
        ],
        'contact_selfie_upload' => [
            'slug'    => 'contact_selfie_upload',
            'label'   => 'Selfie Upload',
            'type'    => 'text',
            'options' => [],
            'group'   => 'Verification'
        ],
        'contact_rec' => [
            'slug'    => 'contact_rec',
            'label'   => 'Medical Recommendation',
            'type'    => 'text',
            'options' => [],
            'group'   => 'Verification'
        ],
    ];
}

/* Map user_meta -> custom field slug & transform type for mirror */
const AAA_FCRM_FIELD_RULES = [
    'afreg_additional_4532' => ['slug'=>'contact_id_number',     'type'=>'text'],
    'afreg_additional_4623' => ['slug'=>'contact_id_expiration', 'type'=>'date'],
    'afreg_additional_4631' => ['slug'=>'contact_id_type',       'type'=>'text'],
    'afreg_additional_4626' => ['slug'=>'contact_id_upload',     'type'=>'file_url'],
    'afreg_additional_4627' => ['slug'=>'contact_selfie_upload', 'type'=>'file_url'],
    'afreg_additional_4630' => ['slug'=>'contact_rec',           'type'=>'file_url'],
];

/* ============== Helpers ============== */
function aaa_fcrm_mirror_normalize_date($raw){
    $raw = trim((string)$raw); if($raw==='') return '';
    if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$raw)) return $raw;
    if(ctype_digit($raw) && (int)$raw>100000000) return gmdate('Y-m-d',(int)$raw);
    if(preg_match('/^\d{2}\/\d{2}\/\d{4}$/',$raw)){ list($a,$b,$y)=array_map('intval',explode('/',$raw));
        $m=($a>12)?$b:$a; $d=($a>12)?$a:$b; if(checkdate($m,$d,$y)) return sprintf('%04d-%02d-%02d',$y,$m,$d); }
    $ts=strtotime($raw); return $ts?gmdate('Y-m-d',$ts):'';
}

/* Flatten any input (CSV string / JSON / serialized / arrays / objects) to list of strings */
function aaa_fcrm_mirror_flatten_list($input){
    $out=[]; $push=function($v) use (&$out,&$push){
        if(is_null($v)) return;
        if(is_scalar($v)){ $out[]= (string)$v; return; }
        if(is_object($v)) $v=(array)$v;
        if(is_array($v)){
            $assoc = array_keys($v)!==range(0,count($v)-1);
            if($assoc){
                foreach(['url','file_url','link','guid','path','file','filename','name'] as $ck){
                    if(isset($v[$ck]) && !is_array($v[$ck]) && !is_object($v[$ck])) $out[]=(string)$v[$ck];
                }
                foreach($v as $k=>$vv){ if(is_string($k) && preg_match('/\.(jpe?g|png|gif|webp|pdf)$/i',$k)) $out[]=$k; $push($vv); }
            }else{
                foreach($v as $vv){ $push($vv); }
            }
        }
    };

    if(is_array($input)||is_object($input)){ $push($input); return array_values(array_filter(array_map('trim',$out))); }

    $s=trim((string)$input); if($s==='') return [];
    $json=json_decode($s,true); if(json_last_error()===JSON_ERROR_NONE){ $push($json); return array_values(array_filter(array_map('trim',$out))); }
    if(function_exists('is_serialized') && is_serialized($s)){ $arr=@unserialize($s); $push($arr); return array_values(array_filter(array_map('trim',$out))); }

    $parts=array_map('trim',explode(',',$s)); foreach($parts as $p){ if($p!=='') $out[]=$p; }
    return array_values(array_unique($out));
}

function aaa_fcrm_mirror_to_upload_urls($raw){
    $items=aaa_fcrm_mirror_flatten_list($raw); if(!$items) return '';
    $uploads=wp_get_upload_dir();
    $base=rtrim($uploads['baseurl'],'/').'/'.trim(AAA_FCRM_UPLOAD_SUBDIR,'/').'/';
    $urls=[];
    foreach($items as $it){
        if(preg_match('#^https?://#i',$it)){ $urls[]=esc_url_raw($it); }
        else{ $name=basename($it); if($name!=='') $urls[]=esc_url_raw($base.ltrim($name,'/')); }
    }
    return implode(', ', array_unique($urls));
}

function aaa_fcrm_mirror_transform_value($type,$raw){
    if($raw===''||$raw===null) return '';
    switch($type){
        case 'date':     return aaa_fcrm_mirror_normalize_date($raw);
        case 'file_url': return aaa_fcrm_mirror_to_upload_urls($raw);
        default:         return is_scalar($raw)?(string)$raw:wp_json_encode($raw);
    }
}

/* ============== Push to FluentCRM (current site) ============== */
function aaa_fcrm_mirror_push_to_contact($user_id,$main=[],$custom_values=[]){
    if(!function_exists('FluentCrmApi')){ aaa_fcrm_mirror_log('FluentCRM not loaded'); return; }
    $user=get_user_by('ID',$user_id); if(!$user || empty($user->user_email)){ aaa_fcrm_mirror_log(['skip_user'=>$user_id,'reason'=>'no email']); return; }
    $data=array_merge(['user_id'=>(int)$user_id,'email'=>$user->user_email],$main);
    if($custom_values) $data['custom_values']=$custom_values;
    try{ FluentCrmApi('contacts')->createOrUpdate($data,true); }
    catch(\Throwable $e){ aaa_fcrm_mirror_log('Push error: '.$e->getMessage()); }
}

/* ============== Sync helper (NEW) ============== */
function aaa_fcrm_mirror_sync_user( $user_id ) {
    $user_id = (int) $user_id; if ( ! $user_id ) return;

    foreach ( AAA_FCRM_DOB_META_KEYS as $k ) {
        $v = get_user_meta( $user_id, $k, true );
        if ( $v !== '' && $v !== null ) {
            aaa_fcrm_mirror_on_user_meta_change( 0, $user_id, $k, $v );
        }
    }
    foreach ( array_keys( AAA_FCRM_FIELD_RULES ) as $k ) {
        $v = get_user_meta( $user_id, $k, true );
        if ( $v !== '' && $v !== null ) {
            aaa_fcrm_mirror_on_user_meta_change( 0, $user_id, $k, $v );
        }
    }
}

/* ============== Live sync on user_meta changes (current site) ============== */
function aaa_fcrm_mirror_on_user_meta_change( $meta_id, $user_id, $meta_key, $meta_value ) {
    $user_id = (int) $user_id;

    // DOB?
    if ( in_array( $meta_key, AAA_FCRM_DOB_META_KEYS, true ) ) {
        $dob = aaa_fcrm_mirror_normalize_date( $meta_value );
        if ( $dob ) {
            aaa_fcrm_mirror_push_to_contact( $user_id, [ 'date_of_birth' => $dob ], [] );
        }
        return;
    }

    // Custom mapped field?
    if ( isset( AAA_FCRM_FIELD_RULES[ $meta_key ] ) ) {
        $rule = AAA_FCRM_FIELD_RULES[ $meta_key ];
        $val  = aaa_fcrm_mirror_transform_value( $rule['type'], $meta_value );
        if ( $val !== '' ) {
            aaa_fcrm_mirror_push_to_contact( $user_id, [], [ $rule['slug'] => $val ] );
        }
    }
}
add_action( 'added_user_meta',   'aaa_fcrm_mirror_on_user_meta_change', 10, 4 );
add_action( 'updated_user_meta', 'aaa_fcrm_mirror_on_user_meta_change', 10, 4 );

/* When an existing profile is updated via WP admin, sync this user (no fake core hooks) */
add_action( 'profile_update', 'aaa_fcrm_mirror_sync_user', 50, 1 );

/* When a brand new user is created (e.g., via order creator), sync this user (no fake core hooks) */
add_action( 'user_register', 'aaa_fcrm_mirror_sync_user', 50, 1 );

/* ============== CLI: audit / repair / ensure / backfill ============== */
if(defined('WP_CLI') && WP_CLI){

    WP_CLI::add_command('aaa:fcrm-mirror-audit-fields', function(){
        if(!class_exists('\FluentCrm\App\Models\CustomContactField')){ WP_CLI::error('CustomContactField model missing on this site.'); }
        $model=new \FluentCrm\App\Models\CustomContactField;
        $fields=$model->getGlobalFields();
        WP_CLI::line(wp_json_encode($fields, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        WP_CLI::success('Printed current FluentCRM custom fields JSON above.');
    });

    WP_CLI::add_command('aaa:fcrm-mirror-repair-fields', function(){
        if(!class_exists('\FluentCrm\App\Models\CustomContactField')){ WP_CLI::error('CustomContactField model missing on this site.'); }
        $model=new \FluentCrm\App\Models\CustomContactField;
        $existing=$model->getGlobalFields();

        $clean=[]; $bySlug=[];
        foreach((array)$existing as $f){
            if(!is_array($f)) continue;
            $slug = isset($f['slug']) ? (string)$f['slug'] : '';
            if($slug==='') continue;
            if($slug==='custom_field'){ continue; }
            $normalized = [
                'slug'    => $slug,
                'label'   => isset($f['label']) ? (string)$f['label'] : $slug,
                'type'    => isset($f['type']) ? (string)$f['type'] : 'text',
                'options' => isset($f['options']) && is_array($f['options']) ? array_values($f['options']) : []
            ];
            if(isset($f['group'])) $normalized['group']=(string)$f['group'];
            $clean[]=$normalized; $bySlug[$slug]=true;
        }

        $model->saveGlobalFields($clean);
        WP_CLI::success('Repaired: removed stray/broken entries and normalized field shapes.');
    });

    WP_CLI::add_command('aaa:fcrm-mirror-ensure-fields', function(){
        if(!class_exists('\FluentCrm\App\Models\CustomContactField')){ WP_CLI::error('CustomContactField model missing on this site.'); }
        $model=new \FluentCrm\App\Models\CustomContactField;
        $existing=$model->getGlobalFields();

        $expected = aaa_fcrm_expected_fields();
        $bySlug=[];
        $out=[];

        foreach((array)$existing as $f){
            if(!is_array($f)) continue;
            $slug = isset($f['slug']) ? (string)$f['slug'] : '';
            if($slug==='') continue;

            if(isset($expected[$slug])){
                $def = $expected[$slug];
                $f['label']   = $def['label'];
                $f['type']    = $def['type'];
                $f['options'] = $def['options'];
                $f['group']   = $def['group'];
                $bySlug[$slug] = true;
                WP_CLI::log("Updated custom field: {$slug}");
            }
            $out[]=$f;
        }
        foreach($expected as $slug=>$def){
            if(!isset($bySlug[$slug])){
                $out[]=$def;
                WP_CLI::log("Created custom field: {$slug}");
            }
        }

        $model->saveGlobalFields($out);
        WP_CLI::success('Custom fields ensured with correct labels/types/options.');
    });

    WP_CLI::add_command('aaa:fcrm-mirror-backfill', function(){
        global $wpdb;
        $keys=array_unique(array_merge(AAA_FCRM_DOB_META_KEYS, array_keys(AAA_FCRM_FIELD_RULES)));
        $in=implode("','", array_map('esc_sql',$keys));
        $rows=$wpdb->get_results("SELECT user_id, meta_key, meta_value FROM {$wpdb->usermeta} WHERE meta_key IN ('$in') AND meta_value <> '' ORDER BY user_id ASC");

        $seen=[];
        foreach($rows as $row){
            $uid=(int)$row->user_id;
            if(!isset($seen[$uid])) $seen[$uid]=['main'=>[],'custom'=>[]];

            if(in_array($row->meta_key,AAA_FCRM_DOB_META_KEYS,true)){
                $dob=aaa_fcrm_mirror_normalize_date($row->meta_value);
                if($dob) $seen[$uid]['main']['date_of_birth']=$dob;
            } elseif(isset(AAA_FCRM_FIELD_RULES[$row->meta_key])){
                $r=AAA_FCRM_FIELD_RULES[$row->meta_key];
                $val=aaa_fcrm_mirror_transform_value($r['type'],$row->meta_value);
                if($val!=='') $seen[$uid]['custom'][$r['slug']]=$val;
            }
        }

        $count=0;
        foreach($seen as $uid=>$payload){
            aaa_fcrm_mirror_push_to_contact($uid,$payload['main'],$payload['custom']);
            $count++;
        }
        WP_CLI::success("Mirrored {$count} user(s) to FluentCRM on this site.");
    });
}
/* ============================================================
 * Admin Settings Page: FluentCRM → User Meta Mirror
 * Adds two buttons: Ensure Fields, Run Backfill Now
 * ============================================================ */

/** Programmatic ensure (same logic as CLI) */
function aaa_fcrm_mirror_ensure_fields_programmatic() {
    if ( ! class_exists('\FluentCrm\App\Models\CustomContactField') ) {
        throw new \Exception('FluentCRM CustomContactField model not found. Is FluentCRM active on this site?');
    }
    $model    = new \FluentCrm\App\Models\CustomContactField;
    $existing = (array) $model->getGlobalFields();
    $expected = aaa_fcrm_expected_fields();

    $out = [];
    $seen = [];
    $created = [];
    $updated = [];

    foreach ( $existing as $f ) {
        if ( ! is_array($f) || empty($f['slug']) ) { continue; }
        $slug = (string) $f['slug'];

        if ( isset($expected[$slug]) ) {
            $def = $expected[$slug];
            // Normalize to our definition
            $f['label']   = $def['label'];
            $f['type']    = $def['type'];
            $f['options'] = $def['options'];
            $f['group']   = $def['group'];
            $updated[]    = $slug;
            $seen[$slug]  = true;
        }
        $out[] = $f;
    }

    foreach ( $expected as $slug => $def ) {
        if ( empty($seen[$slug]) ) {
            $out[]    = $def;
            $created[] = $slug;
        }
    }

    $model->saveGlobalFields($out);

    return [
        'created' => $created,
        'updated' => $updated,
    ];
}

/** Programmatic backfill (same logic as CLI) */
function aaa_fcrm_mirror_run_backfill_programmatic() {
    global $wpdb;
    $keys = array_unique( array_merge( AAA_FCRM_DOB_META_KEYS, array_keys( AAA_FCRM_FIELD_RULES ) ) );
    if ( ! $keys ) { return 0; }

    $in   = implode( "','", array_map( 'esc_sql', $keys ) );
    $rows = $wpdb->get_results( "SELECT user_id, meta_key, meta_value FROM {$wpdb->usermeta} WHERE meta_key IN ('$in') AND meta_value <> '' ORDER BY user_id ASC" );

    $by_user = [];
    foreach ( (array) $rows as $row ) {
        $uid = (int) $row->user_id;
        if ( ! isset( $by_user[ $uid ] ) ) {
            $by_user[ $uid ] = [ 'main' => [], 'custom' => [] ];
        }

        if ( in_array( $row->meta_key, AAA_FCRM_DOB_META_KEYS, true ) ) {
            $dob = aaa_fcrm_mirror_normalize_date( $row->meta_value );
            if ( $dob ) {
                $by_user[ $uid ]['main']['date_of_birth'] = $dob;
            }
        } elseif ( isset( AAA_FCRM_FIELD_RULES[ $row->meta_key ] ) ) {
            $r   = AAA_FCRM_FIELD_RULES[ $row->meta_key ];
            $val = aaa_fcrm_mirror_transform_value( $r['type'], $row->meta_value );
            if ( $val !== '' ) {
                $by_user[ $uid ]['custom'][ $r['slug'] ] = $val;
            }
        }
    }

    $count = 0;
    foreach ( $by_user as $uid => $payload ) {
        aaa_fcrm_mirror_push_to_contact( $uid, $payload['main'], $payload['custom'] );
        $count++;
    }
    return $count;
}

/** Admin POST handler (nonce + capability) */
add_action( 'admin_init', function () {
    if ( empty( $_POST['aaa_fcrm_mirror_action'] ) ) { return; }
    if ( ! current_user_can( 'manage_options' ) ) { return; }

    check_admin_referer( 'aaa_fcrm_mirror_do' );

    $action = sanitize_text_field( $_POST['aaa_fcrm_mirror_action'] );
    $code   = 'success';
    $msg    = '';

    try {
        if ( $action === 'ensure_fields' ) {
            $res = aaa_fcrm_mirror_ensure_fields_programmatic();
            $msg = sprintf(
                'Fields ensured. Created: %d; Updated: %d.',
                count( $res['created'] ),
                count( $res['updated'] )
            );
        } elseif ( $action === 'run_backfill' ) {
            $n   = aaa_fcrm_mirror_run_backfill_programmatic();
            $msg = "Backfill complete for {$n} user(s) on this site.";
        } else {
            $code = 'error';
            $msg  = 'Unknown action.';
        }
    } catch ( \Throwable $e ) {
        $code = 'error';
        $msg  = 'Error: ' . $e->getMessage();
    }

    set_transient( 'aaa_fcrm_mirror_admin_notice', [ 'code' => $code, 'msg' => $msg ], 60 );
    wp_safe_redirect( admin_url( 'admin.php?page=aaa-fluentcrm-user-meta-mirror' ) );
    exit;
} );

/** Admin notice */
add_action( 'admin_notices', function () {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    $notice = get_transient( 'aaa_fcrm_mirror_admin_notice' );
    if ( ! $notice ) { return; }
    delete_transient( 'aaa_fcrm_mirror_admin_notice' );
    $cls = ( $notice['code'] === 'success' ) ? 'notice notice-success' : 'notice notice-error';
    echo '<div class="' . esc_attr( $cls ) . '"><p>' . esc_html( $notice['msg'] ) . '</p></div>';
} );

/** Menu item under FluentCRM (bottom) */
add_action( 'admin_menu', function () {
    add_submenu_page(
        'fluentcrm-admin',
        'User Meta Mirror',
        'User Meta Mirror',
        'manage_options',
        'aaa-fluentcrm-user-meta-mirror',
        'aaa_fcrm_mirror_render_settings_page'
    );
}, 999 );

/** Render settings page */
function aaa_fcrm_mirror_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    $page_url = admin_url( 'admin.php?page=aaa-fluentcrm-user-meta-mirror' );
    ?>
    <div class="wrap">
        <h1>FluentCRM — User Meta Mirror</h1>
        <p>Current site: <code><?php echo (int) get_current_blog_id(); ?></code></p>

        <form method="post" action="<?php echo esc_url( $page_url ); ?>" style="margin-bottom:16px;">
            <?php wp_nonce_field( 'aaa_fcrm_mirror_do' ); ?>
            <input type="hidden" name="aaa_fcrm_mirror_action" value="ensure_fields">
            <?php submit_button( 'Ensure Custom Fields', 'primary', 'submit', false ); ?>
            <span class="description" style="margin-left:8px;">
                Creates/normalizes the 6 verification fields for this site.
            </span>
        </form>

        <form method="post" action="<?php echo esc_url( $page_url ); ?>">
            <?php wp_nonce_field( 'aaa_fcrm_mirror_do' ); ?>
            <input type="hidden" name="aaa_fcrm_mirror_action" value="run_backfill">
            <?php submit_button( 'Run Backfill Now', 'secondary', 'submit', false ); ?>
            <span class="description" style="margin-left:8px;">
                Pushes values from user meta into FluentCRM contacts on this site.
            </span>
        </form>
    </div>
    <?php
}
