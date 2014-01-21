<?php
if ( (extension_loaded('xhprof')) && DO_XHPROF_STATS ) {
    include_once '/usr/share/php/xhprof_lib/utils/xhprof_lib.php';
    include_once '/usr/share/php/xhprof_lib/utils/xhprof_runs.php';
    xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
}
?>
