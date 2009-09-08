var menulist = new Array();

/*
menulist[0] = "menu-dom";
menulist[1] = "menu-mail";
menulist[2] = "menu-ftp";
menulist[3] = "menu-sql";
menulist[4] = "menu-adm";
*/

var menu_opened = "";

function deploy(menu)
{
	for (var i = 0; i < menulist.length; i++)
	{
		if (checkObj(menulist[i]))
		{
			var div_content = new getObj(menulist[i]);
			var div_img = new getObj("img-" + menulist[i]);

			div_content.style.display = "none";
			div_img.obj.src = "/admin/images/plus.png";
		}
	}

	if (menu == 0)
		return;

	var div_content = new getObj(menu);
	var div_img = new getObj("img-" + menu);

	if (menu_opened == menu)
	{
		div_content.style.display = "none";
		div_img.obj.src = "/admin/images/plus.png";
		menu_opened = "";
	}
	else
	{
		div_content.style.display = "block";
		div_img.obj.src = "/admin/images/minus.png";
		menu_opened = menu;
	}
}

function getObj(name)
{
	if (document.getElementById)
	{
		this.obj = document.getElementById(name);
		this.style = document.getElementById(name).style;
	}
		else if (document.all)
	{
		this.obj = document.all[name];
		this.style = document.all[name].style;
	}
		else if (document.layers)
	{
		this.obj = document.layers[name];
		this.style = document.layers[name];
	}
}

function checkObj(name)
{
	if (document.getElementById)
	{
		 if (document.getElementById(name))
			 return true;
	}
		else if (document.all)
	{
		if (document.all[name])
			return true;
	}
		else if (document.layers)
	{
		if (document.layers[name])
			return true;
	}
	return false;
}

function help(hid) {
	var top=100; /* (10-screen.height); */
	var left=100; /*(10-screen.width); */
	var largeur=700;
	var hauteur=550;
	window.open('./aide/help.php?hid='+hid,'help','top='+top+',left='+left+',width='+largeur+',height='+hauteur+',scrollbars=yes');
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

