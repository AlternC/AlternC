#!/bin/sh
# Update po files in locales/$1/LC_MESSAGES
# 
if [ -d $1/LC_MESSAGES ] 
then
    for i in *.po 
    do
      echo -n "Updating $i : "
      msgmerge -v -U $1/LC_MESSAGES/$i $i
      echo " Done."
    done
else
    echo "Directory $1/LC_MESSAGES/ does not exist ! "
fi
