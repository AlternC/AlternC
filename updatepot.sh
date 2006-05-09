#!/bin/sh
#
# Met a jour le fichier pot.
# 
cd bureau
i="main"

xgettext admin/*.php class/*.php -d${i}_tmp -plocales/ -Lphp -k_ -k__ --from-code=iso-8859-1 --msgid-bugs-address="i18n@alternc.org"
YEAR=`date +%Y`
FULLDATE=`date +"%Y-%m-%d %H:%M %Z"`
cat locales/${i}_tmp.po | sed -e "s/# SOME DESCRIPTIVE TITLE./# AlternC's Translation/" | sed -e "s/YEAR THE PACKAGE'S COPYRIGHT HOLDER/$YEAR AlternC's translation team <i18n@alternc.org>/" | sed -e "s/This file is distributed under the same license as the PACKAGE package./This file is distributed under the same license as the AlternC's package./" | sed -e "s/FIRST AUTHOR <EMAIL@ADDRESS>, YEAR./AlternC's translation team <i18n@alternc.org>/" | sed -e 's/Project-Id-Version: PACKAGE VERSION/$id$/' | sed -e "s/PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE/PO-Revision-Date: $FULLDATE/" | sed -e "s/Language-Team: LANGUAGE <LL@li.org>/Language-Team: Team AlternC <i18n@alternc.org>/" | sed -e 's/Content-Type: text\/plain; charset=CHARSET/Content-Type: text\/plain; charset=ISO-8859-1/' | sed -e 's/Last-Translator: FULL NAME <EMAIL@ADDRESS>/Last-Translator: Team AlternC <i18n@alternc.org>/'  >locales/${i}.pot
rm locales/${i}_tmp.po

