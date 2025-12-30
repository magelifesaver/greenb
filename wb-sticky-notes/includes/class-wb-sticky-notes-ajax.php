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
		
		// Check if a "global_note" flag was sent. If so, store 0 as the owner.
		$is_global = (isset($_POST['global_note']) && absint($_POST['global_note']) === 1);
		$post_data['post_data']['id_user'] = $is_global ? 0 : get_current_user_id();
		$post_data['post_data_format'][] = '%d';
		$post_data['post_data']['state']=1;
		$post_data['post_data_format'][]='%d';
		$post_data['post_data']['status']=Wb_Sticky_Notes::$status['active'];
		$post_data['post_data_format'][]='%d';

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
			foreach($note_data as $id=>$value) 
			{
				//only accept integer value
				//$id=(int) $id; 
				//$value=(int) $value;
				if($id>0)
				{
					$result=$wpdb->update(
						$table_name,
						array('z_index'=>$value),
						array('id_user'=>$id_user,'id_wb_stn_notes'=>$id),
						array('%d'),
						array('%d','%d')
					);
				}
			}
			$out['response']=true;
			$out['er']=__('Success', 'wb-sticky-notes');
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
			if($result!==false){
				$out['response']=true;
				$out['er']=__('Success', 'wb-sticky-notes');
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
     * Get notes to display in the dashboard.
     *
     * By default the original plugin only loaded notes belonging to the
     * current user (filtered by the `id_user` field). This method has
     * been updated to return all active notes regardless of owner so
     * they appear as a shared notice for every authorised user. When a
     * specific note ID is passed it still limits the query to that
     * individual note. The create/edit/delete actions continue to use
     * the current user’s ID for ownership and permission checks.
     *
     * @since    1.0.0
     * @since    1.1.1 Single note HTML returning option added. Using in toggle archive functionality.
     * @modified 1.2.5  Global visibility – fetch all notes instead of filtering by user.
     */
    private function get_notes()
    {
        global $wpdb;
        $out = array(
            'response' => true,
            'er'       => '',
            'data'     => '',
        );

        $settings = Wb_Sticky_Notes::get_settings();
        // Bail early if the module is disabled.
        if ( $settings['enable'] != 1 ) {
            return $out;
        }

$table_name    = $wpdb->prefix . $this->notes_tb;
$status_active = Wb_Sticky_Notes::$status['active'];

$current_user = get_current_user_id();
$id = $this->get_noteid_input();

// Build a query that fetches notes where:
// - status = active
// - AND (id_user = current user OR id_user = 0)
$sql        = "SELECT * FROM $table_name WHERE status=%d AND (id_user=%d OR id_user=0)";
$sql_params = array($status_active, $current_user);

// If a specific note ID is requested, add that filter too.
if ($id > 0) {
    $sql        .= " AND id_wb_stn_notes=%d";
    $sql_params[] = $id;
}

$sql .= " ORDER BY z_index,id_wb_stn_notes";

// Execute the SQL query with the parameters
$qry     = $wpdb->prepare($sql, $sql_params);
$results = $wpdb->get_results($qry, ARRAY_A);

// Return the HTML rendering of all notes (private + global)
$out['data'] = $this->prepareNoteHTML($results);
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