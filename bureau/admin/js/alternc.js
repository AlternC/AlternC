var menulist = new Array();

var menu_opened = "";

function help(hid) {
	var top=100; /* (10-screen.height); */
	var left=100; /*(10-screen.width); */
	var largeur=800;
	var hauteur=700;
	window.open('./aide/help.php?hid='+hid,'help','top='+top+',left='+left+',width='+largeur+',height='+hauteur+',scrollbars=yes');
}

function CheckAll() {
    chk=document.getElementById('checkall').checked;
    for (var i = 0; i < document.main.elements.length; i++) {
	if(document.main.elements[i].type == 'checkbox'){
	    document.main.elements[i].checked = chk;
	}
    }
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
  $("#"+id).toggle(200, function() {
    var tmpi = {};
    // Animation complete.
    if ($("#"+id).is(":hidden")) {
      $("#"+id+"-img").attr("src","images/menu_plus.png");
      tmpi[''+id] = 'hidden';
    } else {
      $("#"+id+"-img").attr("src","images/menu_moins.png");
      tmpi[''+id] = 'visible';
    }
    $.post('tempovars.php', { 'key' : 'menu_toggle', 'val' : tmpi })
  });

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
  $("#z"+id).html("<input id='inp"+id+"' type='textbox' size=8 readonly='readonly' value='"+generate_password(size)+"' />&nbsp;<a href='javascript:generate_password_html("+id+","+size+",\""+field1+"\",\""+field2+"\");'><img src='/images/refresh.png' alt='Refresh' title='Refresh'/></a>");
  $("#inp"+id).focus();
  $("#inp"+id).select();
  if (field1 != "") { $(field1).val( $("#inp"+id).val() ); }
  if (field2 != "") { $(field2).val( $("#inp"+id).val() ); }
}

