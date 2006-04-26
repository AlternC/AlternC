function help(hid) {
	var top=100; /* (10-screen.height); */
	var left=100; /*(10-screen.width); */
	var largeur=700;
	var hauteur=550;
	window.open('/admin/aide/help.php?hid='+hid,'help','top='+top+',left='+left+',width='+largeur+',height='+hauteur);
}

function browseforfolder(caller) {
        eval("file=document."+caller+".value");
    w=window.open("browseforfolder.php?caller="+caller+"&file="+file,"browseforfolder","width=300,height=400,scrollbars,left=100,top=100");
}

function CheckAll() {
  var fi = 1;
  for (var i = 0; i < document.main.elements.length; i++) {
    if(document.main.elements[i].type == 'checkbox'){
	if (fi) {
		fi=0;
		chk=!document.main.elements[i].checked;
	}
      document.main.elements[i].checked = chk;
    }
  }
}

