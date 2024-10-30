<?php
/**
 * @package Instruct
 * @version 0.3
 */
/*
Plugin Name: Instruct
Plugin URI: http://www.think-bowl.com/instruct
Description: Easily produce step-by-step instructions with images to include in pages and posts.
Author: Eric Bartel
Version: 0.3
Author URI: http://www.think-bowl.com/
*/

// Globals
global $diy_db_version, $diy_step_table, $diy_instruct_table, $diy_progress_table, $wpdb;
$diy_db_version = "0.1";

$diy_step_table = $wpdb->prefix . "instruct_step";
$diy_instruct_table = $wpdb->prefix . "instruct";
$diy_progress_table = $wpdb->prefix . "instruct_progress";

include_once 'admin-form.php';
include_once 'admin-menu.php';
include_once 'admin-table.php';
include_once 'shortcode.php';

function diy_install() {
   global $wpdb;
   global $diy_db_version, $diy_step_table, $diy_instruct_table, $diy_progress_table;
   error_log($diy_instruct_table);
   require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      
   $sql = "CREATE TABLE " . $diy_step_table . " (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,
	  text text NOT NULL,
    picture tinytext,
    thumbnail tinytext,
	  PRIMARY KEY id (id)
    );"; error_log($sql);
   dbDelta($sql);
      
   $sql = "CREATE TABLE " . $diy_instruct_table . " (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,
	  title tinytext NOT NULL,
    description text,
    post mediumint(9),
	  PRIMARY KEY id (id)
    );";
   dbDelta($sql);
   
   $sql = "CREATE TABLE " . $diy_progress_table . " (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,
	  step mediumint(9) NOT NULL,
    instruct mediumint(9) NOT NULL,
    number mediumint(9),
	  PRIMARY KEY id (id)
    );";
   dbDelta($sql);
 
   add_option("diy_db_version", $diy_db_version);
}

register_activation_hook(__FILE__,'diy_install');

// Script enqueues
function diy_admin_scripts() {
wp_enqueue_script('media-upload');
wp_enqueue_script('thickbox');
wp_register_script('diy-upload', WP_PLUGIN_URL.'/instruct/diy-script.js', array('jquery','media-upload','thickbox'));
wp_enqueue_script('diy-upload');
}

function diy_admin_styles() {
wp_enqueue_style('thickbox');
wp_register_style('diy-style', WP_PLUGIN_URL.'/instruct/diy.css');
wp_enqueue_style('diy-style');
}

if (isset($_GET['page']) && $_GET['page'] == 'instructions') {
  add_action('admin_print_scripts', 'diy_admin_scripts');
  add_action('admin_print_styles', 'diy_admin_styles');
}

function diy_post_styles_scripts() {
wp_register_style('diy-style-post', WP_PLUGIN_URL.'/instruct/diy-post.css');
wp_enqueue_style('diy-style-post');
wp_register_script('diy-upload', WP_PLUGIN_URL.'/instruct/diy-script.js', array('jquery'));
wp_enqueue_script('diy-upload');
}

add_action('wp_enqueue_scripts', 'diy_post_styles_scripts');

function reorder_instructions($instruct_id){
  global $wpdb, $diy_instruct_table, $diy_progress_table, $diy_step_table;
  
  $sql = "SELECT progress.id FROM $diy_progress_table progress, $diy_step_table step
          WHERE progress.step = step.id AND progress.instruct=".$instruct_id." ORDER BY number";
  $data =  $wpdb->get_results($sql, ARRAY_A);
  
  $step_cnt = 1;
  foreach($data as $row){
    $sql = "UPDATE ".$diy_progress_table." SET number=".$step_cnt." WHERE id=".$row['id'];
    $wpdb->query($sql);
    $step_cnt++;
  }
}

function is_post($post_id){
  query_posts( 'p='.$post_id );
  return have_posts();
}

// Post box

/* Define the custom box */

add_action( 'add_meta_boxes', 'diy_add_custom_box' );

/* Adds a box to the main column on the Post */
function diy_add_custom_box() {
    add_meta_box( 
        'diy_sectionid', 
        'Instructions',
        'diy_inner_custom_box',
        'post' 
    );
}

/* Prints the box content */
function diy_inner_custom_box( $post ) {
  // WordPress globals
  global $wpdb;
  // diy instructions globals
  global $diy_step_table, $diy_instruct_table, $diy_progress_table;
  
  // Use nonce for verification
  wp_nonce_field( plugin_basename( __FILE__ ), 'diy_noncename' );

  // Find instructions related to post
  $instruct = $wpdb->get_row("SELECT * FROM ".$diy_instruct_table." WHERE post=".$post->ID);
  if(!empty($instruct)){
    echo '<p>Post linked to instructions: <a href="'.get_admin_url().'admin.php?page=instructions&action=edit_instruction&id='.$instruct->id.'">'.$instruct->title.'</a>.</p>';
  } else {
    echo '<p>No related instructions found. Click <a href="'.get_admin_url().'admin.php?page=instructions&action=add_instruction">here</a> to create new instructions.</p>';
  }
}

?>