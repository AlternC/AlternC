/*
 $Id: quota_get.c,v 1.1 2003/03/27 00:42:19 benjamin Exp $
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
 Purpose of file: Obtention des quotas disque, fronted securise a quota.
 ----------------------------------------------------------------------
*/
#include <stdio.h>
#include <stdlib.h>

int main(int argc,char *argv[]) {
  unsigned int uid,size;
  int i,c;
  char res[255];
  if (argc<2) {
    printf("Usage : quota_get uid\n");
    exit(-1);
  }
  i=atoi(argv[1]);
  if (i<1000) {
    printf("uid must be > 1000 !\n");
    exit(-1);
  }

  setuid(geteuid());
  sprintf(res,"/usr/lib/alternc/quota_get.sh %d",i);
  system(res);
  exit(0);
}
