/*
 $Id: mem_del.c,v 1.1 2003/03/27 00:42:19 benjamin Exp $
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
 Original Author of file: Benjamin Sonntag - 2002/02/01
 Purpose of file: Destruction d'un dossier membre.
 ----------------------------------------------------------------------
*/
#include <stdio.h>
#include <stdlib.h>

int main(int argc,char *argv[])
{
	char mail[64],s[255];

  if (argc!=2)
    {
      printf("Utilisation : %s member-name \n  ",argv[0]);
	  printf("Detruit le membre 'member-name'\n");
      exit(-1);
    }
	strncpy(mail,argv[1],64);
	mail[64]=0;
	sprintf(s,"/bin/rm -rf '/var/alternc/html/%c/%s'",mail[0],mail);

	setuid(geteuid());
		/*************************/
		/* WARNING : ROOT ZONE ! */
		/*************************/
	system(s);
	exit(0);
}

