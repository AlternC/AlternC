<?php

require_once("../class/config_nochk.php");

$request = FALSE;
$valid_request = TRUE;
if (isset($_REQUEST['name_or_email'])) {
    $request = TRUE;
    // Inserted into the global namespace by config.php
    $valid_request = !$fatalcsrf;
    if ($fatalcsrf) {
        $msg->raise('ERROR', _('Failed to validate CSRF token'));
    }
}


// Show the form if nothing was submitted, or if what was submitted is not
// a valid request (eg. doesn't pass CSRF).
$show_form =  !$request || ($request && !$valid_request);

if ($request && $valid_request) {
    $mem->send_reset_url($_REQUEST['name_or_email']);
}

if (!isset($charset) || ! $charset) {
    $charset="UTF-8";
}

@header("Content-Type: text/html; charset=$charset");
require_once("html-head.php");
?>

<body class="login_page">
    <div id="global">
        <div id="content">
            <?php
            // Getting logo. C.f. admin/index.php
            $logo = variable_get('logo_login', '' ,'You can specify a logo for the login page, example /images/my_logo.png .', array('desc'=>'URL','type'=>'string'));
            if ( empty($logo) ||  ! $logo ) {
                $logo = 'images/logo.png';
            }
            ?>
            <p id='logo'>  <img src="<?php echo $logo; ?>" border="0" height="100px" alt="<?php __("Web Hosting Control Panel"); ?>" title="<?php __("Web Hosting Control Panel"); ?>" />
            </p>
            <p>&nbsp;</p>
            <?php echo $msg->msg_html_all(); ?>
            <br/>
            <div class="block_list">
              <?php if ($show_form): ?>
                <div class="block_login_page">
                        <div class="menu-box">
                            <div class="menu-title"><?php echo _('Password reset'); ?></div>
                            <form action="request_reset.php" method="post" name="passwordreset">
                                <?php csrf_get(); ?>
                                <div>
                                    <label for="name_or_email"><?php echo _('Username or e-mail'); ?></label>
                                    <input type="text" class="int" name="name_or_email">
                                </div>
                                <div class="submit"><input type="submit" class="inb" name="submit"></div>
                            </form>
                        </div>
                </div>
                <div class="block_list">
                  <p><?php echo _('An e-mail with instructions will be sent'); ?></p>
                </div>
              <?php else: ?>
                <div><p><a href="index.php"><?php __('Return to login page'); ?></a></p></div>
              <?php endif; ?>
            </div>
        </div>
    </div>
</body>
