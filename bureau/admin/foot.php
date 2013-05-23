
</div> <!-- div content -->
<div style="clear:both;" ></div>
</div> <!-- div global -->
<?php 
if ( isset($debug_alternc) && $debug_alternc->status ) {
  $debug_alternc->dump();
}
?>
</body>
</html>
<?php 
exit(); // case of include('foot.php');
?>
