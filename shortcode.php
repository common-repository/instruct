<?php

/* 
 * Shortcodes 
 */

// [instructions id="id int of instructions"]
function shortcode_instructions( $atts ) {
	extract( shortcode_atts( array(
		'id' => 0,
    'notitle' => false  
	), $atts ) );

  if( ! is_numeric($id)) 
    return NULL;
  
  if( $notitle == 'true')
    $notitle = true;
  else 
    $notitle = false;
  
  // WordPress globals
  global $wpdb;
  // diy instructions globals
  global $diy_step_table, $diy_instruct_table, $diy_progress_table;
  
  //get instructions title and description
  $instructions =  $wpdb->get_row(
                                  "SELECT title, description
                                   FROM ".$diy_instruct_table."
                                   WHERE id=".$id);

  $html = "";
  
  if(!$notitle) 
    $html = "<h3 class='instruct_title'>".$instructions->title."</h3>";
  
  $html .= "<p>".$instructions->description."</p>";
  
  
  $step_template .= "<tr class='instruct_row'><td class='instruct_row'>#STEP_NUM#</td><td class='instruct_row'>#STEP_TEXT#</td><td class='instruct_row'>#IMG_TEMPLATE#</tr></tr>";
  $tbimg_template .= '<div id="instruct-thumbnail"><img class="instruct-thumbnail-img" src="#STEP_THUMBNAIL#" large="#STEP_PICTURE#"></div>';
  
  //get instructions title and description
  $sql = "SELECT step.id step_id, `text`, number, picture, thumbnail
                 FROM ".$diy_step_table." step, ".$diy_progress_table." progress
                 WHERE step.id = progress.step AND progress.instruct=".$id." ORDER BY number";
  $data =  $wpdb->get_results($sql, OBJECT);
  
  if(count($data) > 0){
    $html .= "<table class='instruct>";

    foreach($data as $step) {
      
      // Go until no step exists with previous of current
      if($step == NULL) break;
      $step_html = $step_template;
      $step_html = str_replace("#STEP_NUM#", $step->number, $step_html);
      $step_html = str_replace("#STEP_TEXT#", $step->text, $step_html);
      
      if(!empty($step->thumbnail) and !empty($step->picture)) {
        $img_html = str_replace("#STEP_THUMBNAIL#", $step->thumbnail, $tbimg_template);
        $img_html = str_replace("#STEP_PICTURE#", $step->picture, $img_html);
      } elseif(empty($step->thumbnail) and !empty($step->picture)) {
        $img_html = str_replace("#STEP_THUMBNAIL#", $step->picture, $tbimg_template);
        $img_html = str_replace("#STEP_PICTURE#", $step->picture, $img_html);
      } elseif(!empty($step->thumbnail) and empty($step->picture)) {
        $img_html = str_replace("#STEP_THUMBNAIL#", $step->thumbnail, $tbimg_template);
        $img_html = str_replace("#STEP_PICTURE#", $step->thumbnail, $img_html);
      } else {
        $img_html = '';
      }
      
      $step_html = str_replace("#IMG_TEMPLATE#", $img_html, $step_html);
      $query = $step_sql."=".$step->step_id;
      $html .= $step_html.PHP_EOL;
    }

    $html .= "</table>";
  }
  
  # Add divisions for pop-up
  add_action('wp_footer', 'diy_shortcode_foot');
  
	return $html;
}
add_shortcode( 'instructions', 'shortcode_instructions' );

function diy_shortcode_foot() {
  echo '<div id="instruct-thumbnail-large"></div><div id="instruct-thumbnail-bg"></div>';
}

?>
