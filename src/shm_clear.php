#!/usr/bin/php -q
<?php
    $sem_key = 0x01ff;
    $shm_key = 0x01ff;
    $var_key = 0x0001;

    $sem_id = sem_get ( $sem_key );
    sem_acquire( $sem_id );

    $shm_id = shm_attach($shm_key);
    shm_remove ($shm_id);

    sem_release ($sem_id);
    exit;
?>
