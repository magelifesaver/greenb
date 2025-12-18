<?php
/**
 * File: /wp-content/plugins/aaa-admin-controler-v2/ajax/class-ac-reports.php
 * Purpose: AJAX for Reports tab: list + CSV export from aaa_ac_session_logs
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class AC_Reports_Ajax {

	public static function init(){
		add_action('wp_ajax_aaa_ac_reports_load',   [__CLASS__, 'load']);
		add_action('wp_ajax_aaa_ac_reports_export', [__CLASS__, 'export']);
	}

	protected static function table(){
		global $wpdb;
		return $wpdb->base_prefix . ( defined('AAA_AC_TABLE_PREFIX') ? AAA_AC_TABLE_PREFIX : 'aaa_ac_' ) . 'session_logs';
	}

	protected static function sanitize_date($d){
		$d = trim((string)$d);
		return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) ? $d : '';
	}

	protected static function where_sql_and_args( $role, $start, $end ){
		$where = ' WHERE 1=1 ';
		$args  = [];

		if ( $role !== '' ){
			$where .= ' AND l.role_at_login = %s ';
			$args[] = sanitize_key($role);
		}
		if ( $start !== '' ){
			$where .= ' AND l.login_time >= %s ';
			$args[] = $start . ' 00:00:00';
		}
		if ( $end !== '' ){
			$where .= ' AND l.login_time <= %s ';
			$args[] = $end . ' 23:59:59';
		}
		return [$where, $args];
	}

	protected static function trigger_label( $end_trigger, $user_action ){
		$label = (string)$end_trigger;
		if ( $label === 'admin' && is_string($user_action) && strpos($user_action, 'admin:') === 0 ){
			$actor = trim( substr($user_action, strlen('admin:')) );
			if ( $actor !== '' ){
				$label .= ' - ' . $actor;
			}
		}
		return $label;
	}

	public static function load(){
		if ( ! current_user_can('manage_network_users') ) wp_send_json_error('no_cap');
		check_ajax_referer('aaa_ac_ajax', 'nonce');

		global $wpdb;
		$table = self::table();
		$users = $wpdb->users;

		$role  = isset($_GET['role'])  ? sanitize_key($_GET['role']) : '';
		$start = isset($_GET['start']) ? self::sanitize_date($_GET['start']) : '';
		$end   = isset($_GET['end'])   ? self::sanitize_date($_GET['end'])   : '';

		$sort  = isset($_GET['sort'])  ? sanitize_key($_GET['sort']) : 'login_time';
		$dir   = (isset($_GET['dir']) && strtoupper($_GET['dir'])==='ASC') ? 'ASC' : 'DESC';

		$whitelist = ['id','login_time','logout_time','user_id','end_trigger','role_at_login','is_online','session_token'];
		if ( ! in_array($sort, $whitelist, true) ) $sort = 'login_time';

		$page = max(1, (int)($_GET['page'] ?? 1));
		$per  = min( max(10, (int)($_GET['per'] ?? 50)), 200);
		$off  = ($page - 1) * $per;

		list($where, $args) = self::where_sql_and_args($role, $start, $end);

		$sql = "
			SELECT SQL_CALC_FOUND_ROWS
			       l.*, u.display_name
			FROM   {$table} l
			LEFT JOIN {$users} u ON u.ID = l.user_id
			{$where}
			ORDER BY {$sort} {$dir}
			LIMIT %d OFFSET %d
		";
		$args2 = array_merge($args, [ $per, $off ]);
		$q = $wpdb->prepare( $sql, $args2 );
		$rows = $wpdb->get_results( $q );
		$total = (int) $wpdb->get_var('SELECT FOUND_ROWS()');

		ob_start();
		if ( $rows ){
			foreach( $rows as $r ){
				$tok   = (string)($r->session_token ?? '');
				$short = ( $tok && preg_match('/^[a-f0-9]{40,64}$/i',$tok) ) ? substr($tok, 0, 12) . ( strlen($tok)>12 ? '…' : '' ) : '';
				$trigger = self::trigger_label( $r->end_trigger, $r->user_action );

				echo '<tr>';
				echo '<td>'.(int)$r->id.'</td>';
				echo '<td><a href="'.esc_url( network_admin_url('user-edit.php?user_id='.(int)$r->user_id) ).'">'.(int)$r->user_id.'</a></td>';
				echo '<td>'.esc_html( $r->display_name ?: '' ).'</td>';
				echo '<td>'.esc_html( $r->role_at_login ?: '' ).'</td>';
				echo '<td>'.esc_html( $r->ip_address ?: '' ).'</td>';
				echo '<td>'. ( $short ? '<code title="'.esc_attr($tok).'">'.esc_html($short).'</code>' : '&mdash;' ) .'</td>';
				echo '<td>'.esc_html( $r->login_time ?: '' ).'</td>';
				echo '<td>'.esc_html( $r->logout_time ?: '' ).'</td>';
				echo '<td>'.esc_html( $trigger ).'</td>';
				echo '<td>'. ( (int)$r->session_auto_ended ? 'Yes' : 'No' ) .'</td>';
				echo '<td>'.esc_html( $r->user_action ?: 'none' ).'</td>';
				echo '<td>'. ( (int)$r->is_online ? '<span class="aaa-ac-online">Online</span>' : '<span class="aaa-ac-offline">Offline</span>' ) .'</td>';
				echo '</tr>';
			}
		}else{
			echo '<tr><td colspan="12" style="text-align:center;color:#777;">No records.</td></tr>';
		}
		$html = ob_get_clean();

		$total_pages = $per ? (int)ceil($total / $per) : 1;

		wp_send_json_success([
			'rows'        => $html,
			'total'       => $total,
			'page'        => $page,
			'total_pages' => $total_pages,
		]);
	}

	public static function export(){
		if ( ! current_user_can('manage_network_users') ) wp_die('no_cap');
		check_ajax_referer('aaa_ac_ajax', 'nonce');

		global $wpdb;
		$table = self::table();
		$users = $wpdb->users;

		$role  = isset($_GET['role'])  ? sanitize_key($_GET['role']) : '';
		$start = isset($_GET['start']) ? self::sanitize_date($_GET['start']) : '';
		$end   = isset($_GET['end'])   ? self::sanitize_date($_GET['end'])   : '';

		$sort  = isset($_GET['sort'])  ? sanitize_key($_GET['sort']) : 'login_time';
		$dir   = (isset($_GET['dir']) && strtoupper($_GET['dir'])==='ASC') ? 'ASC' : 'DESC';
		$whitelist = ['id','login_time','logout_time','user_id','end_trigger','role_at_login','is_online','session_token'];
		if ( ! in_array($sort, $whitelist, true) ) $sort = 'login_time';

		list($where, $args) = self::where_sql_and_args($role, $start, $end);

		$sql = "
			SELECT l.*, u.display_name
			FROM   {$table} l
			LEFT JOIN {$users} u ON u.ID = l.user_id
			{$where}
			ORDER BY {$sort} {$dir}
			LIMIT 5000
		";
		$q = $wpdb->prepare($sql, $args);
		$rows = $wpdb->get_results( $q );

		nocache_headers();
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=aaa-ac-reports-'.gmdate('Ymd-His').'.csv');

		$out = fopen('php://output', 'w');
		fputcsv($out, ['id','user_id','display_name','role','ip','session_token','login_time','logout_time','trigger','auto','user_action','is_online']);
		if ( $rows ){
			foreach( $rows as $r ){
				$trigger = self::trigger_label( $r->end_trigger, $r->user_action );
				fputcsv($out, [
					(int)$r->id,
					(int)$r->user_id,
					(string)$r->display_name,
					(string)$r->role_at_login,
					(string)$r->ip_address,
					(string)$r->session_token,
					(string)$r->login_time,
					(string)$r->logout_time,
					(string)$trigger,
					(int)$r->session_auto_ended ? '1' : '0',
					(string)$r->user_action,
					(int)$r->is_online ? '1' : '0',
				]);
			}
		}
		fclose($out);
		exit;
	}
}
