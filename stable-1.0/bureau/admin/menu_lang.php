<div class="menu-box">
 <div class="menu-title">
  <img src="/admin/images/lang.png" alt="<?php __("Langues"); ?>" />&nbsp;<a href="javascript:menulang_toggle();"><?php __("Langues"); ?></a></div>
  <div class="menu-content" id="menu-lang">
  <ul>
   <?php foreach($locales as $l) { ?>
    <li><a href="/admin/login.php?setlang=<?php echo $l; ?>" target="_top"><?php __($l); ?></a></li>
   <?php } ?>
  </ul>
 </div>
</div>
<script type="text/javascript">
  function menulang_toggle() {
    $("#menu-lang").toggle();
  }
  menulang_toggle();
</script>
