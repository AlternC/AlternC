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

function generate_password(passwordLength, classcount) {
        passwordLength     = parseInt(passwordLength);
        if(!passwordLength)
                passwordLength = 8;

	classcount = parseInt(classcount);
	if(!classcount)
		classcount = 3;

	var numberChars = "0123456789";
	var upperChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	var lowerChars = "abcdefghiklmnopqrstuvwxyz";
	var specialchars = "(!#$%&'()*+,-./:;<=>?@[\]^_";

	if (classcount >= 4) {
		var allChars = numberChars + upperChars + lowerChars + specialchars;
	} else {
		var allChars = numberChars + upperChars + lowerChars;
	}
	var randPasswordArray = Array(passwordLength);
	randPasswordArray[0] = numberChars;
	randPasswordArray[1] = upperChars;
	randPasswordArray[2] = lowerChars;
	if (classcount == 4) {
		randPasswordArray[3] = specialchars;
		randPasswordArray = randPasswordArray.fill(allChars, 4);
	} else {
		randPasswordArray = randPasswordArray.fill(allChars, 3);
	}

	return shuffleArray(randPasswordArray.map(function(x) { return x[Math.floor(Math.random() * x.length)] })).join('');
}

function shuffleArray(array) {
  for (var i = array.length - 1; i > 0; i--) {
    var j = Math.floor(Math.random() * (i + 1));
    var temp = array[i];
    array[i] = array[j];
    array[j] = temp;
  }
  return array;
}

function generate_password_html(id, size, field1, field2, classcount) {
    $("#z"+id).html("<input id='inp"+id+
		    "' type='textbox' size=8 readonly='readonly' value='"+generate_password(size, classcount)+
		    "' />&nbsp;<a href='javascript:generate_password_html("+id+","+size+",\""+field1+"\",\""+field2+
		    "\");'><img src='/images/refresh.png' alt='Refresh' title='Refresh'/></a>");
  $("#inp"+id).focus();
  $("#inp"+id).select();
  if (field1 != "") { $(field1).val( $("#inp"+id).val() ); }
  if (field2 != "") { $(field2).val( $("#inp"+id).val() ); }
}

