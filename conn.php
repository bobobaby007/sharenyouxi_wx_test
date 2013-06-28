<?php
//Á¬½Ó
$con = mysql_connect ( "localhost", "root", "85bcc87cc3" );
if (! $con) {
	die ( 'Could not connect: ' . mysql_error () );
}
mysql_select_db ( "sharenyouxi_wx", $con );
?>
