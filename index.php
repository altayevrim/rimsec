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
                // '127.0.0.1'
            ]
        ]
    );

    $rimsec->template('rimsec.html','html');
    
    $rimsec->checkBan();
    // echo $rimsec->getIP();
    if(isset($_GET['bruteforce'])){
        $rimsec->addBan(10, 0, '+1 day', 'He is ugly.');
        echo '<h1>Bruteforced</h1>';
        exit;
    }


    echo '<h1>You are free to go!</h1>';