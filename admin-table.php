<?php
require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

/*
 * WP-List-Tables
 */

/*
 * Instructions
 */

class Instructions_List_Table extends WP_List_Table {
  function __construct(){
    global $status, $page;

    //Set parent defaults
    parent::__construct( array(
        'singular'  => 'instruction',     //singular name of the listed records
        'plural'    => 'instructions',    //plural name of the listed records
        'ajax'      => false        //does this table support ajax?
    ) );  
  }
  
  function column_default($item, $column_name){
    switch($column_name){
        case 'description':
            return $item[$column_name];
        default:
            return print_r($item,true); //Show the whole array for troubleshooting purposes
    }
  }
  
  
  // Handles printing cell for instructions title 
  function column_title($item){
        
    //Build row actions
    $actions = array(
        'edit'      => sprintf('<a href="?page=%s&action=edit_instruction&id=%s">Edit</a>',$_REQUEST['page'],$item['id']),
        'delete'    => sprintf('<a href="?page=%s&action=delete_instruction&id=%s">Delete</a>',$_REQUEST['page'],$item['id'])
    );
    
    // Depending on if post is set, add link for Add or Edit post
    if (empty($item['post']) || !is_post($item['post']))
      $actions['post'] = '<a href="?page='.$_REQUEST['page'].'&action=add_post&id='.$item['id'].'">Add Post</a>';
    else 
      $actions['post'] = '<a href="'.get_admin_url().'post.php?post='.$item['post'].'&action=edit">Edit Post</a>';

    //Return the title contents
    return sprintf('%1$s <span style="color:silver">[instructions id=%2$s]</span>%3$s',
        /*$1%s*/ $item['title'],
        /*$2%s*/ $item['id'],
        /*$3%s*/ $this->row_actions($actions)
    );
  }
  
  function get_columns(){
    $columns = array(
        /*'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text*/
        'title'     => 'Title',
        'description' => 'Description'
    );
    return $columns;
  }
  
  function get_sortable_columns() {
    $sortable_columns = array(
        'title'     => array('title',true),     //true means its already sorted
        'description' => array('description',false)
    );
    return $sortable_columns;
  }
  
  function prepare_items() {
    // WordPress globals
    global $wpdb;
    // diy instructions globals
    global $diy_step_table, $diy_instruct_table, $diy_progress_table;
        
    /**
     * First, lets decide how many records per page to show
     */
    $per_page = 5;


    /**
     * REQUIRED. Now we need to define our column headers. This includes a complete
     * array of columns to be displayed (slugs & titles), a list of columns
     * to keep hidden, and a list of columns that are sortable. Each of these
     * can be defined in another method (as we've done here) before being
     * used to build the value for our _column_headers property.
     */
    $columns = $this->get_columns();
    $hidden = array();
    $sortable = $this->get_sortable_columns();


    /**
     * REQUIRED. Finally, we build an array to be used by the class for column 
     * headers. The $this->_column_headers property takes an array which contains
     * 3 other arrays. One for all columns, one for hidden columns, and one
     * for sortable columns.
     */
    $this->_column_headers = array($columns, $hidden, $sortable);

    //get instructions title and description
    $data =  $wpdb->get_results("SELECT id, title, description, post FROM ".$diy_instruct_table. " ORDER BY title", ARRAY_A);

    /**
     * REQUIRED for pagination. Let's figure out what page the user is currently 
     * looking at. We'll need this later, so you should always include it in 
     * your own package classes.
     */
    $current_page = $this->get_pagenum();

    /**
     * REQUIRED for pagination. Let's check how many items are in our data array. 
     * In real-world use, this would be the total number of items in your database, 
     * without filtering. We'll need this later, so you should always include it 
     * in your own package classes.
     */
    $total_items = count($data);


    /**
     * The WP_List_Table class does not handle pagination for us, so we need
     * to ensure that the data is trimmed to only the current page. We can use
     * array_slice() to 
     */
    $data = array_slice($data,(($current_page-1)*$per_page),$per_page);


    /**
     * REQUIRED. Now we can add our *sorted* data to the items property, where 
     * it can be used by the rest of the class.
     */
    $this->items = $data;


    /**
     * REQUIRED. We also have to register our pagination options & calculations.
     */
    $this->set_pagination_args( array(
        'total_items' => $total_items,                  //WE have to calculate the total number of items
        'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
        'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
    ) );
  }
}


class Steps_List_Table extends WP_List_Table {
  private $instruction_id;
  private $number_of_steps;
  
  function __construct($instruction_id = null){
    global $status, $page;

    $this->instruction_id = $instruction_id;
    
    //Set parent defaults
    parent::__construct( array(
        'singular'  => 'step',     //singular name of the listed records
        'plural'    => 'steps',    //plural name of the listed records
        'ajax'      => false        //does this table support ajax?
    ) );  
  }
  
  // Returns column conten for all columns without function column_<name>
  function column_default($item, $column_name){
    switch($column_name){
        default:
            return print_r($item,true); //Show the whole array for troubleshooting purposes
    }
  }
  
  // Handles printing cell for instructions number
  function column_number($item){
    $html = "<input type='hidden' name='oldnumber".$item['id']."' value='".$item['number']."'><br/>";
    
    $html .= '<select name="number'.$item['id'].'" onchange="this.form.update.value=\'Update Number\';this.form.update.click()">';
    for($step_num = 1; $step_num <= $this->number_of_steps; $step_num++){
      if($step_num == $item['number']) $html .= '<option value="'.$step_num.'" selected>'.$step_num."\n";
      else $html .= '<option value="'.$step_num.'">'.$step_num."\n";
    }
    $html .= '</select>';
    $html .= '<a name="stepanchor'.$item['id'].'"></a>';
    $html .= '<a href="?page='.$_REQUEST['page'].'&action=delete_step&id='.$this->instruction_id.'&step='.$item['id'].'">Delete</a><br/>';
    return $html;
  }
  
  // Handles printing cell for instructions title 
  function column_text($item){
    // text field for entering
    //$html = '<textarea name="text:'.$item['id'].'">'.$item['text'].'</textarea>';
    $html = wp_editor($item['text'], 'text'.$item['id'], array('textarea_rows' => 5));
    return $html;
  }
  
  // Handles picture 
  function column_picture($item){
    ?>
    <img class="diy-admin-img" id="img<?php echo $item['id'];?>" src="<?php echo $item['picture']; ?>" /><br/>
    <input id="picture<?php echo $item['id']; ?>" type="hidden" name="picture<?php echo $item['id']; ?>" value="<?php echo $item['picture']; ?>" />
    <input id="picture_button<?php echo $item['id']; ?>" type="button" value="Set Image" style="display: none"/>
    <input id="picture_button_clear<?php echo $item['id']; ?>" type="button" value="Clear Image" style="display: none"/>

    <script language="javascript">
    jQuery(document).ready(function() {
      
      if(jQuery('#picture<?php echo $item['id']; ?>').val()){
        jQuery('#picture_button_clear<?php echo $item['id']; ?>').show();
        jQuery('#picture_button<?php echo $item['id']; ?>').hide();
      } else {
        jQuery('#picture_button_clear<?php echo $item['id']; ?>').hide();
        jQuery('#picture_button<?php echo $item['id']; ?>').show();
      }

      jQuery('#picture_button<?php echo $item['id']; ?>').click(function() {
       formfield = jQuery('#picture<?php echo $item['id']; ?>').attr('name');
       imgfield = jQuery('#img<?php echo $item['id']; ?>').attr('id');
       setbutton = jQuery('#picture_button<?php echo $item['id']; ?>').attr('id')
       clearbutton = jQuery('#picture_button_clear<?php echo $item['id']; ?>').attr('id')
       tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');
       return false;
      });
      
      jQuery('#picture_button_clear<?php echo $item['id']; ?>').click(function() {
       jQuery('#picture<?php echo $item['id']; ?>').val('');
       jQuery('#img<?php echo $item['id']; ?>').hide();
       jQuery('#picture_button_clear<?php echo $item['id']; ?>').hide();
       jQuery('#picture_button<?php echo $item['id']; ?>').show();
       return false;
      });

      window.send_to_editor = function(html) {
       imgurl = jQuery('img',html).attr('src');
       jQuery('#'+formfield).val(imgurl);
       jQuery('#'+imgfield).attr('src', imgurl);
       jQuery('#'+imgfield).show();
       jQuery('#'+clearbutton).show();
       jQuery('#'+setbutton).hide();
       tb_remove();
      }

    });
    </script>
    <?
  }
  // Handles picture 
  function column_thumbnail($item){
    ?>
    <img class="diy-admin-img" id="tbimg<?php echo $item['id'];?>" src="<?php echo $item['thumbnail']; ?>" /><br/>
    <input id="thumbnail<?php echo $item['id']; ?>" type="hidden" name="thumbnail<?php echo $item['id']; ?>" value="<?php echo $item['thumbnail']; ?>" />
    <input id="tbpicture_button<?php echo $item['id']; ?>" type="button" value="Set Thumbnail" style="display: none"/>
    <input id="tbpicture_button_clear<?php echo $item['id']; ?>" type="button" value="Clear Thumbnail" style="display: none"/>

    <script language="javascript">
    jQuery(document).ready(function() {
      
      if(jQuery('#thumbnail<?php echo $item['id']; ?>').val()){
        jQuery('#tbpicture_button_clear<?php echo $item['id']; ?>').show();
        jQuery('#tbpicture_button<?php echo $item['id']; ?>').hide();
      } else {
        jQuery('#tbpicture_button_clear<?php echo $item['id']; ?>').hide();
        jQuery('#tbpicture_button<?php echo $item['id']; ?>').show();
      }

      jQuery('#tbpicture_button<?php echo $item['id']; ?>').click(function() {
       formfield = jQuery('#thumbnail<?php echo $item['id']; ?>').attr('name');
       imgfield = jQuery('#tbimg<?php echo $item['id']; ?>').attr('id');
       setbutton = jQuery('#tbpicture_button<?php echo $item['id']; ?>').attr('id')
       clearbutton = jQuery('#tbpicture_button_clear<?php echo $item['id']; ?>').attr('id')
       tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');
       return false;
      });
      
      jQuery('#tbpicture_button_clear<?php echo $item['id']; ?>').click(function() {
       jQuery('#thumbnail<?php echo $item['id']; ?>').val('');
       jQuery('#tbimg<?php echo $item['id']; ?>').hide();
       jQuery('#tbpicture_button_clear<?php echo $item['id']; ?>').hide();
       jQuery('#tbpicture_button<?php echo $item['id']; ?>').show();
       return false;
      });

    });
    </script>
    <?php
  }
  
  function get_columns(){
    $columns = array(
        'number'     => 'Number',
        'text' => 'Step',
        'picture' => 'Image',
        'thumbnail' => 'Thumbnail'
    );
    return $columns;
  }
  
  function get_sortable_columns() {
    $sortable_columns = array(
        'number'     => array('number',true),     //true means its already sorted
        'step' => array('step',false)
    );
    return $sortable_columns;
  }
  
  function prepare_items($newrow = false) {
    // WordPress globals
    global $wpdb;
    // diy instructions globals
    global $diy_step_table, $diy_instruct_table, $diy_progress_table;
        
    /**
     * First, lets decide how many records per page to show
     */
    $per_page = 20;


    /**
     * REQUIRED. Now we need to define our column headers. This includes a complete
     * array of columns to be displayed (slugs & titles), a list of columns
     * to keep hidden, and a list of columns that are sortable. Each of these
     * can be defined in another method (as we've done here) before being
     * used to build the value for our _column_headers property.
     */
    $columns = $this->get_columns();
    $hidden = array();
    $sortable = $this->get_sortable_columns();


    /**
     * REQUIRED. Finally, we build an array to be used by the class for column 
     * headers. The $this->_column_headers property takes an array which contains
     * 3 other arrays. One for all columns, one for hidden columns, and one
     * for sortable columns.
     */
    $this->_column_headers = array($columns, $hidden, $sortable);

    //get all steps or steps for specific set of instructions
    $sql = "SELECT number, step.id, step.text, picture, thumbnail FROM $diy_progress_table progress, $diy_step_table step
            WHERE progress.step = step.id";
    if(!empty($this->instruction_id) && is_numeric($this->instruction_id)) $sql .= " AND progress.instruct =".$this->instruction_id;
    $sql .= " ORDER BY number";
    $data =  $wpdb->get_results($sql, ARRAY_A);
    
    /**
     * REQUIRED for pagination. Let's figure out what page the user is currently 
     * looking at. We'll need this later, so you should always include it in 
     * your own package classes.
     */
    $current_page = $this->get_pagenum();

    /**
     * REQUIRED for pagination. Let's check how many items are in our data array. 
     * In real-world use, this would be the total number of items in your database, 
     * without filtering. We'll need this later, so you should always include it 
     * in your own package classes.
     */
    $total_items = count($data);
    $this->number_of_steps = count($data);

    /**
     * The WP_List_Table class does not handle pagination for us, so we need
     * to ensure that the data is trimmed to only the current page. We can use
     * array_slice() to 
     */
    $data = array_slice($data,(($current_page-1)*$per_page),$per_page);

    if($newrow){
      array_push($data, array("id" => 'new', 'text' => '', 'picture' => '', 'thumbnail' => '', 'number' => 'new'));
    }

    /**
     * REQUIRED. Now we can add our *sorted* data to the items property, where 
     * it can be used by the rest of the class.
     */
    $this->items = $data;


    /**
     * REQUIRED. We also have to register our pagination options & calculations.
     */
    $this->set_pagination_args( array(
        'total_items' => $total_items,                  //WE have to calculate the total number of items
        'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
        'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
    ) );
  }
}
?>
