<?php
/**
 * File: /wp-content/plugins/aaa-admin-controler-v2/ajax/class-ac-popup-reports.php
 * Purpose: AJAX for Popup Reports tab (reads aaa_ac_popup_logs)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class AC_Popup_Reports_Ajax {

	public static function init(){
		add_action('wp_ajax_aaa_ac_popup_reports_load',   [__CLASS__, 'load']);
		add_action('wp_ajax_aaa_ac_popup_reports_export', [__CLASS__, 'export']);
	}

	/** Popup logs table name */
	protected static function table(){
		global $wpdb;
		return $wpdb->base_prefix . ( defined('AAA_AC_TABLE_PREFIX') ? AAA_AC_TABLE_PREFIX : 'aaa_ac_' ) . 'popup_logs';
	}

	/** Sanitize YYYY-MM-DD */
	protected static function sanitize_date($d){
		$d = trim((string)$d);
		return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) ? $d : '';
	}

	/**
	 * Role filter:
	 * We don't store role in popup logs table, so to filter by role we look at usermeta capabilities.
	 * Use a broad match on any blog's *_capabilities (works across network).
	 */
	protected static function where_and_args_for_filters( $role, $start, $end, $action ){
		global $wpdb;
		$where = ' WHERE 1=1 ';
		$args  = [];

		if ( $role ){
			$where .= " AND u.ID IN (
				SELECT user_id FROM {$wpdb->usermeta}
				WHERE meta_key LIKE %s AND meta_value LIKE %s
			) ";
			$args[] = '%_capabilities';
			$args[] = '%"'. $wpdb->_real_escape( $role ) .'"%'; // role slug appears JSON-encoded in caps array
		}

		if ( $start ){
			$where .= ' AND l.shown_at >= %s ';
			$args[] = $start . ' 00:00:00';
		}
		if ( $end ){
			$where .= ' AND l.shown_at <= %s ';
			$args[] = $end . ' 23:59:59';
		}
		if ( $action ){
			$where .= ' AND l.action = %s ';
			$args[] = $action;
		}

		return [$where, $args];
	}

	public static function load(){
		if ( ! current_user_can('manage_network_users') ) wp_send_json_error('no_cap');
		check_ajax_referer('aaa_ac_ajax', 'nonce');

		global $wpdb;
		$table = self::table();
		$users = $wpdb->users;

		$role   = isset($_GET['role'])   ? sanitize_key($_GET['role']) : '';
		$start  = isset($_GET['start'])  ? self::sanitize_date($_GET['start']) : '';
		$end    = isset($_GET['end'])    ? self::sanitize_date($_GET['end'])   : '';
		$action = isset($_GET['act'])    ? sanitize_key($_GET['act'])   : ''; // 'shown' | 'confirmed' | 'switch' | ''

		// Sorting / paging
		$sort  = isset($_GET['sort']) ? sanitize_key($_GET['sort']) : 'id';
		$dir   = (isset($_GET['dir']) && strtoupper($_GET['dir'])==='ASC') ? 'ASC' : 'DESC';
		$whitelist = ['id','user_id','shown_at','handled_at','action','site_id','due_hhmm'];
		if ( ! in_array($sort, $whitelist, true) ) $sort = 'id';

		$page = max(1, (int)($_GET['page'] ?? 1));
		$per  = min( max(10, (int)($_GET['per'] ?? 50)), 200);
		$off  = ($page - 1) * $per;

		list($where, $args) = self::where_and_args_for_filters($role, $start, $end, $action);

		$sql = "
			SELECT SQL_CALC_FOUND_ROWS
			       l.*, u.display_name
			FROM   {$table} l
			LEFT JOIN {$users} u ON u.ID = l.user_id
			{$where}
			ORDER BY {$sort} {$dir}
			LIMIT %d OFFSET %d
		";
		$q = $wpdb->prepare( $sql, array_merge($args, [ $per, $off ]) );
		$rows  = $wpdb->get_results( $q );
		$total = (int) $wpdb->get_var('SELECT FOUND_ROWS()');

		// Render rows
		ob_start();
		if ( $rows ){
			foreach( $rows as $r ){
				// Build a role string (live) from user object since logs table doesn't keep role snapshot
				$role_str = '';
				$uobj = get_userdata( (int)$r->user_id );
				if ( $uobj && is_array($uobj->roles) && ! empty($uobj->roles) ){
					$role_str = implode(',', $uobj->roles);
				}

				$tok   = (string)($r->session_token ?? '');
				$short = ( $tok && preg_match('/^[a-f0-9]{40,64}$/i',$tok) ) ? substr($tok,0,12).(strlen($tok)>12?'…':'') : '';

				echo '<tr>';
				echo '<td>'.(int)$r->id.'</td>';
				echo '<td><a href="'.esc_url( network_admin_url('user-edit.php?user_id='.(int)$r->user_id) ).'">'.(int)$r->user_id.'</a></td>';
				echo '<td>'.esc_html( $r->display_name ?: '' ).'</td>';
				echo '<td>'.esc_html( $role_str ).'</td>';
				echo '<td>'.esc_html( $r->due_hhmm ).'</td>';
				echo '<td>'.esc_html( $r->shown_at ).'</td>';
				echo '<td>'.esc_html( $r->action ).'</td>';
				echo '<td>'.esc_html( $r->handled_at ).'</td>';
				echo '<td>'. ( $short ? '<code title="'.esc_attr($tok).'">'.esc_html($short).'</code>' : '&mdash;' ) .'</td>';
				echo '<td>'.(int)$r->site_id.'</td>';
				echo '<td>'.esc_html( $r->admin_page ).'</td>';
				echo '</tr>';
			}
		}else{
			echo '<tr><td colspan="11" style="text-align:center;color:#777;">No records.</td></tr>';
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

		$role   = isset($_GET['role'])   ? sanitize_key($_GET['role']) : '';
		$start  = isset($_GET['start'])  ? self::sanitize_date($_GET['start']) : '';
		$end    = isset($_GET['end'])    ? self::sanitize_date($_GET['end'])   : '';
		$action = isset($_GET['act'])    ? sanitize_key($_GET['act'])   : '';

		list($where, $args) = self::where_and_args_for_filters($role, $start, $end, $action);

		$sql = "
			SELECT l.*, u.display_name
			FROM   {$table} l
			LEFT JOIN {$users} u ON u.ID = l.user_id
			{$where}
			ORDER BY l.id DESC
			LIMIT 5000
		";
		$q = $wpdb->prepare($sql, $args);
		$rows = $wpdb->get_results( $q );

		nocache_headers();
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=aaa-ac-popup-reports-'.gmdate('Ymd-His').'.csv');

		$out = fopen('php://output', 'w');
		fputcsv($out, ['id','user_id','display_name','roles','due_hhmm','shown_at','action','handled_at','session_token','site_id','admin_page']);
		if ( $rows ){
			foreach( $rows as $r ){
				$role_str = '';
				$uobj = get_userdata( (int)$r->user_id );
				if ( $uobj && is_array($uobj->roles) && ! empty($uobj->roles) ){
					$role_str = implode(',', $uobj->roles);
				}
				fputcsv($out, [
					(int)$r->id,
					(int)$r->user_id,
					(string)$r->display_name,
					(string)$role_str,
					(string)$r->due_hhmm,
					(string)$r->shown_at,
					(string)$r->action,
					(string)$r->handled_at,
					(string)$r->session_token,
					(int)$r->site_id,
					(string)$r->admin_page,
				]);
			}
		}
		fclose($out);
		exit;
	}
}
