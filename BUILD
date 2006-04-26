Build instructions
==================

1- get the source

# cvs co alternc

2- get the dependencies

You probably need at least dpkg-dev, dehelper and optionally fakeroot, to
build the package as non-root.

3- build the package

Should be as simple as calling dpkg-buildpackage now. The package will
be in ../alternc_<version>_<arch>.deb

You can verify the validity of the package using:

lintian -i ../*.deb

Warning: this will print out a lot of messages, since the package is
really not clean right now.
