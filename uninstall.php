<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();


function wp_vistcnt_remove_database() {
     global $wpdb;
     $dbtablehits = $wpdb->prefix . "vistcnt_hits";
	 $dbtableinfo = $wpdb->prefix . "vistcnt_info";

     $sql = "DROP TABLE IF EXISTS $dbtablehits,$dbtableinfo;";
     $wpdb->query($sql);
    // delete_option("wp_vistcnt_db_version");

	$qry = "DELETE FROM ". $wpdb->prefix  ."postmeta WHERE `meta_key` = 'wp_visit_counts_by_Faizan'";
	$wpdb->query($qry);
}

//register_deactivation_hook( __FILE__, 'wp_vistcnt_remove_database' );

wp_vistcnt_remove_database();

?>
