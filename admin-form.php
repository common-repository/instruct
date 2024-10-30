<?php
function diy_form_list_instruction(){
  // Create an instructions list table and prepare
  $instructionsTable = new Instructions_List_Table();
  $instructionsTable->prepare_items();
  ?>
  <div class="wrap">
    <div id="icon-tools" class="icon32"><br/></div>
    <h2>
      <?php echo 'Instructions'; ?><a href="?page=instructions&action=add_instruction" class="add-new-h2">Add New</a>
    </h2>
    <?php $instructionsTable->display() ?>
  </div>
  <?
}

function diy_form_edit_instruction(){
  // WordPress globals
  global $wpdb;
  // diy instructions globals
  global $diy_step_table, $diy_instruct_table, $diy_progress_table;
  
  //get instructions title and description
  $instructions =  $wpdb->get_row(
                              "SELECT id, title, description
                               FROM ".$diy_instruct_table."
                               WHERE id=".$_REQUEST['id']);

  ?>
  <div class="wrap">
    <div id="icon-tools" class="icon32"><br/></div>
    <h2>
    <?php 
      echo __('Edit Instructions', 'diy_edit_instructions');
      if (!empty($instructions->title)) echo " - ".$instructions->title;
    ?>
    </h2>
    <br/>
    <div id="diy-instructions-form">
    <form id="edit_instruction" action='' method="post">
      <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
      <input type="hidden" name="id" value="<?php echo $instructions->id ?>" />
      <input type="hidden" name="action" value="save_instruction" />
      <div id="titlediv">
        <div id="titlewrap">
          <input type="text" name="title" size="30" value="<?php echo $instructions->title;?>" id="title" autocomplete="off" placeholder="Enter title here">
        </div>
      </div>
      <?php wp_editor($instructions->description,'description'); ?><br/>
      <input id='update-top' class='button-primary' type='submit' name='submit' value='Update' />
      <input id='update-add-step-top'class='button-primary' type="submit" name="submit" value="Update and Add Step" />
      <input id='update-and-close-top'class='button-primary' type="submit" name="submit" value="Update and Close" />
      
      <!--Print table of steps and allow edit-->
      <?php 
        $stepsTable = new Steps_List_Table($instructions->id);
        if($_REQUEST['submit'] == "Update and Add Step") {
          $stepsTable->prepare_items(true);
          echo $stepsTable->display();
          ?>
          <script type="text/javascript" language="JavaScript">
            //document.forms['edit_instruction'].elements['textnew'].focus();
          </script>
          <?php
        } else {
          $stepsTable->prepare_items();
          echo $stepsTable->display();
        }
      ?>
      
      
      <input id='update' class='button-primary' type='submit' name='submit' value='Update' />
      <input id='update-add-step'class='button-primary' type="submit" name="submit" value="Update and Add Step" />
      <input id='update-and-close'class='button-primary' type="submit" name="submit" value="Update and Close" />
    </form>
    </div>
  <?
}

function moveToAnchor($anchor){
  ?>
  <script type="text/javascript">  
    jQuery(document).ready(function() {
      window.location.hash="<?php echo $anchor; ?>";
    });
  </script>
  <?php
}
?>
