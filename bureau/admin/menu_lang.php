<div class="menu-box">
 <div class="menu-title">
  <img src="/admin/images/lang.png" alt="<?php __("Langues"); ?>" />&nbsp;<a href="javascript:menulang_show();"><?php __("Langues"); ?></a></div>
  <div class="menu-content" id="menu-lang">
  <ul>
   <?php foreach($locales as $l) { ?>
    <li><a href="/admin/login.php?setlang=<?php echo $l; ?>" target="_top"><?php __($l); ?></a></li>
   <?php } ?>
  </ul>
 </div>
</div>
<script type="text/javascript">
  $("#menu-lang").hide();
  function menulang_show() {
    $("#menu-lang").show();
  }
</script>
