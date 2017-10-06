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
        "css" => _("CSS Stylesheet"),
        "csv" => _("Comma Separated Values data"),
        "dia" => _("DIA Diagram"),
        "doc" => _("Word Document"),
        "dot" => _("Word Document Template"),
        "epf" => _("Encapsulated Postscript"),
        "gif" => _("GIF Image"),
        "hqx" => _("Macintosh Executable"),
        "htm" => _("HTML Document"),
        "html" => _("HTML Document"),
        "jpeg" => _("JPEG Image"),
        "jpg" => _("JPEG Image"),
        "m3u" => _("Music Playlist"),
        "mp3" => _("MP3 Music File"),
        "ogg" => _("Ogg Music File"),
        "pdf" => _("Acrobat PDF"),
        "php" => _("PHP Source"),
        "png" => _("PNG Image"),
        "pps" => _("Powerpoint Slideshow"),
        "ppt" => _("Powerpoint Slideshow"),
        "ps" => _("Postscript Document"),
        "psd" => _("Photoshop Image"),
        "rar" => _("Rar Compressed Files"),
        "rtf" => _("Rich Text Document"),
        "sxc" => _("OpenOffice Spreadsheet"),
        "sxd" => _("OpenOffice Drawing"),
        "sxi" => _("OpenOffice Presentation"),
        "sxw" => _("OpenOffice Writer"),
        "tif" => _("TIFF Image"),
        "tiff" => _("TIFF Image"),
        "txt" => _("Text Document"),
        "vcf" => _("Virtual Card"),
        "vcs" => _("Virtual Card"),
        "xcf" => _("Gimp Image"),
        "xls" => _("Excel Spreadsheet"),
        "zip" => _("Zip Compressed Files"),
        "sxw" => _("Flash Animation"),
        "ra" => _("Real Media File"),
        "rm" => _("Real Media File"),
        "ram" => _("Real Media File"),
    );
}
