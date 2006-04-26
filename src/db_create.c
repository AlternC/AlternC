/*
 $Id: db_create.c,v 1.1 2003/03/27 00:42:19 benjamin Exp $
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2002 by the AlternC Development Team.
 http://alternc.org/
 ----------------------------------------------------------------------
 Based on:
 Valentin Lacambre's web hosting softwares: http://altern.org/
 ----------------------------------------------------------------------
 LICENSE

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License (GPL)
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 To read the license please visit http://www.gnu.org/copyleft/gpl.html
 ----------------------------------------------------------------------
 Original Author of file: Benjamin Sonntag - 2002/06/22
 Purpose of file: Change the owner / mod of a newly created db
 ----------------------------------------------------------------------
*/
#include <stdio.h>
#include <stdlib.h>
#include <sys/types.h>
#include <sys/quota.h>

int main(int argc,char *argv[])
{
	unsigned int uid,size;
	int i;
	char res[255];
	struct dqblk addr;

  if (argc!=3)
    {
      printf("Utilisation : %s uid login\n  ",argv[0]);
      printf("Change le possesseur et le groupe de la base mysql 'login' pour le groupe 'uid'\n");
      exit(-1);
    }
	uid=atoi(argv[1]);
	if (!uid)
		exit(-1);

	setuid(geteuid());
		/*************************/
		/* WARNING : ROOT ZONE ! */
		/*************************/
	chown(argv[2],-1,uid);
	chmod(argv[2],02770);
	exit(0);
}

