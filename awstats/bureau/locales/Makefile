all: aws_manual.pot aws.pot */LC_MESSAGES/aws_manual.po */LC_MESSAGES/aws.po

aws.pot: ../*/*.php
	[ -r $@ ] || touch $@
	xgettext --copyright-holder="AlternC Team" --package-name="AlternC" --package-version="2.0" --msgid-bugs-address="i18n@alternc.org"  --force-po -o $@ --keyword=__ --keyword=_  -L PHP -F --from-code UTF-8 $^

%/LC_MESSAGES/aws.po: aws.pot
	msgmerge -v -U $@ $^

%/LC_MESSAGES/aws_manual.po: aws_manual.pot
	msgmerge -v -U $@ $^
