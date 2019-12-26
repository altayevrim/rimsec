<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
class rimsec {
    /* 
        Enable SESSION Cache System
    */
    private $sessions = true;

    /* 
        SESSION Check Timeout in Seconds
    */
    private $checkTimeout = 2;

    /* 
        Ban after X tries
        Requires SESSIONS
    */
    private $maxBanTries = 3;

    /* 
        Try Period in Seconds
        Requires SESSIONS
    */
    private $tryPeriod = 20;

    /* 
        Show Logs
    */
    private $showLogs = true;
   
    /* 
        Default template
    */
    private $template = 'Your IP is banned from our servers. (<em>IP adresiniz sunucularımızdan banlandı.</em>)<hr />IP: {ip}<br /><br /><center>RimSec v1.0<br />Rimtay Yazılım</center>';



    /* 
        Db Connection
        Controlled by system.
    */
    private $connection = false;

    /* 
        IP Details from Db
        Controlled by system.
    */
    private $ipDetails = false;

    /* 
        System Logs
        Controlled by system.
    */
    public $log = [];

    /* 
        Freepass for IP
        Controlled by system.
    */
    private $freepass = false;

    function __construct($settings)
    {
        if (count($settings['mysqlInfo']) != 4) {
            echo 'RimSec MYSQL Ayarları Hatalı!';
            exit;
        }

        if (is_array($settings['freepass'])) {
            if (in_array($this->getIP(), $settings['freepass'])) {
                $this->log[] = 'Freepass for this IP';
                $this->freepass = true;
            }
        }
        if ($this->freepass == false) {
            try {
                $this->connection = new \PDO("mysql:host=" . $settings['mysqlInfo']['host'] . ";dbname=" . $settings['mysqlInfo']['dbase'] . ";charset=utf8", $settings['mysqlInfo']['user'], $settings['mysqlInfo']['pass']);
            } catch (\PDOException $e) {
                // print $e->getMessage();
                echo 'RimSec MySQL Hatası!';
                exit;
            }
            $this->log[] = 'MySQL Handled';
        }

        if (is_bool($settings['sessions'])) {
            $this->sessions = $settings['sessions'];
        }
        $this->getDetails();
    }

    function __destruct()
    {
        // Close PDO
        $this->connection = null;

        // Show logs if enabled
        if ($this->showLogs) {
            echo '<pre>';
            print_r($this->log);
            echo '</pre>';
        }
    }

    function template($template, $type = 'html')
    {
        $this->log[] = '--> template() starts';
        if ($type == 'html' && file_exists($template)) {
            $this->log[] = 'Template added: HTML';
            $this->template = file_get_contents($template);
        } elseif ($type == 'base') {
            $this->log[] = 'Template added: Base64';
            $this->template = base64_decode($template);
        }
        $this->log[] = '<-- template() ends successfully';
        return true;
    }

    function getIP()
    {
        $this->log[] = '--> IP Get';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    function checkBan($priority = 0)
    {
        if ($this->freepass) {
            return true;
        }
        $ip = $this->ipDetails;
        $this->log[] = '--> checkBan() starts';
        if ($ip) {
            $this->log[] = 'BANCHECK: Check Record Status';
            if ($ip['active'] == 0) {
                $this->log[] = ' - BANSTATUS: Record is not active, IP is CLEAR';
                $this->log[] = '<-- checkBan() ends with NO BAN';
                return false;
            }else{
                $this->log[] = ' - BANCHECK: Record is active';
            }
            $this->log[] = 'BANCHECK: Check Priority';
            if ($ip['priority'] < $priority) {
                $this->log[] = ' - BANSTATUS: Priority is too low, IP is CLEAR';
                $this->log[] = '<-- checkBan() ends with NO BAN';
                return false;
            }else{
                $this->log[] = ' - BANCHECK: Priority is high enough';
            }
            $this->log[] = 'BANCHECK: Check Ban Period';
            if ($ip['permanent'] == 0) {
                $this->log[] = ' - BANCHECK: Record is not permanent. Check ban end time';
                if (time() > strtotime($ip['banend'])) {
                    $this->log[] = ' -- BANSTATUS: Time is up, IP is CLEAR';
                    $this->log[] = '<-- checkBan() ends with NO BAN';
                    return false;
                }else{
                    $this->log[] = ' -- BANCHECK: IP is still banned';
                }
            }else{
                $this->log[] = ' - BANCHECK: Perm IP Ban';
            }
            $this->log[] = 'BANSTATUS: Banned';
            echo str_replace(['{ip}', '{banend}', '{reason}', '{created}'], [$ip['ip'], $ip['banend'], $ip['reason'], $ip['created']], $this->template);
            exit;
        }
        $this->log[] = '<-- checkBan() ends with clean IP';
    }

    function addBan($priority = 10, $permanent = 1, $banend = "2019-12-11 10:00:00",  $reason = "No reason")
    {
        $this->log[] = '--> addBan() starts';
        # --TODO: Check old tries, and ban accordingly
        if ($this->freepass) {
            return true;
        }

        // Session actions
        if($this->sessions){
            // If there is a detail data; erase it to get a new one.
            unset($_SESSION['rimsec-ban']['ip_details']);
            // Max tries enabled, system will ban accordingly
            if ($this->maxBanTries && is_int($this->maxBanTries)) {
                if(!isset($_SESSION['rimsec-ban']['tries']['count']) || !is_int($_SESSION['rimsec-ban']['tries']['count'])){
                    echo 'Rimsec Session problem';
                    exit;
                }else{
                    $tries = $_SESSION['rimsec-ban']['tries']['count'];
                    if($tries == 0){
                        $_SESSION['rimsec-ban']['tries']['start'] = time();
                    }

                    if(time() - $_SESSION['rimsec-ban']['tries']['start'] > $this->tryPeriod){
                        $this->log[] = 'TRIES: Try period is up. Reset tries';
                        $_SESSION['rimsec-ban']['tries']['start'] = time();
                        $_SESSION['rimsec-ban']['tries']['count'] = 0;
                        $tries = 0;
                    }
                    
                    $this->log[] = 'TRIES: Tries increased from '.$tries.' to '.($tries + 1);
                    $tries++;

                    // Not enough tries to ban
                    if($tries < $this->maxBanTries){
                        $this->log[] = 'TRIES: Not enough tries to ban';
                        $_SESSION['rimsec-ban']['tries']['count'] = $tries;
                        $this->log[] = '<-- addBan() ends with NO BAN';
                        return true;
                    }else{
                        // Will be banned, so reset the tries.
                        $this->log[] = 'TRIES: Tries reset because of upcoming BAN';
                        $_SESSION['rimsec-ban']['tries']['count'] = 0;
                        $_SESSION['rimsec-ban']['tries']['start'] = 0;
                    }
                }
            }
        }

        $query = $this->connection->prepare("SELECT * FROM ip_details WHERE ip = :ip");
        $query->execute(['ip' => $this->getIP()]);
        $ip = $query->fetch(\PDO::FETCH_ASSOC);

        // # --TODO: Check only wheter this record is active or not.
        // If the IP record is not active, but exists; don't try to re-add
        if (is_numeric($ip['id']) && $ip['active'] == 0) {
            $this->log[] = 'DB: There is an old INACTIVE Record in DB.';
            // $this->checkBan();
            $this->log[] = '<-- addBan() ends successfully';
            return true;
        }

        $banend = date("Y-m-d H:i:s", strtotime($banend));

        # --TODO: If there is a record, update that.
        if(is_numeric($ip['id'])){
            $query = $this->connection->prepare("UPDATE ip_details SET
                ip = :ip,
                active = 1,
                permanent = :permanent,
                reason = :reason,
                created = NOW(),
                banend = :banend,
                priority = :priority
                WHERE id = :id");
            $update = $query->execute([
                'ip' => $this->getIP(),
                'permanent' => $permanent,
                'reason' => $reason,
                'priority' => $priority,
                'banend' => $banend,
                'id' => $ip['id']
            ]);
            $this->log[] = 'DB: IP Updated From DB';
        }else{
            $this->log[] = 'DB: IP Added to DB';
            $query = $this->connection->prepare("INSERT INTO ip_details SET
                ip = :ip,
                active = 1,
                permanent = :permanent,
                reason = :reason,
                created = NOW(),
                banend = :banend,
                priority = :priority");
            $insert = $query->execute([
                'ip' => $this->getIP(),
                'permanent' => $permanent,
                'reason' => $reason,
                'priority' => $priority,
                'banend' => $banend
            ]);
        }
        if ($insert || $update) {
            unset($_SESSION['rimsec-ban']['ip_details']);
            // $last_id = $this->connection->lastInsertId();
            echo str_replace(['{ip}', '{banend}', '{reason}', '{created}'], [$this->getIP(), $banend, $reason, date("Y-m-d H:i:s")], $this->template);
            $this->log[] = '<-- addBan() ends with ban';
            exit;
        } else {
            echo 'RimSec MySQL Hatası!';
            $this->log[] = '<-- addBan() ends with error';
            exit;
        }
    }

    public function getDetails()
    {
        if ($this->freepass) {
            return true;
        }
        $this->log[] = '--> getDetails() starts';

        // Session Actions
        if ($this->sessions) {
            $this->log[] = 'SESSION: Sessions are enabled';
            
            $rimsecSession = $_SESSION['rimsec-ban'];

            // Create a session variable to check tries if does not exist
            if($this->maxBanTries && is_int($this->maxBanTries)){
                if(!isset($rimsecSession['tries']['count'])){
                    $_SESSION['rimsec-ban']['tries']['count'] = 0;
                }
            }

            // There is a lastcheck value
            if (isset($rimsecSession['last_check']) && $rimsecSession['ip'] == $this->getIP()) {

                // Last check valid
                if (time() - $rimsecSession['last_check'] < $this->checkTimeout && is_array($rimsecSession['ip_details'])) {
                    $this->log[] = 'SESSION: Valid session found';

                    // IP Banned
                    if ($rimsecSession['banned'] == true) {
                        $this->log[] = 'SESSION: Found Banned';
                        
                        // There is IP details
                        if (is_array($rimsecSession['ip_details'])) {
                            $this->log[] = 'SESSION: Found IP Details';
                            $this->ipDetails = $rimsecSession['ip_details'];
                            $this->log[] = '<-- getDetails() ends successfully FROM SESSIONS';
                            return true;
                        }
                    } else {
                        // IP is not banned, but no need to check the Db again
                        $this->ipDetails = false;
                        $this->log[] = '<-- getDetails() ends successfully FROM SESSIONS';
                        return true;
                    }
                } else {
                    // Time ended
                    $this->log[] = 'SESSION: Lastcheck Timeout';
                }
            }else{
                $this->log[] = 'SESSION: IP Change.';
            }
        }
        $query = $this->connection->prepare("SELECT * FROM ip_details WHERE ip = :ip");
        $query->execute(['ip' => $this->getIP()]);
        $getDetail = $query->fetch(\PDO::FETCH_ASSOC);
        if ($getDetail) {
            if ($this->sessions) {
                $this->log[] = 'SESSION: Created For Banned IP';
                $_SESSION['rimsec-ban']['banned'] = true;
                $_SESSION['rimsec-ban']['ip_details'] = $getDetail;
                $_SESSION['rimsec-ban']['last_check'] = time();
                $_SESSION['rimsec-ban']['ip'] = $this->getIP();
            }
            $this->ipDetails = $getDetail;
        } else {
            if ($this->sessions) {
                $this->log[] = 'SESSION: Created For Clean IP';
                $_SESSION['rimsec-ban']['banned'] = false;
                $_SESSION['rimsec-ban']['ip_details'] = false;
                $_SESSION['rimsec-ban']['last_check'] = time();
                $_SESSION['rimsec-ban']['ip'] = $this->getIP();
            }
            $this->log[] = 'DB: IP is Clean';
            $this->ipDetails = false;
        }
        $this->log[] = '<-- getDetails() ends successfully';
        return true;
    }
}
