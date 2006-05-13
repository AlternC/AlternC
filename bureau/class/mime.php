<?php
/*
 $Id: mime.php,v 1.3 2004/06/03 14:32:20 anonymous Exp $
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2002 by the AlternC Development Team.
 http://alternc.org/
 ----------------------------------------------------------------------
 Based on:
 Valentin Lacambre's web hosting softwares: http://altern.org/
 ----------------------------------------------------------------------
 LICENSE

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License (GPL)
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 To read the license please visit http://www.gnu.org/copyleft/gpl.html
 ----------------------------------------------------------------------
 Original Author of file: Benjamin Sonntag 23/12/2001
 Purpose of file: Brouteur php3 pour AlternC
 ----------------------------------------------------------------------
*/
/*
	Brouteur php3 pour AlternC (voir http://www.alternc.org)
	
	Version 0.1 

	Notes :	
		Benjamin Sonntag 23/12/2001 Version initiale
	
	Fichier : 
		mime.php3 : gestion de la liste des types mime des fichiers.

*/
if (!IsSet($MIME_H)) {
$MIME_H=1;

  $bro_mime=
    array(
	  "css" => "text/css",
          "csv" => "text/comma-separated-values",
          "dia" => "application/x-dia",
          "doc" => "application/msword",
          "dot" => "application/msword",
          "epf" => "application/postscript",
          "gif" => "image/gif",
          "hqx" => "application/mac-binhex40",
          "htm" => "text/html",
          "html"=> "text/html",
          "jpeg" => "image/jpeg",
          "jpg" => "image/jpeg",
          "m3u" => "audio/mpegurl",
          "mp3" => "audio/mpeg",
          "ogg" => "application/ogg",
          "pdf" => "application/pdf",
          "php" => "text/plain",
          "png" => "image/png",
          "pps" => "application/powerpoint",
          "ppt" => "application/powerpoint",
          "ps"  => "application/postscript",
          "psd" => "image/psd",
          "ra" => "audio/x-pn-realaudio",
          "ram" => "audio/x-pn-realaudio",
          "rar" => "application/rar",
          "rm" => "audio/x-pn-realaudio",
          "rtf" => "text/rtf",
          "swf" => "application/x-shockwave-flash",
          "sxc" => "application/vnd.sun.xml.calc",
          "sxd" => "application/vnd.sun.xml.draw",
          "sxi" => "application/vnd.sun.xml.impress",
          "sxw" => "application/vnd.sun.xml.writer",
          "tif" => "image/tiff",
          "tiff" => "image/tiff",
          "txt" => "text/plain",
          "vcf" => "text/x-vCard",
          "vcs" => "text/x-vCalendar",
          "xcf" => "image/xcf",
          "xls" => "application/vnd.ms-excel",
          "zip" => "application/zip",
          );

  $bro_icon=
    array(
	  "css" => "txt",
          "csv" => "xls",
          "dia" => "jpg",
          "doc" => "doc",
          "dot" => "doc",
          "epf" => "txt",
          "gif" => "jpg",
          "hqx" => "exe",
          "htm" => "htm",
          "html"=> "htm",
          "jpeg" => "jpg",
          "jpg" => "jpg",
          "m3u" => "m3u",
          "mp3" => "wav",
          "ogg" => "wav",
          "pdf" => "pdf",
          "php" => "php",
          "png" => "jpg",
          "pps" => "ppt",
          "ppt" => "ppt",
          "ps"  => "txt",
          "psd" => "jpg",
          "ra" => "ra",
          "ram" => "ra",
          "rar" => "zip",
          "rm" => "ra",
          "rtf" => "doc",
          "swf" => "swf",        
          "sxc" => "sxc",
          "sxd" => "sxd",
          "sxi" => "sxi",
          "sxw" => "sxw",
          "tif" => "jpg",
          "tiff" => "jpg",
          "txt" => "txt",
          "vcf" => "file",
          "vcs" => "file",
          "xcf" => "jpg",
          "xls" => "xls",
          "zip" => "zip",
          );

  $bro_type=
    array(
	  "css" => __("CSS Stylesheet"),
          "csv" => __("Comma Separated Values data"),
          "dia" => __("DIA Diagram"),
          "doc" => __("Word Document"),
          "dot" => __("Word Document Template"),
          "epf" => __("Encapsulated Postscript"),
          "gif" => __("GIF Image"),
          "hqx" => __("Macintosh Executable"),
          "htm" => __("HTML Document"),
          "html"=> __("HTML Document"),
          "jpeg" => __("JPEG Image"),
          "jpg" => __("JPEG Image"),
          "m3u" => __("Music Playlist"),
          "mp3" => __("MP3 Music File"),
	  "ogg" => __("Ogg Music File"),
          "pdf" => __("Acrobat PDF"),
          "php" => __("PHP Source"),
          "png" => __("PNG Image"),
          "pps" => __("Powerpoint Slideshow"),
          "ppt" => __("Powerpoint Slideshow"),
          "ps"  => __("Postscript Document"),
          "psd" => __("Photoshop Image"),
          "rar" => __("Rar Compressed Files"),
          "rtf" => __("Rich Text Document"),
          "sxc" => __("OpenOffice Spreadsheet"),
          "sxd" => __("OpenOffice Drawing"),
          "sxi" => __("OpenOffice Presentation"),
          "sxw" => __("OpenOffice Writer"),
          "tif" => __("TIFF Image"),
          "tiff" => __("TIFF Image"),
          "txt" => __("Text Document"),
          "vcf" => __("Virtual Card"),
          "vcs" => __("Virtual Card"),
          "xcf" => __("Gimp Image"),
          "xls" => __("Excel Spreadsheet"),
          "zip" => __("Zip Compressed Files"),
          "sxw" => __("Flash Animation"),
          "ra" => __("Real Media File"),
          "rm" => __("Real Media File"),
          "ram" => __("Real Media File"),
          );

}
?>
