<?php
/*
Plugin Name: WP Visit Counter
Plugin URI: http://faizan-ali.com/
Description: Simply displays one more column in your posts/pages for number of visits.
Author: Faizan Ali
Version: 1.0
Author URI: http://faizan-ali.com/
*/









function wp_vistcnt_activate() {
global $wpdb;
$dbtablehits = $wpdb->prefix . "vistcnt_hits";
if ($wpdb->get_var('SHOW TABLES LIKE '.$dbtablehits) != $dbtablehits){
    $sql = "CREATE TABLE IF NOT EXISTS $dbtablehits(page char(100),PRIMARY KEY(page),count int(15))";

    require_once(ABSPATH.'wp-admin/includes/upgrade.php');
    dbDelta($sql);

}


$dbtableinfo = $wpdb->prefix . "vistcnt_info";
if ($wpdb->get_var('SHOW TABLES LIKE '.$dbtableinfo) != $dbtableinfo){
    $sql = "CREATE TABLE IF NOT EXISTS $dbtableinfo(id INT NOT NULL AUTO_INCREMENT, PRIMARY KEY(id),page char(100), ip_address VARCHAR(30), user_agent VARCHAR(100), datetime VARCHAR(25))";

    require_once(ABSPATH.'wp-admin/includes/upgrade.php');
    dbDelta($sql);

}



}
register_activation_hook(__FILE__, 'wp_vistcnt_activate');




// ########################################################
// ######### check if counter exsist and update ###########
// ########################################################




function wp_vistcnt_addinfo($page){
	global $wpdb;
	$dbtablehits = $wpdb->prefix . "vistcnt_hits";
	$dbtableinfo = $wpdb->prefix . "vistcnt_info";
		// gather user data
	//$ip= $_SERVER["REMOTE_ADDR"];

	$ip = (wp_vistcnt_get_the_user_ip());
	$agent = ($_SERVER["HTTP_USER_AGENT"]);
	$datetime = (date("Y/m/d") . ' ' . date('H:i:s')) ;



	if($wpdb->get_var("SELECT page FROM $dbtablehits WHERE page = '$page'")){
		//A counter for this page  already exsists. Now we have to update it.

			$updatecounter = $wpdb->query("UPDATE $dbtablehits SET count = count+1 WHERE page = '$page'");
			if (!$updatecounter){
			die ("Can't update the counter : " . mysql_error()); // remove ?
			}

		}

	else {
	// This page did not exsist in the counter database. A new counter must be created for this page.

		$insert = $wpdb->query( $wpdb->prepare("INSERT INTO $dbtablehits (page, count)VALUES ('$page', '1')"));

		if (!$insert) {
    		die ("Can\'t insert into $dbtablehits : " . mysql_error()); // remove ?
		}
	}

// ####################################################
// ######### add IP and user-agent and time ###########
// ####################################################





	// check if the  Page &&  IP are in database

if(
	(!$wpdb->get_var("SELECT ip_address FROM $dbtableinfo WHERE ip_address = '$ip'"))

   ||

   (!$wpdb->get_var("SELECT page FROM $dbtableinfo WHERE page = '$page'"))


) {
	// if not , add it.
	$adddata = $wpdb->query("INSERT INTO $dbtableinfo (page, ip_address, user_agent, datetime)VALUES ('". $page ."', '". mysql_real_escape_string($ip) . "' , '" . mysql_real_escape_string($agent) . "' , '" . mysql_real_escape_string($datetime) . "' ) ") ;

/*	$adddata = $wpdb->query( $wpdb->prepare(
	"
		INSERT INTO $dbtableinfo
		( ip_address, user_agent, datetime )
		VALUES ( %s, %s, %s )
	",
   mysql_real_escape_string($ip),
	mysql_real_escape_string($agent),
	mysql_real_escape_string($datetime)
) ); */


	if (!$adddata) {
	    die('Could not add IP : ' . mysql_error()); // remove ?
	}
}


// ***************************************************************
// ** delete the first entry in $dbtableinfo if rows > $maxrows **
// ***************************************************************

$result = $wpdb->query("SELECT * FROM $dbtableinfo", $link);
$num_rows = mysql_num_rows($result);
$to_delete = $num_rows- $maxrows;
if($to_delete > 0) {
	for ($i = 1; $i <= $to_delete; $i++) {

		$delete = $wpdb->query( $wpdb->prepare("DELETE FROM $dbtableinfo ORDER BY id LIMIT 1")) ;
		if (!$delete) {
		    die('Could not delete : ' . mysql_error()); // remove ?
		}
	}
}




}










function wp_vistcnt_get_the_user_ip() {
if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
//check ip from share internet
$ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
//to check ip is pass from proxy
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
$ip = $_SERVER['REMOTE_ADDR'];
}
return apply_filters( 'wpb_get_ip', $ip );
}

add_shortcode('show_ip', 'wp_vistcnt_get_the_user_ip');







function wp_vistcnt_count_this_page() {
	global $post;
    $post_data = get_post($post->ID, ARRAY_A);
    $page = $post_data['post_name'];
	if($page != '') {
		wp_vistcnt_addinfo($page);
	}
    //return $slug;

	wp_vistcnt_add_custom_field($post->ID);
}

//add_shortcode('count_visitors', 'the_slug');
add_action( 'wp_footer', 'wp_vistcnt_count_this_page' );

/*function wp_vistcnt_get_featured_image($post_ID) {
    $post_thumbnail_id = get_post_thumbnail_id($post_ID);
    if ($post_thumbnail_id) {
        $post_thumbnail_img = wp_get_attachment_image_src($post_thumbnail_id, 'featured_preview');
        return $post_thumbnail_img[0];
    }
}
*/

function wp_vistcnt_columns_head($defaults) {
    $defaults['wp_vistcnt_count'] = 'Unique Visits';
    return $defaults;
}

function wp_vistcnt_columns_content($column_name, $post_ID) {
    if ($column_name == 'wp_vistcnt_count') {
        $wp_vistcnt_count = get_post_meta( $post_ID, 'wp_visit_counts_by_Faizan', true );
        if ($wp_vistcnt_count) {
            echo $wp_vistcnt_count;
        }
    }
}

add_filter('manage_posts_columns', 'wp_vistcnt_columns_head');
add_action('manage_posts_custom_column', 'wp_vistcnt_columns_content', 10, 2);

add_filter('manage_pages_columns', 'wp_vistcnt_columns_head');
add_action('manage_pages_custom_column', 'wp_vistcnt_columns_content', 10, 2);




function wp_vistcnt_add_custom_field($post_id){
	$page = wp_vistcnt_get_the_slug($post_id);
	$visit_counts = wp_vistcnt_get_unique_hits_by_posts($page);
	add_post_meta($post_id, 'wp_visit_counts_by_Faizan', $visit_counts, true ) || 		     update_post_meta($post_id, 'wp_visit_counts_by_Faizan', $visit_counts);
	}


function wp_vistcnt_get_total_hits(){
	global $wpdb;
	$dbtablehits = $wpdb->prefix . "vistcnt_hits";
	$results = $wpdb->get_results("SELECT SUM(count)  AS totalhits FROM " . $dbtablehits);
	return $results[0]->totalhits;

}


function wp_vistcnt_get_unique_hits_by_posts($page){
	global $wpdb;
	$dbtableinfo = $wpdb->prefix . "vistcnt_info";
	$results = $wpdb->get_results("SELECT ip_address FROM $dbtableinfo where page = '" . $page . "'");
	return count($results);

}


function wp_vistcnt_get_the_slug($post_ID){
    $post_data = get_post($post_ID, ARRAY_A);
    $slug = $post_data['post_name'];
	return $slug;

}





