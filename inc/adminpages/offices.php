<?php


function vtm_render_office_page(){


    $testListTable["office"] = new vtmclass_admin_office_table();
	$doaction = vtm_office_input_validation("office");
	
	/* echo "<p>action: $doaction</p>"; */
	
	if ($doaction == "add-office") {
		$testListTable["office"]->add();		
	}
	if ($doaction == "save-office") {
		$testListTable["office"]->edit();				
	}

	vtm_render_office_add_form("office", $doaction);
	$testListTable["office"]->prepare_items();
	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	$current_url = remove_query_arg( 'action', $current_url );
	?>	

	<form id="office-filter" method="get" action='<?php print htmlentities($current_url); ?>'>
		<input type="hidden" name="page" value="<?php print $_REQUEST['page'] ?>" />
		<input type="hidden" name="tab" value="office" />
 		<?php $testListTable["office"]->display() ?>
	</form>

    <?php 
}

function vtm_render_office_add_form($type, $addaction) {
	global $wpdb;

	$id   = isset($_REQUEST['office']) ? $_REQUEST['office'] : '';
		
	if ('fix-' . $type == $addaction) {
		$name          = $_REQUEST[$type . "_name"];
		$desc          = $_REQUEST[$type . "_desc"];
		$ordering      = $_REQUEST[$type . "_ordering"];
		$visible       = $_REQUEST[$type . "_visible"];
		
		$nextaction = $_REQUEST['action'];

	} elseif ('edit-' . $type == $addaction) {
		$sql = "SELECT * FROM " . VTM_TABLE_PREFIX . "OFFICE WHERE ID = %s";
		$sql = $wpdb->prepare($sql, $id);
		$data =$wpdb->get_row($sql);
		/* echo "<p>SQL: $sql</p>";
		print_r($data); */
		
		$name          = $data->NAME;
		$desc          = $data->DESCRIPTION;
		$ordering      = $data->ORDERING;
		$visible       = $data->VISIBLE;
		
		$nextaction = "save";

	} else {
	
		$name = "";
		$desc = "";
		$ordering = "";
		$visible  = 'Y';
		
		$nextaction = "add";
		
	}
		
	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	$current_url = remove_query_arg( 'action', $current_url );
	?>
	<form id="new-<?php print $type; ?>" method="post" action='<?php print htmlentities($current_url); ?>'>
		<input type="hidden" name="<?php print $type; ?>_id" value="<?php print $id; ?>"/>
		<input type="hidden" name="tab" value="<?php print $type; ?>" />
		<input type="hidden" name="action" value="<?php print $nextaction; ?>" />
		<table>
		<tr>
			<td>Name:</td>
			<td><input type="text" name="<?php print $type; ?>_name" value="<?php print vtm_formatOutput($name); ?>" size=30 /></td>
			<td>Ordering:</td>
			<td><input type="text" name="<?php print $type; ?>_ordering" value="<?php print $ordering; ?>" size=5 /></td>
			<td>Visible to Players:</td>
			<td>
				<select name="<?php print $type; ?>_visible">
					<option value="N" <?php selected($visible, "N"); ?>>No</option>
					<option value="Y" <?php selected($visible, "Y"); ?>>Yes</option>
				</select>
			</td>
		</tr>
		<tr>
			<td>Description:  </td>
			<td colspan=5><input type="text" name="<?php print $type; ?>_desc" value="<?php print vtm_formatOutput($desc); ?>" size=90 /></td> 
		</tr>
		</table>
		<input type="submit" name="save_<?php print $type; ?>" class="button-primary" value="Save" />
	</form>
	
	<?php

}

function vtm_office_input_validation($type) {
	
	$doaction = '';
	
	if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'edit' && $_REQUEST['tab'] == $type)
		$doaction = "edit-$type";

	if (!empty($_REQUEST[$type . '_name'])){
	
		$doaction = $_REQUEST['action'] . "-" . $type;
		
		if (empty($_REQUEST[$type . '_desc']) || $_REQUEST[$type . '_desc'] == "") {
			$doaction = "fix-$type";
			echo "<p style='color:red'>ERROR: Description is missing</p>";
		}
			
	}
	
	return $doaction;

}


/* 
-----------------------------------------------
ROAD/PATHS TABLE
------------------------------------------------ */


class vtmclass_admin_office_table extends vtmclass_MultiPage_ListTable {
   
    function __construct(){
        global $status, $page;
                
        parent::__construct( array(
            'singular'  => 'office',     
            'plural'    => 'offices',    
            'ajax'      => false        
        ) );
    }
 	function add() {
		global $wpdb;
		
		$wpdb->show_errors();
		
		$dataarray = array(
						'NAME'           => $_REQUEST['office_name'],
						'DESCRIPTION'    => $_REQUEST['office_desc'],
						'ORDERING'       => $_REQUEST['office_ordering'],
						'VISIBLE'        => $_REQUEST['office_visible']
					);
		
		/* print_r($dataarray); */
		
		$wpdb->insert(VTM_TABLE_PREFIX . "OFFICE",
					$dataarray,
					array (
						'%s',
						'%s',
						'%d',
						'%s'
					)
				);
		
		if ($wpdb->insert_id == 0) {
			echo "<p style='color:red'><b>Error:</b> " . vtm_formatOutput($_REQUEST['office_name']) . " could not be inserted (";
			$wpdb->print_error();
			echo ")</p>";
		} else {
			echo "<p style='color:green'>Added " . vtm_formatOutput($_REQUEST['office_name']) . "' (ID: {$wpdb->insert_id})</p>";
		}
	}

 	function edit() {
		global $wpdb;
		
		$wpdb->show_errors();
		
		$dataarray = array(
						'NAME'           => $_REQUEST['office_name'],
						'DESCRIPTION'    => $_REQUEST['office_desc'],
						'ORDERING'       => $_REQUEST['office_ordering'],
						'VISIBLE'        => $_REQUEST['office_visible']
					);
		
		$result = $wpdb->update(VTM_TABLE_PREFIX . "OFFICE",
					$dataarray,
					array (
						'ID' => $_REQUEST['office']
					)
				);
		
		if ($result) 
			echo "<p style='color:green'>Updated Office</p>";
		else if ($result === 0) 
			echo "<p style='color:orange'>No updates made</p>";
		else {
			$wpdb->print_error();
			echo "<p style='color:red'>Could not update Office ({$_REQUEST['office']})</p>";
		}
		 
	}
	
 	function delete($selectedID) {
		global $wpdb;
		
		/* Check if question in use */
		$sql = "select characters.NAME
				from 
					" . VTM_TABLE_PREFIX . "CHARACTER characters,
					" . VTM_TABLE_PREFIX . "CHARACTER_OFFICE charoffices,
					" . VTM_TABLE_PREFIX . "OFFICE offices
				where 
					characters.ID = charoffices.CHARACTER_ID 
					and offices.ID = charoffices.DOMAIN_ID
					and offices.ID = %d;";
					
		$isused = $wpdb->get_results($wpdb->prepare($sql, $selectedID));
		if ($isused) {
			echo "<p style='color:red'>Cannot delete as this office has been use for the following characters:";
			echo "<ul>";
			foreach ($isused as $item)
				echo "<li style='color:red'>" . vtm_formatOutput($item->NAME) . "</li>";
			echo "</ul></p>";
			return;
			
		} else {
		
			$sql = "delete from " . VTM_TABLE_PREFIX . "OFFICE where ID = %d;";
			
			$result = $wpdb->get_results($wpdb->prepare($sql, $selectedID));
		
			echo "<p style='color:green'>Deleted office $selectedID</p>";
		}
	}
  
    function column_default($item, $column_name){
        switch($column_name){
            case 'DESCRIPTION':
                return vtm_formatOutput($item->$column_name);
            case 'ORDERING':
                return $item->$column_name;
            default:
                return print_r($item,true); 
        }
    }
	
   function column_name($item){
        
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&amp;action=%s&amp;office=%s&amp;tab=%s">Edit</a>',$_REQUEST['page'],'edit',$item->ID, $this->type),
            'delete'    => sprintf('<a href="?page=%s&amp;action=%s&amp;office=%s&amp;tab=%s">Delete</a>',$_REQUEST['page'],'delete',$item->ID, $this->type),
       );
        
        
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            vtm_formatOutput($item->NAME),
            $item->ID,
            $this->row_actions($actions)
        );
    }
   

    function get_columns(){
        $columns = array(
            'cb'          => '<input type="checkbox" />', 
            'NAME'        => 'Name',
            'DESCRIPTION' => 'Description',
            'ORDERING'    => 'List Order',
            'VISIBLE'     => 'Visible to Players',
         );
        return $columns;
		
    }
    
    function get_sortable_columns() {
        $sortable_columns = array(
            'NAME'       => array('NAME',true),
            'ORDERING'   => array('ORDERING',false),
            'VISIBLE'    => array('VISIBLE',false)
        );
        return $sortable_columns;
    }
	
    
    function get_bulk_actions() {
        $actions = array(
            'delete'    => 'Delete'
       );
        return $actions;
    }
    
    function process_bulk_action() {
        if( 'delete'===$this->current_action() && $_REQUEST['tab'] == $this->type && isset($_REQUEST['office'])) {
			if ('string' == gettype($_REQUEST['office'])) {
				$this->delete($_REQUEST['office']);
			} else {
				foreach ($_REQUEST['office'] as $office) {
					$this->delete($office);
				}
			}
        }
        		
     }
	 
 
        
    function prepare_items() {
        global $wpdb; 
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
		
		$type = "office";
        			
		$this->_column_headers = array($columns, $hidden, $sortable);
        
		$this->type = $type;
        
        $this->process_bulk_action();
				
		/* Get the data from the database */
		$sql = "SELECT * FROM " . VTM_TABLE_PREFIX . "OFFICE offices";
							
		/* order the data according to sort columns */
		if (!empty($_REQUEST['orderby']) && !empty($_REQUEST['order']))
			$sql .= " ORDER BY {$_REQUEST['orderby']} {$_REQUEST['order']}";
				
		//echo "<p>SQL: $sql</p>";
		
		$data =$wpdb->get_results($sql);
        
        $current_page = $this->get_pagenum();
        $total_items = count($data);
        
        $this->items = $data;
        
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  
            'per_page'    => $total_items,                  
            'total_pages' => 1
        ) );
    }

}
?>