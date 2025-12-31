<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
/**
 * The ajax functionality of the plugin.
 *
 * @link       https://wordpress.org/plugins/wb-sticky-notes
 * @since      1.0.0
 *
 * @package    Wb_Sticky_Notes
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wb_Sticky_Notes
 * @author     Web Builder 143 
 */
class Wb_Sticky_Notes_Ajax {

	private $plugin_name;

	private $version;
	private $notes_tb = '';

	/**
	 * Initiate ajax class
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->notes_tb=Wb_Sticky_Notes::$notes_tb;
	}

	/**
	 * Main method to handle all ajax requests
	 *
	 * @since    1.0.0
	 */
	public function ajax_main()
	{
		$out=array(
			'response'=>false,
			'message'=>__('Unable to handle your request.', 'wb-sticky-notes'),
		);
		$nonce=isset($_POST['security']) && is_string($_POST['security']) ? sanitize_text_field($_POST['security']) : '';
		$non_json_response=array();
		$wb_stn_action=is_string($_POST['wb_stn_action']) ? sanitize_text_field($_POST['wb_stn_action']) : '';
		
		if(wp_verify_nonce($nonce,WB_STICKY_PLUGIN_NAME))
		{
			if(isset($_POST['wb_stn_action']))
			{
				$allowed_actions=array('get_notes','save_note','delete_note','set_zindex','toggle_notes','create_note', 'get_archives', 'toggle_archive');		
				if(in_array($wb_stn_action,$allowed_actions) && method_exists($this,$wb_stn_action))
				{ 
					$out=$this->{$wb_stn_action}();
				}
			}
		}
		if(in_array($wb_stn_action,$non_json_response))
		{
			echo (is_array($out) ? $out['message'] : $out);
		}else
		{
			echo json_encode($out);
		}		
		exit();
	}

	/**
	 * Method to create new note (Ajax sub)
	 *
	 * @since    1.0.0
	 */
	private function create_note()
	{
		global $wpdb;
		$out=array(
			'response'=>false,
			'er'=>__('Error', 'wb-sticky-notes'),
			'data'=>'',
			'id_wb_stn_notes'=>0,
		);
		$settings=Wb_Sticky_Notes::get_settings();
		$table_name=$wpdb->prefix.$this->notes_tb;
		$post_data=$this->preparePostData($settings);
		
        // Always save the creator's user ID so ownership is retained. Notes
        // created as global will still store the creator here and use the
        // separate `is_global` flag for visibility.
        $post_data['post_data']['id_user'] = get_current_user_id();
        $post_data['post_data_format'][]   = '%d';

        // Determine whether this note should be visible to all users. The
        // front‑end should send a `global_note` flag (1 or 0). If absent or
        // falsy it defaults to a private note. Global notes are marked by
        // setting the `is_global` column to 1.
        $is_global = ( isset( $_POST['global_note'] ) && absint( $_POST['global_note'] ) === 1 ) ? 1 : 0;
        $post_data['post_data']['is_global'] = $is_global;
        $post_data['post_data_format'][]     = '%d';

        $post_data['post_data']['state'] = 1;
        $post_data['post_data_format'][] = '%d';
        $post_data['post_data']['status'] = Wb_Sticky_Notes::$status['active'];
        $post_data['post_data_format'][] = '%d';

		$result=$wpdb->insert($table_name,$post_data['post_data'],$post_data['post_data_format']);
		if($result!==false){
			$out['response']=true;
			$out['er']=__('Success', 'wb-sticky-notes');
			$out['id_wb_stn_notes']=$wpdb->insert_id;
		}
		return $out;
	}

	/**
	 * Hide/Show notes
	 *
	 * @since    1.0.0
	 * @since    1.1.1 Status changing will be applicable for notes with all statuses
	 */
	private function toggle_notes()
	{
		global $wpdb;
		$out=array(
			'response'=>false,
			'er'=>__('Error', 'wb-sticky-notes'),
			'data'=>'',
		);
		$table_name=$wpdb->prefix.$this->notes_tb;
		$id=$this->get_noteid_input();
		$id_user=get_current_user_id();
		$status_active=Wb_Sticky_Notes::$status['active'];
		$state=(isset($_POST['state']) ? intval($_POST['state']) : 0);
		$where=array('id_user'=>$id_user);
		$where_format=array('%d');
		if($id>0)
		{
			$where['id_wb_stn_notes']=$id;
			$where_format[]='%d';
		}
		$result=$wpdb->update(
			$table_name,
			array('state'=>$state),
			$where,
			array('%d'),
			$where_format
		);
		if($result!==false){
			$out['response']=true;
			$out['er']=__('Success', 'wb-sticky-notes');
		}
		return $out;
	}

	/**
	 * Set z-index to notes. Active not has heigher value
	 *
	 * @since    1.0.0
	 */
	private function set_zindex()
	{
		global $wpdb;
		$out=array(
			'response'=>false,
			'er'=>__('Error', 'wb-sticky-notes'),
			'data'=>'',
		);
		$note_data=(isset($_POST['note_data']) ? $this->validate_note_data($_POST['note_data']) : array());
		if(is_array($note_data) && count($note_data)>0)
		{
			$table_name=$wpdb->prefix.$this->notes_tb;
			$id_user=get_current_user_id();			
            foreach ( $note_data as $id => $value ) {
                if ( $id > 0 ) {
                    // Determine whether the note is global. For global notes we
                    // don't filter by user when updating the z-index.
                    $note_row = $wpdb->get_row( $wpdb->prepare( "SELECT is_global FROM $table_name WHERE id_wb_stn_notes = %d", $id ), ARRAY_A );
                    $where    = array( 'id_wb_stn_notes' => $id );
                    $where_f  = array( '%d' );
                    if ( empty( $note_row['is_global'] ) ) {
                        $where['id_user'] = $id_user;
                        $where_f[]        = '%d';
                    }
                    $wpdb->update( $table_name, array( 'z_index' => $value ), $where, array( '%d' ), $where_f );
                }
            }
            $out['response'] = true;
            $out['er']       = __( 'Success', 'wb-sticky-notes' );
		}
		return $out;
	}

	/**
	* Validate the note data, only accept integers
	*
	*/
	private function validate_note_data($note_data)
	{
		$out=array();
		foreach($note_data as $id=>$value) 
		{
			$id=intval($id); 
			$value=intval($value);
			$out[$id]=$value;
		}
		return $out;
	}

	/**
	 * Update note content
	 *
	 * @since    1.0.0
	 */
	private function save_note()
	{
		global $wpdb;
		$out=array(
			'response'=>false,
			'er'=>__('Error', 'wb-sticky-notes'),
			'data'=>'',
		);
        $id = $this->get_noteid_input();
        if ( $id > 0 ) {
            $settings    = Wb_Sticky_Notes::get_settings();
            $table_name = $wpdb->prefix . $this->notes_tb;
            $id_user    = get_current_user_id();
            $post_data  = $this->preparePostData( $settings );

            // Determine if the note is global. Global notes can be edited by any
            // authorised user, so we do not filter by id_user in that case.
            $note_row = $wpdb->get_row( $wpdb->prepare( "SELECT is_global, id_user FROM $table_name WHERE id_wb_stn_notes = %d", $id ), ARRAY_A );
            if ( ! empty( $note_row ) ) {
                $where        = array( 'id_wb_stn_notes' => $id );
                $where_format = array( '%d' );
                if ( empty( $note_row['is_global'] ) ) {
                    // Private notes remain editable only by their owner.
                    $where['id_user']    = $id_user;
                    $where_format[]      = '%d';
                }
                $result = $wpdb->update( $table_name, $post_data['post_data'], $where, $post_data['post_data_format'], $where_format );
                if ( false !== $result ) {
                    $out['response'] = true;
                    $out['er']       = __( 'Success', 'wb-sticky-notes' );
                }
            }
        }
        return $out;
	}

	/**
	 * Process POST data values
	 *
	 * @since    1.0.0
	 */
	private function preparePostData($settings)
	{
		$out=array();
		$out_format=array();
		$table_cols=array('content','status','state','theme','font_size','font_family','width','height','postop','posleft','z_index');
		$table_cols_format=array('content'=>'%s','status'=>'%d','state'=>'%d','theme'=>'%d','font_size'=>'%d','font_family'=>'%d','width'=>'%d','height'=>'%d','postop'=>'%d','posleft'=>'%d','z_index'=>'%d');
		$cols_need_formating=array('theme','font_family');
		foreach($_POST as $key=>$val)
		{
			$key=(is_string($key) ? sanitize_key($key) : '');
			if(in_array($key,$table_cols))
			{
				$val=((is_numeric($val) || is_string($val)) ?  esc_html($val) : '');
				if(in_array($key,$cols_need_formating))
				{
					if($key=='theme')
					{ 
						if(strpos($val,'wb_stn_')!==false)
						{
							$val=str_replace('wb_stn_','',$val);
							$val=(in_array($val,Wb_Sticky_Notes::$themes) ? array_search($val,Wb_Sticky_Notes::$themes) : $settings['theme']);
						}else
						{
							$val=$settings['theme'];
						}
					}
					elseif($key=='font_family') 
					{
						if(strpos($val,'wb_stn_font_')!==false)
						{
							$val=str_replace('wb_stn_font_','',$val);
							$val=(in_array($val,Wb_Sticky_Notes::$fonts) ? array_search($val,Wb_Sticky_Notes::$fonts) : $settings['font_family']);
						}else
						{
							$val=$settings['font_family'];
						}
					}else
					{
						$val=str_replace('wb_stn_','',$val);
					}
				}
				$out[$key]=$val;
				$out_format[]=$table_cols_format[$key];
			}
		}
		return array(
			'post_data'=>$out,
			'post_data_format'=>$out_format,
		);
	}

	/**
	 * Delete note
	 *
	 * @since    1.0.0
	 */
	private function delete_note()
	{
		global $wpdb;
		$out=array(
			'response'=>false,
			'er'=>__('Error', 'wb-sticky-notes'),
			'data'=>'',
		);
		$id=$this->get_noteid_input();
		if($id>0)
		{
			$table_name=$wpdb->prefix.$this->notes_tb;
			$id_user=get_current_user_id();
			$result=$wpdb->delete($table_name,array('id_user'=>$id_user,'id_wb_stn_notes'=>$id),array('%d','%d'));
			if($result!==false){
				$out['response']=true;
				$out['er']=__('Success', 'wb-sticky-notes');
			}
		}
		return $out;
	}

	/**
	 * Process note id POST input
	 *
	 * @since    1.0.0
	 */
	private function get_noteid_input()
	{
		//only accept integer values
		return (isset($_POST['id_wb_stn_notes']) ? intval($_POST['id_wb_stn_notes']) : 0);
	}

	/**
	 * Generate note option dropdown menu HTML
	 *
	 * @since    1.0.0
	 */
	private function getSingleNoteDropDownMenu($settings)
	{
		ob_start();	
		include WB_STN_PLUGIN_PATH.'admin/partials/_single_dropdown_menu.php';
		return ob_get_clean();
	}

	/**
	 * Generate note HTML
	 *
	 * @since    1.0.0
	 */
	private function getNoteHTML($theme_data,$settings,$note_dropdown_menu_html)
	{
		ob_start();	
		include WB_STN_PLUGIN_PATH.'admin/partials/wb-sticky-notes-single.php';
		return ob_get_clean();
	}

	/**
	 * Prepare note HTML with settings from DB
	 *
	 * @since    1.0.0
	 */
	private function prepareNoteHTML($data_arr)
	{
		$settings=Wb_Sticky_Notes::get_settings();
		$note_dropdown_menu_html=$this->getSingleNoteDropDownMenu($settings);
		$html=$this->getNoteHTML(null,$settings,$note_dropdown_menu_html); //dummy HTML
		$z_index='';
		foreach($data_arr as $key =>$theme_data)
		{
			if($z_index=="")
			{
				$z_index=$theme_data['z_index'];
			}else
			{
				$z_index++;
				$theme_data['z_index']=$z_index;
			}
			$html.=$this->getNoteHTML($theme_data,$settings,$note_dropdown_menu_html);
		}
		return $html;
	}

	/**
	 * Get all notes of the current user
	 *
	 * @since    1.0.0
	 * @since    1.1.1 Single note HTML returning option added. Using in toggle archive functionality.
	 */
	private function get_notes()
	{
		global $wpdb;
		$out=array(
			'response'=>true,
			'er'=>'',
			'data'=>'',
		);
		$settings=Wb_Sticky_Notes::get_settings();
		if($settings['enable']!=1) /* not enabled */
		{
			return $out;
		}

        $table_name    = $wpdb->prefix . $this->notes_tb;
        $id_user       = get_current_user_id();
        $status_active = Wb_Sticky_Notes::$status['active'];
        // Always require a logged‑in user to load notes. If no user is
        // authenticated the notes cannot be fetched and an error is
        // returned.
        if ( $id_user > 0 ) {
            $id = $this->get_noteid_input();

            // Build a query that returns notes that are either owned by the
            // current user or marked global (`is_global` = 1). This ensures
            // private notes remain visible only to their owner while global
            // notes are shown to everyone. Only notes with status = active
            // are retrieved.
            $sql        = "SELECT * FROM $table_name WHERE status=%d AND (id_user=%d OR is_global=1)";
            $sql_data   = array( $status_active, $id_user );

            // If a specific note ID is requested, add that filter.
            if ( $id > 0 ) {
                $sql        .= " AND id_wb_stn_notes=%d";
                $sql_data[] = $id;
            }

            $sql      .= " ORDER BY z_index,id_wb_stn_notes";
            $qry       = $wpdb->prepare( $sql, $sql_data );
            $results   = $wpdb->get_results( $qry, ARRAY_A );
            $out['data'] = $this->prepareNoteHTML( $results );
        } else {
            $out = array(
                'response' => false,
                'er'       => __( 'Error', 'wb-sticky-notes' ),
                'data'     => '',
            );
        }

        return $out;
	}

	/**
	 * Get archives of the current user (Ajax callback)
	 *
	 * @since     1.1.1
	 */
	public function get_archives()
	{
		$out=array(
			'response'=>true,
			'er'=>'',
			'data'=>'',
		);

		$offset = isset($_POST['wb_stn_offset']) ? absint($_POST['wb_stn_offset']) : 0;
		$limit = 12;
		$archives = $this->get_user_archives($offset, $limit);		
		$settings=Wb_Sticky_Notes::get_settings();
		
		ob_start();	
		include WB_STN_PLUGIN_PATH.'admin/partials/_archives_list.php';
		$out['data'] = ob_get_clean();

		return $out;
	}

	/**
	 * Get archives of the current user from DB
	 *
	 * @since     1.1.1
	 * @param    	$offset 	int 	Offset
	 * @param    	$limit 		int 	Max items
	 * @return    	array of archives data
	 */
	private function get_user_archives($offset, $limit)
	{
		global $wpdb;

		$table_name=$wpdb->prefix.Wb_Sticky_Notes::$notes_tb;
		$id_user=get_current_user_id();
		$status_archive=Wb_Sticky_Notes::$status['archive'];
		$archives = array();

		if($id_user>0) //logged in
		{
			$qry = $wpdb->prepare("SELECT * FROM $table_name WHERE id_user=%d AND status=%d ORDER BY id_wb_stn_notes DESC LIMIT %d, %d", array($id_user, $status_archive, $offset, $limit));
			$archives = $wpdb->get_results($qry,ARRAY_A);
		}

		return $archives;
	}

	/**
	 * Archive/Unarchive the current note (Ajax callback)
	 *
	 * @since     1.1.1
	 */
	public function toggle_archive()
	{
		global $wpdb;
		$out=array(
			'response'=>false,
			'er'=>__('Error', 'wb-sticky-notes'),
			'data'=>'',
		);
		$id=$this->get_noteid_input();
		if($id>0)
		{
			$settings=Wb_Sticky_Notes::get_settings();
			$table_name=$wpdb->prefix.$this->notes_tb;
			$id_user=get_current_user_id();
			$post_data=$this->preparePostData($settings);
			$result=$wpdb->update(
				$table_name,
				$post_data['post_data'],
				array('id_user'=>$id_user,'id_wb_stn_notes'=>$id),
				$post_data['post_data_format'],
				array('%d','%d')
			);

			if($result!==false)
			{
				$out['response']=true;
				$out['er']=__('Success', 'wb-sticky-notes');

				if(isset($post_data['post_data']['status']) && 1 === absint($post_data['post_data']['status']))
				{
					$note_data = $this->get_notes();
					$out['data'] = $note_data['data'];
				}
			}
		}
		return $out;
	}

}