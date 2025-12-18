<?php
/**
 * File: /wp-content/plugins/aaa-admin-controler-v2/ajax/class-ac-ajax-v2.php
 * Purpose: Settings/Sessions AJAX (v2) — network-wide user fetching per role
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! function_exists('aaa_ac_log') ){ function aaa_ac_log($m,$c=[]){ /* toggle in loader */ } }

class AC_Ajax_V2 {

	public static function init(){
		add_action('wp_ajax_aaa_ac_load_sessions_v2',       [__CLASS__,'load_sessions']);
		add_action('wp_ajax_aaa_ac_load_settings_users_v2', [__CLASS__,'load_settings_users']);
		add_action('wp_ajax_aaa_ac_save_user_settings_v2',  [__CLASS__,'save_user_settings']);
	}

	protected static function users_by_role_network( $role ){
		$role = sanitize_key($role);
		if ( ! $role ) return [];
		$users_map = [];
		$sites = function_exists('get_sites') ? get_sites(['number'=>0]) : [];
		if ( empty($sites) ){
			$site_users = get_users(['role__in'=>[$role],'orderby'=>'display_name','order'=>'ASC','number'=>2000]);
			foreach($site_users as $u){ $users_map[$u->ID] = $u; }
			return array_values($users_map);
		}
		foreach( $sites as $site ){
			switch_to_blog( (int)$site->blog_id );
			$site_users = get_users([
				'role__in' => [$role],
				'orderby'  => 'display_name',
				'order'    => 'ASC',
				'fields'   => ['ID','display_name'],
				'number'   => 5000,
			]);
			foreach( $site_users as $u ){
				if ( empty($users_map[ $u->ID ]) ) $users_map[ $u->ID ] = $u;
			}
			restore_current_blog();
		}
		usort($users_map, function($a,$b){ return strcasecmp($a->display_name, $b->display_name); });
		return array_values($users_map);
	}

	public static function load_sessions(){
		if ( ! current_user_can('manage_network_users') ) wp_send_json_error('no_cap');
		check_ajax_referer('aaa_ac_ajax','nonce');

		$role    = isset($_GET['role']) ? sanitize_key($_GET['role']) : '';
		$enabled = function_exists('aaa_ac_get_enabled_roles') ? aaa_ac_get_enabled_roles() : [];
		if ( ! $role || ! in_array($role, $enabled, true) ){
			wp_send_json_success(['rows'=>'']); return;
		}

		global $wpdb;
		$log_table = $wpdb->base_prefix . ( defined('AAA_AC_TABLE_PREFIX') ? AAA_AC_TABLE_PREFIX : 'aaa_ac_' ) . 'session_logs';

		$users = self::users_by_role_network( $role );
		ob_start(); $i=0;

		foreach( $users as $u ){
			$log = $wpdb->get_row( $wpdb->prepare(
				"SELECT id, session_token, login_time, is_online
				 FROM {$log_table}
				 WHERE user_id=%d AND is_online=1
				 ORDER BY login_time DESC
				 LIMIT 1", $u->ID
			) );

			$log_id = $log ? (int)$log->id : 0;
			$start  = $log && $log->login_time ? (string)$log->login_time : '';
			$token  = $log && $log->session_token ? (string)$log->session_token : '';

			$online = $log && (int)$log->is_online === 1;
			if ( ! $online && function_exists('aaa_ac_is_user_online') ) $online = aaa_ac_is_user_online($u->ID);

			if ( ! $start && class_exists('WP_Session_Tokens') ){
				$mgr = WP_Session_Tokens::get_instance( $u->ID );
				$tokens = is_object($mgr) ? $mgr->get_all() : [];
				$best = 0;
				foreach( $tokens as $hash => $t ){
					$login = isset($t['login']) ? (int)$t['login'] : 0;
					if ( $login > $best ) $best = $login;
				}
				if ( $best ) $start = get_date_from_gmt( gmdate('Y-m-d H:i:s', $best), 'Y-m-d H:i:s' );
			}

			$tokCell = '&mdash;';
			if ( is_string($token) && preg_match('/^[a-f0-9]{40,64}$/i', $token) ){
				$short  = substr($token, 0, 12) . ( strlen($token) > 12 ? '…' : '' );
				$tokCell = '<code class="aaa-ac-code" title="'.esc_attr($token).'">'.esc_html($short).'</code>';
			}

			echo '<tr>';
			echo '<td>'. ( $log_id ? (int)$log_id : '&mdash;' ) .'</td>';
			echo '<td>'.(++$i).'</td>';
			echo '<td>'.esc_html($u->display_name).'</td>';
			echo '<td><a href="'.esc_url(network_admin_url('user-edit.php?user_id='.$u->ID)).'">'.(int)$u->ID.'</a></td>';
			echo '<td>'.($start?esc_html($start):'&mdash;').'</td>';
			echo '<td>'.($online?'<span class="aaa-ac-online">Online</span>':'<span class="aaa-ac-offline">Offline</span>').'</td>';
			echo '<td>';
			if ( $online ){
				$url = wp_nonce_url( add_query_arg(['page'=>'aaa-ac-online','aaa_ac_end_session'=>$u->ID], network_admin_url('admin.php')), 'aaa_ac_end_'.$u->ID );
				echo '<a class="button button-secondary" href="'.esc_url($url).'">'.esc_html__('End Session','aaa-ac').'</a>';
			} else {
				echo '—';
			}
			echo '</td>';
			echo '<td>'.$tokCell.'</td>';
			echo '</tr>';
		}
		wp_send_json_success(['rows'=>ob_get_clean()]);
	}

	public static function load_settings_users(){
		if ( ! current_user_can('manage_network_users') ) wp_send_json_error('no_cap');
		check_ajax_referer('aaa_ac_ajax','nonce');

		$role    = isset($_GET['role']) ? sanitize_key($_GET['role']) : '';
		$enabled = function_exists('aaa_ac_get_enabled_roles') ? aaa_ac_get_enabled_roles() : [];
		if ( ! $role || ! in_array($role, $enabled, true) ){
			wp_send_json_success(['rows'=>'']); return;
		}

		$users = self::users_by_role_network( $role );
		ob_start(); $i=0;
		foreach( $users as $u ){
			$force   = (string) get_user_meta($u->ID, defined('AAA_AC_META_FORCE')?AAA_AC_META_FORCE:'ac_force_times', true);
			$popup   = (string) get_user_meta($u->ID, defined('AAA_AC_META_POPUP')?AAA_AC_META_POPUP:'ac_popup_times', true);
			$include = get_user_meta($u->ID, defined('AAA_AC_META_INCLUDE')?AAA_AC_META_INCLUDE:'ac_include_logs', true) === 'yes';
			echo '<tr data-uid="'.(int)$u->ID.'">';
			echo '<td>'.(++$i).'</td>';
			echo '<td>'.esc_html($u->display_name).'</td>';
			echo '<td><a href="'.esc_url(network_admin_url('user-edit.php?user_id='.$u->ID)).'">'.(int)$u->ID.'</a></td>';
			echo '<td><input type="text" class="regular-text aaa-ac-csv aaa-ac-force" placeholder="e.g., 11:30,23:45" value="'.esc_attr($force).'"/></td>';
			echo '<td><input type="text" class="regular-text aaa-ac-csv aaa-ac-popup" placeholder="e.g., 10:15,15:00" value="'.esc_attr($popup).'"/></td>';
			echo '<td><label><input type="checkbox" class="aaa-ac-inc" '.checked($include,true,false).'/> '.esc_html__('Include in logs','aaa-ac').'</label></td>';
			echo '</tr>';
		}
		wp_send_json_success(['rows'=>ob_get_clean()]);
	}

	public static function save_user_settings(){
		if ( ! current_user_can('manage_network_users') ) wp_send_json_error('no_cap');
		check_ajax_referer('aaa_ac_ajax','nonce');

		$payload = isset($_POST['users']) ? wp_unslash($_POST['users']) : '';
		if ( ! $payload ) wp_send_json_error('no_payload');

		$data = json_decode( $payload, true );
		if ( ! is_array($data) ) wp_send_json_error('bad_json');

		$saved = 0; $skipped = 0;

		foreach( $data as $row ){
			$user_id = isset($row['user_id']) ? absint($row['user_id']) : 0;
			if ( ! $user_id ) { $skipped++; continue; }

			$raw_force = isset($row['force']) ? (string)$row['force'] : '';
			$raw_popup = isset($row['popup']) ? (string)$row['popup'] : '';
			$inc       = ! empty($row['include']) ? 'yes' : 'no';

			$did = false;

			if ( $raw_force !== '' ){
				$force = function_exists('aaa_ac_sanitize_csv_times') ? aaa_ac_sanitize_csv_times($raw_force) : $raw_force;
				update_user_meta( $user_id, defined('AAA_AC_META_FORCE')?AAA_AC_META_FORCE:'ac_force_times', $force );
				$did = true;
			}
			if ( $raw_popup !== '' ){
				$popup = function_exists('aaa_ac_sanitize_csv_times') ? aaa_ac_sanitize_csv_times($raw_popup) : $raw_popup;
				update_user_meta( $user_id, defined('AAA_AC_META_POPUP')?AAA_AC_META_POPUP:'ac_popup_times', $popup );
				$did = true;
			}

			$prev_inc = get_user_meta( $user_id, defined('AAA_AC_META_INCLUDE')?AAA_AC_META_INCLUDE:'ac_include_logs', true );
			if ( $prev_inc !== $inc ){
				update_user_meta( $user_id, defined('AAA_AC_META_INCLUDE')?AAA_AC_META_INCLUDE:'ac_include_logs', $inc );
				$did = true;
			}

			if ( $did ) $saved++; else $skipped++;
		}
		wp_send_json_success(['saved'=>$saved,'skipped'=>$skipped]);
	}
}
