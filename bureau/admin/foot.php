
 </div>
</td>
</tr>
</table>
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
