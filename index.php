<?php   
    error_reporting(E_ALL);
    require 'class.rimsec.php';

    $rimsec = new rimsec(
        [
            'mysqlInfo' => [
                'host' => 'localhost', 
                'dbase' => 'rimsec', 
                'user' => 'root', 
                'pass' => 'toor'
            ],
            'freepass' => [
                '127.0.0.1'
            ]
        ]
    );
    // echo $rimsec->getIP();
    $rimsec->addBan(12);
    
    echo '<h1>You are free to go!</h1>';