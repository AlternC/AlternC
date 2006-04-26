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
	  "css" => "CSS Stylesheet",
          "csv" => "Comma Separated Values data",
          "dia" => "DIA Diagram",
          "doc" => "Word Document",
          "dot" => "Word Document Template",
          "epf" => "Encapsulated Postscript",
          "gif" => "GIF Image",
          "hqx" => "Macintosh Executable",
          "htm" => "HTML Document",
          "html"=> "HTML Document",
          "jpeg" => "JPEG Image",
          "jpg" => "JPEG Image",
          "m3u" => "Music Playlist",
          "mp3" => "MP3 Music File",
	  "ogg" => "Ogg Music File",
          "pdf" => "Acrobat PDF",
          "php" => "PHP Source",
          "png" => "PNG Image",
          "pps" => "Powerpoint Slideshow",
          "ppt" => "Powerpoint Slideshow",
          "ps"  => "Postscript Document",
          "psd" => "Photoshop Image",
          "rar" => "Rar Compressed Files",
          "rtf" => "Rich Text Document",
          "sxc" => "OpenOffice Spreadsheet",
          "sxd" => "OpenOffice Drawing",
          "sxi" => "OpenOffice Presentation",
          "sxw" => "OpenOffice Writer",
          "tif" => "TIFF Image",
          "tiff" => "TIFF Image",
          "txt" => "Text Document",
          "vcf" => "Virtual Card",
          "vcs" => "Virtual Card",
          "xcf" => "Gimp Image",
          "xls" => "Excel Spreadsheet",
          "zip" => "Zip Compressed Files",
          "sxw" => "Flash Animation",
          "ra" => "Real Media File",
          "rm" => "Real Media File",
          "ram" => "Real Media File",
          );

}
?>
