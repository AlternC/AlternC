diff --git a/ssl/alternc-ssl.install.php b/ssl/alternc-ssl.install.php
index ba568910..041eef80 100644
--- a/ssl/alternc-ssl.install.php
+++ b/ssl/alternc-ssl.install.php
@@ -9,7 +9,9 @@ if ($argv[1] == "templates") {
     // install ssl.conf
     echo "[alternc-ssl] Installing ssl.conf template\n";
     copy("/etc/alternc/templates/apache2/mods-available/ssl.conf","/etc/apache2/mods-available/ssl.conf");
-    mkdir("/var/run/alternc-ssl");
+    if (!is_dir('/var/run/alternc-ssl')) {
+        mkdir("/var/run/alternc-ssl");
+    }
     chown("/var/run/alternc-ssl","alterncpanel");
     chgrp("/var/run/alternc-ssl","alterncpanel");
     // replace open_basedir line if necessary : 
@@ -64,4 +66,23 @@ if ($argv[1] == "before-reload") {
         $db->query("UPDATE sub_domaines SET web_action='DELETE' WHERE type='php52-mixssl';");
     }
 
+    // Enable name-based virtual hosts in Apache2 : 
+    $f = fopen("/etc/apache2/ports.conf", "rb");
+    if (!$f) {
+        echo "FATAL: there is no /etc/apache2/ports.conf ! I can't configure name-based virtual hosts\n";
+    } else {
+        $found = false;
+        while ($s = fgets($f, 1024)) {
+            if (preg_match(":^[^#]*NameVirtualHost.*443:", $s)) {
+                $found = true;
+                break;
+            }
+        }
+        fclose($f);
+        if (!$found) {
+            $f = fopen("/etc/apache2/ports.conf", "ab");
+            fputs($f, "\n<IfModule mod_ssl.c>\n  NameVirtualHost *:443\n\n</IfModule>\n");
+            fclose($f);
+        }
+    }
 } // before-reload
