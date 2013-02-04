<div class="menu-box">
  <a href="javascript:menu_toggle('menu-lang');">
   <div class="menu-title">
    <img src="/images/lang.png" alt="<?php __("Languages"); ?>" />&nbsp;<?php __("Languages"); ?>
    <img src="/images/menu_moins.png" alt="" style="float:right;" id="menu-lang-img"/>
   </div>
  </a>
  <div class="menu-content" id="menu-lang">
  <ul>
   <?php foreach($locales as $l) { ?>
    <li><a href="/login.php?setlang=<?php echo $l; ?>" target="_top"><?php if (isset($lang_translation[$l])) echo $lang_translation[$l]; else echo $l; ?></a></li>
   <?php } ?>
  </ul>
 </div>
</div>
<script type="text/javascript">
  menu_toggle('menu-lang');
</script>
