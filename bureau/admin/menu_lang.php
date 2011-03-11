<div class="menu-box">
 <div class="menu-title">
  <img src="/admin/images/lang.png" alt="<?php __("Langues"); ?>" />&nbsp;<?php __("Langues"); ?></div>
 <div class="menu-content" id="menu-lang">
  <ul>
   <?php foreach($locales as $l) { ?>
    <li><a href="/admin/login.php?setlang=<?php echo $l; ?>" target="_top"><?php __($l); ?></a></li>
   <?php } ?>
  </ul>
 </div>
</div>

