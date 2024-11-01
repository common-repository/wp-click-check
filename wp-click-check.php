<?php
/*
Plugin Name: WP Click Check
Plugin URI: http://saquery.com/wordpress
Description: External <a href="admin.php?page=wp-click-check/wp-click-check.php">Blog Link Analytics</a>
Version: 0.4.1
Author: Stephan Ahlf
Author URI: http://saquery.com
*/

/*
Copyright 2009 Stephan Ahlf 

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

	$wpdb->saqClicks = $wpdb->prefix."SAQ_CLICK_STATISTICS";

	$saqClickCheck_db_version = "0.1";
  	
	function saqSetupWpClickCheck() {
		global $wpdb;
		$sql="CREATE TABLE IF NOT EXISTS ".$wpdb->saqClicks."(
		REFERER varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
		URL varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
		CLICKS INT(11) NOT NULL,
		DT DATETIME NOT NULL, 
		UNIQUE (REFERER,URL)
		) ENGINE = MYISAM CHARACTER SET ascii COLLATE ascii_general_ci ";
      		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      		dbDelta($sql);

	}


	function saqParseLink($html="") {
	
		preg_match_all ('/[^=]{1,}="[^"]+"/', $html, $tmp);
		foreach ($tmp[0] as $pair) {
		     list ($tag , $value) = explode ("=", $pair , 2);
		     $tmp[trim($tag)]=trim($value, '"');
	   	}

		$html='';
		$target="_self";
		//$href=null;
		//$href_cnt=null;
		$count="false";
		$lnk=null;
		$onclick=null;
		$href=null;
		$href_cnt=null;
		foreach ($tmp as $key => $value) {
			switch (strtolower($key)) {
			case '0':
			    	break;
			case 'href':
				$u=get_bloginfo("url");
				$pos = strpos(strtolower($value), strtolower($u));
				$lnk=$value;
				if ($pos === false) {
					$target="_blank";
					$count="true";
					$value=str_replace("?", "&", $value);
				}
				$href .= $key . "=\"" . $value . "\" ";
				$href_cnt .= $key . "=\"$u/redirect.php?saq_target=" . $value . "\" ";			    
			    break;
			case 'target':
				$target=$value;
			    	break;
			case 'cnt':
				$count=$value;
				//if ($value=="false") $href_cnt=$href;
				break;
			case 'onclick':
				$count="false";
			    	$html .= $key . "=\"" . $value . "\" ";
				break;
			case 'title':
				break;
			default:
			    	$html .= $key . "=\"" . $value . "\" ";
			    	break;
			}
		}
		if ($count=="true") {
			$pos1 = strpos(strtolower($lnk), 'javascript:');
			if ($pos1 === false && substr($lnk,0,1) != '#' ) {
				$html .= $href_cnt;
			} else {
				$html .= $href;
			}
		} else {
			$html .= $href;
		}
		if (substr($lnk,0,1) != '#') $html .= "target=\"" . $target . "\" ";	   
		$html=trim($html);
	   	return '<a '. $html .'>';
	}


	function saqParsePost($text) {
		return saqParseLink($text[1]).$text[2];
	}



	function saqCountClick($source, $target) {
		global $wpdb;
		$result = false;
		$source = $wpdb->escape($source);
		$target = $wpdb->escape($target);
		$dt = gmdate('Y-m-d H:i:s', (time() + (get_option('gmt_offset') * 3600)));
		$sql="INSERT INTO ".$wpdb->saqClicks." (REFERER,URL,CLICKS,DT) VALUES ('$source', '$target',1,'$dt')  ON duplicate KEY UPDATE CLICKS=CLICKS+1, DT='$dt'";


		$u=get_bloginfo("url");
		$pos = strpos(strtolower($source), strtolower($u));
		if ($pos === false) {
			wp_redirect(get_bloginfo('wpurl'), 404);
			exit;
		} else {
			$result = $wpdb->query(str_replace("\'", "\'\'", $sql));
			$result = true;
		}
		return $result;
	}

	function saqMenu1() {

		global $wpdb;
		$res = $wpdb->get_results("SELECT URL, sum(CLICKS) as CLICKS, max(DT) as DT FROM ".$wpdb->saqClicks." GROUP BY URL ORDER BY CLICKS DESC, DT DESC LIMIT 0 , 30");
		print "<div class='wrap'>";
		print "<h2>TOP TARGETS</h2>";
		print "<table style='text-align: center; width: 100%;'>";
		print "<thead>";
		print "<tr>";
		print "<th style='width: 70%;text-align: left;'>TARGET</th>";
		print "<th style='width: 10%;'>CLICKS</th>";
		print "<th style='width: 20%;'>Date/Time</th>";
		print "</tr>";
		print "</thead>";
		print "<tbody>";	    
		foreach ($res as $row) {
			print "<tr class='alternate'";
			print "<td style='text-align: left;'>".urldecode($row->URL)."</td>";
			print "<td>$row->CLICKS</td>";
			print "<td>$row->DT</td>";
			print "</tr>";
		}
		print "</tbody>";
		print "</table>";
		print "</div>";

		$res = $wpdb->get_results("SELECT REFERER, URL, sum(CLICKS) as CLICKS, max(DT) as DT FROM ".$wpdb->saqClicks." GROUP BY REFERER, URL ORDER BY CLICKS DESC, DT DESC LIMIT 0 , 30");
		print "<div class='wrap'>";
		print "<h2>TOP TARGETS by source</h2>";
		print "<table style='text-align: center; width: 100%;'>";
		print "<thead>";
		print "<tr>";
		print "<th style='width: 35%;text-align: left;'>SOURCE</th>";
		print "<th style='width: 40%;text-align: left;'>TARGET</th>";
		print "<th style='width: 5%;'>CLICKS</th>";
		print "<th style='width: 20%;'>Date/Time</th>";
		print "</tr>";
		print "</thead>";
		print "<tbody>";	    
		foreach ($res as $row) {
			print "<tr class='alternate'";
			print "<td style='text-align: left;'>$row->REFERER</td>";
			print "<td style='text-align: left;'>".urldecode($row->URL)."</td>";
			print "<td>$row->CLICKS</td>";
			print "<td>$row->DT</td>";
			print "</tr>";
		}
		print "</tbody>";
		print "</table>";
		print "</div>";

	}

	function saqMenu() {
		add_menu_page('Link Analytics', 'Link Analytics', 8, __FILE__, 'saqMenu1');
	 }

	function saqParseLinks($input) { 
		return preg_replace_callback ("/<a ([^>]{1,})>(.+?<\/a>)/", "saqParsePost", $input);		

	}

	if(isset($_GET['activate']) && $_GET['activate'] == 'true')
	add_action('init', 'saqSetupWpClickCheck');
	add_action('admin_menu', 'saqMenu');
	add_filter('the_content', 'saqParseLinks', 99);
	add_filter('comment_text', 'saqParseLinks', 99);
	//add_action('widget_text', 'saqParseLinks');

	/**
 * Content of Dashboard-Widget
 */
function saqDashboardClickCheck() {
		global $wpdb;
		$res = $wpdb->get_results("SELECT URL, sum(CLICKS) as CLICKS, max(DT) as DT FROM ".$wpdb->saqClicks." GROUP BY URL ORDER BY CLICKS DESC, DT DESC LIMIT 0 , 10");
		print "<div class='wrap'>";
		print "<table style='text-align: center; width: 100%;'>";
		print "<tbody>";	    
		foreach ($res as $row) {
			print "<tr class='alternate'";
			print "<td style='width: 90%;text-align: left;'>".urldecode($row->URL)."</td>";
			print "<td style='width: 10%;'>$row->CLICKS</td>";
			print "</tr>";
		}
		print "</tbody>";
		print "</table>";
		print "</div>";
}
 
/**
 * add Dashboard Widget via function wp_add_dashboard_widget()
 */
function saqDashboardClickCheck_setup() {
	wp_add_dashboard_widget( 'saqDashboardClickCheck', __( 'Link Analytics' ), 'saqDashboardClickCheck' );
}
 
/**
 * use hook, to integrate new widget
 */
add_action('wp_dashboard_setup', 'saqDashboardClickCheck_setup');
 


?>
