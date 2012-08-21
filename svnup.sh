#!/bin/bash

pushd ../..

for i in alternc alternc-bounces alternc-jabber alternc-munin alternc-procmail alternc-stats alternc-apps alternc-changepass alternc-mailman alternc-philesight alternc-secondarymx alternc-sympa alternc-awstats alternc-doc alternc-mergelog alternc-phpcron alternc-slavedns alternc-webalizer
do
    if [ -d "$i" ]; then
	pushd $i
	svn up 
	popd
    fi
done

popd
