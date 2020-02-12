#!/usr/bin/php -q
<?php
	require("tools.inc.php");

	$sem_key = 0x01ff;
    $shm_key = 0x01ff;
    $var_key = 0x0001;

    $sem_id = sem_get ( $sem_key );
    sem_acquire( $sem_id );

    $shm_id = shm_attach($shm_key);
    $list = @shm_get_var ( $shm_id, $var_key );

    shm_detach($shm_id);
    sem_release ($sem_id);

    if (!is_array($list) || count($list) == 0) {
        echo "empty\n";
		exit;
    }

	//echo ShArr($list);
    reset($list);
    while (list($ip, $v) = each($list)) {
        print "{$ip}\t{$v['Count']}\n";
		$pid = $v['pid'];

		reset($pid);
    	while (list($id, $date) = each($pid)) {
        	print "\t\t{$id}\t{$date}\n";
		}
    }

    exit;
?>
