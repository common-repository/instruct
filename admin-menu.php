<?php
// Menus
// Hook to load menu function
add_action('admin_menu', 'diy_menu_load');

function diy_menu_load() {
  add_menu_page("Instructions", "Instructions", 'manage_options', 'instructions', diy_menu_main);
}

function diy_menu_main() {
  // WordPress globals
  global $wpdb, $user_ID;
  // diy instructions globals
  global $diy_step_table, $diy_instruct_table, $diy_progress_table;
  
  if (empty($_REQUEST['action'])) 
    $action = 'default';
  else 
    $action = $_REQUEST['action'];
  
  switch ($action){
    case 'add_instruction':
      $sql = 'INSERT INTO '.$diy_instruct_table.' SET title="", description=""';
      $wpdb->insert($diy_instruct_table, array('title' => '', 'description' => ''));
      
      $_REQUEST['id'] = $wpdb->insert_id;
      diy_form_edit_instruction();
      break;
    case 'edit_instruction':
      diy_form_edit_instruction();
      break;
    case 'delete_instruction':
      $instruct = $wpdb->get_row("SELECT * FROM ".$diy_instruct_table." WHERE id=".$_REQUEST['id']);
      ?>
      <div style="text-align: center;width:480px;background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
        Are you sure you want to delete instructions for <?php echo $instruct->title; ?>?<br/><br/>
        <div style="text-align: right;">
          <a href="?page=<?php echo $_REQUEST['page']; ?>&action=delete_instruction_confirmed&id=<?php echo $_REQUEST['id']; ?>" >OK</a> 
          <a style="padding-left: 15px" href="?page=<?php echo $_REQUEST['page']; ?>" >Cancel</a>
        </div>
      </div>
      <?php
      diy_form_list_instruction();
      break;
    case 'delete_instruction_confirmed':
      $sql = "SELECT step FROM ".$diy_progress_table." WHERE instruct=".$_REQUEST['id'];
      $steps =  $wpdb->get_results($sql, ARRAY_A);
      
      foreach($steps as $step){
        $sql = "DELETE FROM ".$diy_step_table." WHERE id=".$step['step'];
        $wpdb->query($sql);
      
        $sql = "DELETE FROM ".$diy_progress_table." WHERE step=".$step['step'];
        $wpdb->query($sql);
      }
      
      $sql = "DELETE FROM ".$diy_instruct_table." WHERE id=".$_REQUEST['id'];
      $wpdb->query($sql);
      
      diy_form_list_instruction();
      break;
    case 'delete_step':
      // Deletes progression steps and actual step 
      $sql = "DELETE FROM ".$diy_step_table." WHERE id=".$_REQUEST['step'];
      $wpdb->query($sql);
      
      $sql = "DELETE FROM ".$diy_progress_table." WHERE step=".$_REQUEST['step'];
      $wpdb->query($sql);
      
      reorder_instructions($_REQUEST['id']);
      
      diy_form_edit_instruction();
      break;
    case 'save_instruction':  
      // Save text and order number for each step
      $jumpto = NULL;

      foreach($_REQUEST as $key => $value){
        if(preg_match('/text(\d+)/', $key, $number)){ 
          $fields = array();
          $id = $number[1];
          $fields['text'] = stripcslashes($value);
          $fields['picture'] = $_REQUEST['picture'.$id];
          $fields['thumbnail'] = $_REQUEST['thumbnail'.$id];

          $result = $wpdb->update($diy_step_table,
                                  $fields,
                                  array('id' => $id),
                                  array('%s', '%s', '%s'),
                                  array('%d')
                    );
          
          $oldnumber = $_REQUEST['oldnumber'.$id];
          $number = $_REQUEST['number'.$id];
          if($oldnumber != $number){
            if($oldnumber > $number){
              # Move step to lesser number
              $sql = "UPDATE ".$diy_progress_table." SET number = number + 1 WHERE number >= ".$number." AND number < ".$oldnumber." AND instruct = ".$_REQUEST['id'];
              $wpdb->query($sql);
            } else {
              # Move step to greater number
              $sql = "UPDATE ".$diy_progress_table." SET number = number - 1 WHERE number > ".$oldnumber." AND number <= ".$number." AND instruct = ".$_REQUEST['id'];
              $wpdb->query($sql);
            }
            $sql = "UPDATE ".$diy_progress_table." SET number = ".$number." WHERE step=".$id." AND instruct = ".$_REQUEST['id'];
            $wpdb->query($sql);
            $jumpto = 'stepanchor'.$id;
          }
        } elseif (preg_match('/textnew/', $key)) {
          $step_fields = array();
          $step_fields['text'] = stripcslashes($value);
          $step_fields['picture'] = $_REQUEST['picturenew'];
          $step_fields['thumbnail'] = $_REQUEST['thumbnailnew'];
          $result = $wpdb->insert($diy_step_table, 
                                  $step_fields,
                                  array('%s', '%s', '%s'));
          $new_step_id = $wpdb->insert_id;
          
          // Find max step number and add one
          $sql = "SELECT MAX(number) max FROM ".$diy_progress_table." WHERE instruct=".$_REQUEST['id'];
          $max = $wpdb->get_row($sql);
          
          $progress_fields = array();
          $progress_fields['number'] = $max->max + 1;
          $progress_fields['step'] = $new_step_id;
          $progress_fields['instruct'] = $_REQUEST['id'];
          
          $result = $wpdb->insert($diy_progress_table,
                                  $progress_fields,
                                  array('%d', '%d', '%d'));
        } 
      }
      
      // Save title and description for each instruction
      $result = $wpdb->update(
                              $diy_instruct_table,
                              array(
                                  'title' => stripcslashes($_REQUEST['title']),
                                  'description' => stripcslashes($_REQUEST['description'])
                              ),
                              array(
                                  'id' => $_REQUEST['id']
                              ),
                              array(
                                  '%s',
                                  '%s'
                              ),
                              array(
                                  '%d'
                              )
                );
      
      if($_REQUEST['submit'] == "Update and Add Step") {
        $jumpto = 'stepanchornew';
        diy_form_edit_instruction();
      } elseif ($_REQUEST['submit'] == "Update Number" or $_REQUEST['submit'] == "Update"){
        diy_form_edit_instruction();
      } else {
        diy_form_list_instruction();
      }
      
      if(!empty($jumpto)) {
        moveToAnchor($jumpto);
      }
      break;
    case 'save_step':
      break;
    case 'add_post':
      $instruct = $wpdb->get_row("SELECT * FROM ".$diy_instruct_table." WHERE id=".$_REQUEST['id']);
      
      $post = & get_post($instruct->post);
      
      if(empty($instruct->post) || empty($post)){
        // Create post with title of instruction as title and shortcode to get instructions
        $instruct_post = array(
           'post_title' => $instruct->title,
           'post_content' => '[instructions id='.$instruct->id.' notitle=true]',
           'post_status' => 'publish',
           'post_author' => $user_ID
        );
        $post_id = wp_insert_post( $instruct_post );
        
        // Update instructions with post id once post is generated
        $wpdb->update($diy_instruct_table, array('post' => $post_id), array('ID' => $instruct->id), array('%d'));
      } 
      ?>
      <div style="text-align: center;width:480px;background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
        Post added for <?php echo $instruct->title; ?>.
      </div>
      <?php
      diy_form_list_instruction();
      break;
    default:
      diy_form_list_instruction();
  }
  
}

    
