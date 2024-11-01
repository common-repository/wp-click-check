<?php
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
	function saq_get_redirect_query() {
		$parms=array();
	      	foreach($_GET as $key => $val){
			if (strcmp($key,"saq_target")!=0) {
			  	$parms[$key]=$val;  
			} else {
				$target=$val;
			}
	      	}
		if (count($parms)!=0) {
			$p=array();
		      	foreach($parms as $key => $val){
			  	$p[$key]=$key."=".$val;  
		      	}
			$target.="?".implode("&", $p);
		}			
		return $target;
	}
	require('./wp-blog-header.php');
	$result = false;
	$target=saq_get_redirect_query();
//	print_r();
//print_r(($_SERVER['QUERY_STRING'])).";".
	if (function_exists('saqCountClick')) $result = saqCountClick($_SERVER['HTTP_REFERER'], $target);	
?>

<HEAD>   	
	<?php 
		if ($result) echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=".$target."\">";
	?>
</HEAD>
<body>
	<form>
		<?php 
			if ($result) {
				echo "Redirecting to ".$target. "...";
			} else {
				echo $result;
			}
		?>
	</form>
</body>

