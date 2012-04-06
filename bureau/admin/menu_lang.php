<div class="menu-box">
 <div class="menu-title">
  <img src="/images/lang.png" alt="<?php __("Langues"); ?>" />&nbsp;<a href="javascript:menu_toggle('menu-lang');"><?php __("Langues"); ?>
  <img src="/images/row-down.png" alt="" style="float:right;"/></a>
  </div>
  <div class="menu-content" id="menu-lang">
  <ul>
   <?php foreach($locales as $l) { ?>
    <li><a href="/login.php?setlang=<?php echo $l; ?>" target="_top"><?php __($l); ?></a></li>
   <?php } ?>
  </ul>
 </div>
</div>
<script type="text/javascript">
  menu_toggle('menu-lang');
</script>
