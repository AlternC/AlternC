#!/bin/sh -e

# this script will look for upgrade scripts in
# /usr/share/alternc/install/upgrades and execute them based on the
# extension
#
# usage:
# $0 oldvers, where oldvers is the version of the package previously
# installed
#
# an upgrade file is considered only if its basename is a version
# number greater than the $oldvers argument

# remove version from filename by stripping the extension
strip_ext() {
	echo $1 | sed -e 's/\.[^.]*$//' -e 's/[a-z_]*$//'
}

# find the version from a filename by stripping everything but the extension
get_ext() {
	echo $1 | sed 's/^.*\.\([^.]*\)$/\1/'
}

oldvers=$1

if [ -z "$oldvers" -o "$oldvers" = '<unknown>' ]; then
	# this is not an upgrade
	exit 0
fi

#Checking the form of the version variable. it should be x.x.x with x as a digit.
#If it is not we correct it.
if echo $oldvers | grep -qi '[0-9]\.[0-9]\.[0-9].*' ; then
  echo upgrading from : $oldvers
else
  old_ifs="$IFS"
  IFS='~'
  read PART1 PART2 <<EOF
  $oldvers
EOF
  IFS="$old_ifs"
  oldvers=$PART1".0"
  echo upgrading from : $oldvers
fi


. /etc/alternc/local.sh

# the upgrade script we are considering
extensions="*.sql *.sh *.php"
cd /usr/share/alternc/install/upgrades
for file in $( ls $extensions | sort -n ) ; do
	if [ -r $file ]; then
                # the version in the filename
		upvers=`strip_ext $file`
                # the extension
		ext=`get_ext $file`
		if dpkg --compare-versions $upvers gt $oldvers; then
		  echo running upgrade script $file
                  # run the proper program to interpret the upgrade script
		  case "$ext" in
		  sql)
			mysql --defaults-file=/etc/alternc/my.cnf -f \
			< $file || true
			;;
		  php)
		  	php -q $file || true
			;;
		  sh)
		  	sh $file || true
			;;
                  *)
			# Do nothing
			;;
		  esac
		fi
	fi
done
