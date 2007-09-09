# some miscellaneous shell functions

# imprime le nom d'usager associé au domaine
get_account_by_domain() {
	# les admintools ne sont peut-être pas là
	if [ -x "/usr/bin/get_account_by_domain" ]
	then
		# only first field, only first line
		/usr/bin/get_account_by_domain "$1" | cut -d\  -f1 | cut -d'
' -f 1
	else
		# implantons localement ce que nous avons besoin, puisque admintools
		# n'est pas là
  		mysql -h$MYSQL_HOST -u$MYSQL_USER -p$MYSQL_PASS -D$MYSQL_DATABASE -B -N -e \
  		'SELECT a.login FROM membres a, sub_domaines b WHERE a.uid = b.compte AND \
  		CONCAT(IF(sub="", "", CONCAT(sub, ".")), domaine) = "'"$1"'" LIMIT 1;'
	fi
}

# add the standard input to a given file, only if not already present
append_no_dupe() {
	realfile="$1"
	tmpfile=`mktemp`
	trap "rm -f $tmpfile; exit 1" 1 2 15
	cat > $tmpfile
	if [ -r "$realfile" ] &&
		(diff -q "$tmpfile" "$realfile" > /dev/null || \
			diff -u "$tmpfile" "$realfile"  | grep '^ ' | sed 's/^ //' | diff -q - "$tmpfile" > /dev/null)
	then
		ret=0
	else
		ret=1
		cat "$tmpfile" >> "$realfile"
	fi
	rm -f "$tmpfile"
	return "$ret"
}

add_dom_entry() {
	# protect ourselves from interrupts
	trap "rm -f ${override_f}.new; exit 1" 1 2 15
	# ajouter une entrée, seulement s'il n'y en pas déjà, pour ce domaine
	(echo "$1"; [ -r $override_f ] && cat $override_f) | \
	sort -u > ${override_f}.new && \
	cp ${override_f}.new ${override_f} && \
	rm ${override_f}.new
}

# la première lettre de l'avant-dernière partie du domaine (e.g.
# www.alternc.org -> a)
#
# argument: le domaine
# imprime: la lettre
init_dom_letter() {
    echo "$1" | awk '{z=split($NF, a, ".") ; print substr(a[z-1], 1, 1)}'
}


