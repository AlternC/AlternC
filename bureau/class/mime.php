<?php

/*
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
*/

/**
 * Browser mime function to detect mime types and show icons or type names
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */
if (!IsSet($MIME_H)) {
    $MIME_H = 1;

    $bro_mime = array(
        "css" => "text/css",
        "csv" => "text/comma-separated-values",
        "dia" => "application/x-dia",
        "doc" => "application/msword",
        "dot" => "application/msword",
        "epf" => "application/postscript",
        "gif" => "image/gif",
        "hqx" => "application/mac-binhex40",
        "htm" => "text/html",
        "html" => "text/html",
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
        "ps" => "application/postscript",
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

    $bro_icon = array(
        "css" => "txt",
        "csv" => "xls",
        "dia" => "jpg",
        "doc" => "doc",
        "dot" => "doc",
        "epf" => "txt",
        "gif" => "jpg",
        "hqx" => "exe",
        "htm" => "htm",
        "html" => "htm",
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
        "ps" => "txt",
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

    $bro_type = array(
        "css" => __("CSS Stylesheet", "alternc", true),
        "csv" => __("Comma Separated Values data", "alternc", true),
        "dia" => __("DIA Diagram", "alternc", true),
        "doc" => __("Word Document", "alternc", true),
        "dot" => __("Word Document Template", "alternc", true),
        "epf" => __("Encapsulated Postscript", "alternc", true),
        "gif" => __("GIF Image", "alternc", true),
        "hqx" => __("Macintosh Executable", "alternc", true),
        "htm" => __("HTML Document", "alternc", true),
        "html" => __("HTML Document", "alternc", true),
        "jpeg" => __("JPEG Image", "alternc", true),
        "jpg" => __("JPEG Image", "alternc", true),
        "m3u" => __("Music Playlist", "alternc", true),
        "mp3" => __("MP3 Music File", "alternc", true),
        "ogg" => __("Ogg Music File", "alternc", true),
        "pdf" => __("Acrobat PDF", "alternc", true),
        "php" => __("PHP Source", "alternc", true),
        "png" => __("PNG Image", "alternc", true),
        "pps" => __("Powerpoint Slideshow", "alternc", true),
        "ppt" => __("Powerpoint Slideshow", "alternc", true),
        "ps" => __("Postscript Document", "alternc", true),
        "psd" => __("Photoshop Image", "alternc", true),
        "rar" => __("Rar Compressed Files", "alternc", true),
        "rtf" => __("Rich Text Document", "alternc", true),
        "sxc" => __("OpenOffice Spreadsheet", "alternc", true),
        "sxd" => __("OpenOffice Drawing", "alternc", true),
        "sxi" => __("OpenOffice Presentation", "alternc", true),
        "sxw" => __("OpenOffice Writer", "alternc", true),
        "tif" => __("TIFF Image", "alternc", true),
        "tiff" => __("TIFF Image", "alternc", true),
        "txt" => __("Text Document", "alternc", true),
        "vcf" => __("Virtual Card", "alternc", true),
        "vcs" => __("Virtual Card", "alternc", true),
        "xcf" => __("Gimp Image", "alternc", true),
        "xls" => __("Excel Spreadsheet", "alternc", true),
        "zip" => __("Zip Compressed Files", "alternc", true),
        "sxw" => __("Flash Animation", "alternc", true),
        "ra" => __("Real Media File", "alternc", true),
        "rm" => __("Real Media File", "alternc", true),
        "ram" => __("Real Media File", "alternc", true),
    );
}
