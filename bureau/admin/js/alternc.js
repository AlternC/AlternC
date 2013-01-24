var menulist = new Array();

/*
menulist[0] = "menu-dom";
menulist[1] = "menu-mail";
menulist[2] = "menu-ftp";
menulist[3] = "menu-sql";
menulist[4] = "menu-adm";
*/

var menu_opened = "";

/*
function deploy(menu)
{
	for (var i = 0; i < menulist.length; i++)
	{
		if (checkObj(menulist[i]))
		{
			var div_content = new getObj(menulist[i]);
			var div_img = new getObj("img-" + menulist[i]);

			div_content.style.display = "none";
			div_img.obj.src = "/images/plus.png";
		}
	}

	if (menu == 0)
		return;

	var div_content = new getObj(menu);
	var div_img = new getObj("img-" + menu);

	if (menu_opened == menu)
	{
		div_content.style.display = "none";
		div_img.obj.src = "/images/plus.png";
		menu_opened = "";
	}
	else
	{
		div_content.style.display = "block";
		div_img.obj.src = "/images/minus.png";
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
*/

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
		alert("Please contact you administrator (the method 'browseforfolderdeprecated' is normaly deprecated).");
}

function CheckAll() {
    chk=document.getElementById('checkall').checked;
    for (var i = 0; i < document.main.elements.length; i++) {
	if(document.main.elements[i].type == 'checkbox'){
	    document.main.elements[i].checked = chk;
	}
    }
}

function hide(s) {
    if (document.all) {
        if (document.all[s]) {
            document.all[s].visibility="invisible";
            eval("document.all."+s+".style.display=\"none\"");
        }
    } else {
        if (document.getElementById(s)) {
            document.getElementById(s).visibility="invisible";
            document.getElementById(s).style.display="none";
        }
    }
}

/* Affiche le composant s */
function show(s,shm) {
    if (!shm) shm="block";
    if (document.all) {
        if (document.all[s]) {
            document.all[s].visibility="visible";
            eval("document.all."+s+".style.display=\""+shm+"\"");
        }
    } else {
        if (document.getElementById(s)) {
            document.getElementById(s).visibility="visible";
            document.getElementById(s).style.display=shm;
        }
    }
}
/* Affiche / Cache le composant s */
function swap(s,shm) {
    if (document.all) {
        if (document.all[s]) {
            if (document.all[s].visibility=="visible") {
                hide(s);
            } else {
                show(s,shm);
            }
        }
    } else {
        if (document.getElementById(s)) {
            if (document.getElementById(s).visibility=="visible") {
                hide(s);
            } else {
                show(s,shm);
            }
        }
    }
}

/**
* Function check_form_mail_validity
* is used to check if a given mail is a valid RFC 2822 mail adress and set the according image onto the page.
* @param : id_elem , id of the mail input box we are checking
*/
function check_mail_form_validity(id_elem) {
  var mail = document.getElementById('rcp-'+id_elem).value;
  var mail_element = document.getElementById('rcp-'+id_elem);
  var src = "";
  var alt = "";

  if (mail != "" ) {
    if(is_valid_mail(mail_element.value) != true ){
      src = "images/check_no.png";
      alt = "KO";
    } else {
      src ="images/check_ok.png";
      alt ="OK";
    }
  } 

  document.getElementById('valid-rcp-'+id_elem).src = src;
  document.getElementById('valid-rcp-'+id_elem).alt = alt;
}

/*
* Function :is_valid_mail
* @param : interger arg, an RFC 2822 mail adress
* @return : true if arg really is formed like described in RFC 2822, else false
* FIXME: does this function is used anywhere ? if yes, remove it, see http://www.bortzmeyer.org/arreter-d-interdire-des-adresses-legales.html
*/
function is_valid_mail(arg) {
  //FIXME mail documentation doesn't expect a maximum length of the mail address : http://tools.ietf.org/html/rfc2822#section-3.4.1
  var rgxp = /^[a-z0-9\!\#\$\%\&\'\*+/=?^_`{|}~-]{1,}((\.[a-z0-9\!\#\$\%\&\'\*+/=?^_`{|}~-]{1,13})?)+@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9\-]*[a-z0-9])?$/
    if(rgxp.test(arg) == false) {
	return false;
    } else {
	return true;
    }

}

function menu_toggle(id) {
  $("#"+id).toggle();
}

function false_if_empty(id,err_msg) {
  if ( $("#"+id).val() == '' ) {
    alert(err_msg);
    return false;
  }
}

function generate_password(len){
	len	= parseInt(len);
	if(!len)
		len = 8;
	var password = "";
	var chars    = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	var charsN   = chars.length;
	var nextChar;
 
	for(i=0; i<len; i++){
		nextChar = chars.charAt(Math.floor(Math.random()*charsN));
		password += nextChar;
	}
	return password;
}

function generate_password_html(id, size, field1, field2) {
  $("#"+id).html("<input id='inp"+id+"' type='textbox' size=8 readonly='readonly' value='"+generate_password(size)+"' />&nbsp;<a href='javascript:generate_password_html("+id+","+size+",\""+field1+"\",\""+field2+"\");'><img src='/images/refresh.png' alt='Refresh'/></a>");
  $("#inp"+id).focus();
  $("#inp"+id).select();
	if (field1 != "") { $(field1).val( $("#inp"+id).val() ); }
	if (field2 != "") { $(field2).val( $("#inp"+id).val() ); }
}

