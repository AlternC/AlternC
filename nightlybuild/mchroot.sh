#! /bin/bash

CHROOT_DIR="/root/chroot"
BUILD_AREA="/root/build-area"
SRC_DIR="/root/src"

SOURCES[0]='svn https://www.alternc.org/svn/ /home/root/test/'
#SOURCES[1]='vcs url_ressource target_directory'

function get_sources() {
	CHROOT=${1:-"etch-i386"}
	ELEMENTS=${#SOURCES[@]}
	for ((i=0;i<$ELEMENTS;i++)); do
		SOURCE=( `echo ${SOURCES[${i}]}` )
		VCS=${SOURCE[0]}
		SOURCE=${SOURCE[1]}
		TARGET=${SOURCE[2]}
		chroot_run $CHROOT "mkdir -p $TARGET" '/root/'
		get_$VCS $CHROOT $SOURCE $TARGET
	done
}

function get_svn() {
	chroot_run ${1} "svn cleanup ${3}" ${3}
	command="echo t |svn --force --no-auth-cache co ${2} ${3}"
	chroot_run "$1" "$command" '/root/'
}

function chroot_run() {
	SCHROOT_SESSION="${1}"
	COMMAND="${2}"
	DIR="${3}"

	echo "$COMMAND" | \
	schroot \
		-p \
		-r \
		--chroot $SCHROOT_SESSION \
		-d $DIR \
#		-- "${COMMAND}"


}

function create_packages() {
	rm -r $BUILD_AREA

	for dir in $(ls $CHROOT_DIR); do
        	if [[ ! -d $CHROOT_DIR/$dir ]]; then
                	continue
	        fi
	        dist=$(echo $dir | sed 's/-.*//' )
	        arch=$(echo $dir | sed 's/.*-//' )

#		if [[ $dist != 'squeeze' ]]; then
#			continue
#		fi
	
		#Ouvrir un chroot
		SCHROOT_SESSION=$(schroot -b -c $dir)
		if [[ ! $SCHROOT_SESSION ]]; then
			continue
		fi
	
		CHROOT_SRC=$CHROOT_DIR/$dist-$arch$SRC_DIR
		CHROOT_BUILD_AREA=$CHROOT_DIR/$dist-$arch$BUILD_AREA

		mkdir -p $BUILD_AREA/$dist-$arch
		mkdir -p $CHROOT_SRC
		mkdir -p $CHROOT_BUILD_AREA

#		mount --bind $SRC_DIR $CHROOT_SRC
#		mount --bind $BUILD_AREA/$dir $CHROOT_BUILD_AREA

		#Trouver les paquets
		for paquet in $(find /root/src/alternc-all/ -ipath \*/debian -printf %h\\n); do
			SVN_DIR=$paquet
			STATUT=$(basename $SVN_DIR)

			if [[ $STATUT != "trunk" ]]; then
				STATUT=$(basename $(dirname $SVN_DIR))
			else
				echo "dch -l \"`date +%Y-%m-%d`\" nightly" | \
				schroot \
					-r \
					--chroot $SCHROOT_SESSION \
					-d $SVN_DIR \
					-p
			fi

			continue

			#Construire le package				
			echo $STATUT
			mkdir -p $CHROOT_BUILD_AREA/$STATUT

			echo "svn-buildpackage -us -uc -rfakeroot --svn-move-to=$BUILD_AREA/$STATUT" | \
			schroot \
				-r \
				--chroot $SCHROOT_SESSION \
				-d $SVN_DIR \
				-p

			echo "svn revert * -R" | \
			schroot \
				-r \
				--chroot $SCHROOT_SESSION \
				-d $SVN_DIR \
				-p

			

		done

		#Fermer le chroot
		schroot -e \
			--chroot=$SCHROOT_SESSION

#		umount $CHROOT_SRC
#		umount $CHROOT_BUILD_AREA

	done;

	#Nettoyer les build-area dans les sources
	find $SRC_DIR -iname build-area -exec rm -r {} \;
}

function create_apt() {
	#CrÃ©ation du depot

	DEPOT_DIR="/root/depot"

	for dir in $(ls $CHROOT_DIR); do
        	if [[ ! -d $CHROOT_DIR/$dir ]]; then
                	continue
	        fi
        	dist=$(echo $dir | sed 's/-.*//' )
	        arch=$(echo $dir | sed 's/.*-//' )

		DEPOT_DIST=$DEPOT_DIR/dists/$dist

        	CHROOT_BUILD_AREA=$BUILD_AREA/$dist-$arch

		for dir in $(ls $CHROOT_BUILD_AREA); do 	

			echo $dir

			DEPOT_SRC=$DEPOT_DIST/$dir/source
			DEPOT_BIN=$DEPOT_DIST/$dir/binary-$arch/

			mkdir -p $DEPOT_SRC
			mkdir -p $DEPOT_BIN

			cd $CHROOT_BUILD_AREA/$dir
			cp *.dsc $DEPOT_BIN
			cp *.deb $DEPOT_BIN

			cp *.dsc $DEPOT_SRC
			cp *.diff.gz $DEPOT_SRC
			cp *.tar.gz $DEPOT_SRC

			cd $DEPOT_DIST/$dir/
			dpkg-scanpackages binary-$arch /dev/null dists/$dist/$dir/ | gzip -f9 > binary-$arch/Packages.gz
			dpkg-scansources source /dev/null dists/$dist/$dir/ | gzip -f9 > source/Sources.gz
			apt-ftparchive -c /root/$dist-$arch-apt-ftparchive.conf release $DEPOT_BIN > $DEPOT_BIN/Release
			apt-ftparchive -c /root/$dist-$arch-apt-ftparchive.conf release $DEPOT_SRC > $DEPOT_SRC/Release
		done
	done
}

#get_sources
create_packages
#create_apt
