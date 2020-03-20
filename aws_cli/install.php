<?php


//shell_exec('./install.sh 2>&1');
$output = shell_exec('./install.sh');
print_r($output);
print_r("\n------ End Install.php --------");
