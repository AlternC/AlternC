#!/bin/sh
# Update po files in locales/$1/LC_MESSAGES
# 
if [ "$1" = "all" ]
then
    find -type d -maxdepth 1 -mindepth 1 -exec ./updatelang.sh {} \;
    exit 0
fi

if [ -d $1/LC_MESSAGES ] 
then
    for i in *.po
    do
      echo -n "Updating $i : "
      msgmerge -v -U $1/LC_MESSAGES/$i $i
      echo " Done."
    done
else
    echo "Usage : updatelang.sh <lang code>"
    echo "  Update the .po files in <lang code>/LC_MESSAGES directory"
    echo "  Use 'all' as lang code to update all the po files"
fi
