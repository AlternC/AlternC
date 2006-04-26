#!/bin/sh
cd bureau
( find admin -type f ; find class -type f ) | grep -v CVS | grep -v \\.png | sort > /tmp/d1.txt
cd ../modules
cat * | sort >/tmp/d2.txt
diff /tmp/d1.txt /tmp/d2.txt
