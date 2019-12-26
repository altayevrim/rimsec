<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
class rimsec {
    private $connection = false;
    private $template = 'Your IP is banned from our servers. (<em>IP adresiniz sunucularımızdan banlandı.</em>)<hr />IP: {ip}<br /><br /><center>RimSec v1.0<br />Rimtay Yazılım</center>';
    private $ipDetails = false;
    private $sessions = true;
    private $checkTimeout = 60;
    public $log = [];
    private $showLogs = false;
    private $freepass = false;
    function __construct($settings)
    {
        if (count($settings['mysqlInfo']) != 4) {
            echo 'RimSec MYSQL Ayarları Hatalı!';
            exit;
        }

        if (in_array($this->getIP(), $settings['freepass'])) {
            $this->log[] = 'Freepass for this IP';
            $this->freepass = true;
        }
        if ($this->freepass == false) {
            try {
                $this->connection = new PDO("mysql:host=" . $settings['mysqlInfo']['host'] . ";dbname=" . $settings['mysqlInfo']['dbase'] . ";charset=utf8", $settings['mysqlInfo']['user'], $settings['mysqlInfo']['pass']);
            } catch (PDOException $e) {
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
        $this->connection = null;
        if ($this->showLogs) {
            print_r($this->log);
        }
    }

    function template($template, $type = 'html')
    {
        $this->log[] = 'template() starts';
        if ($type == 'html' && file_exists($template)) {
            $this->log[] = 'Template added: HTML';
            $this->template = file_get_contents($template);
        } elseif ($type == 'base') {
            $this->log[] = 'Template added: Base64';
            $this->template = base64_decode($template);
        }
        $this->log[] = 'template() ends successfully';
        return true;
    }

    function getIP()
    {
        $this->log[] = 'IP Get';
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
        $this->log[] = 'checkBan() starts';
        if ($ip) {
            if ($ip['active'] == 0) {
                $this->log[] = 'Not active, return false';
                return false;
            }
            if ($ip['priority'] < $priority) {
                $this->log[] = 'Priority is too low, return false';
                return false;
            }
            if ($ip['permanent'] == 0) {
                $this->log[] = 'Record is not permanent. Check ban end time';
                if (time() > strtotime($ip['banend'])) {
                    $this->log[] = 'Time is up, return false';
                    return false;
                }
            }
            $this->log[] = 'Banned';
            echo str_replace(['{ip}', '{banend}', '{reason}', '{created}'], [$ip['ip'], $ip['banend'], $ip['reason'], $ip['created']], $this->template);
            exit;
        }
    }

    function addBan($priority = 10, $permanent = 1, $banend = "2019-12-11 10:00:00",  $reason = "No reason")
    {
        if ($this->freepass) {
            return true;
        }
        $this->log[] = 'addBan() starts';
        unset($_SESSION['rimsec-ban']);
        $this->getDetails();
        $ip = $this->ipDetails;

        if ($ip) {
            $this->log[] = 'There is an old record.';
            $this->checkBan();
            $this->log[] = 'addBan() ends successfully';
            return true;
        }
        $this->log[] = 'IP Added to DB';
        $query = $this->connection->prepare("INSERT INTO ip_details SET
            ip = :ip,
            active = 1,
            permanent = :permanent,
            reason = :reason,
            created = NOW(),
            banend = :banend,
            priority = :priority
            ");
        $insert = $query->execute([
            'ip' => $this->getIP(),
            'permanent' => $permanent,
            'reason' => $reason,
            'priority' => $priority,
            'banend' => $banend
        ]);
        if ($insert) {
            unset($_SESSION['rimsec-ban']);
            // $last_id = $this->connection->lastInsertId();
            echo str_replace(['{ip}', '{banend}', '{reason}', '{created}'], [$this->getIP(), $banend, $reason, date("Y-m-d H:i:s")], $this->template);
            $this->log[] = 'addBan() ends with ban';
            exit;
        } else {
            echo 'RimSec MySQL Hatası!';
            $this->log[] = 'addBan() ends with error';
            exit;
        }
    }

    public function getDetails()
    {
        if ($this->freepass) {
            return true;
        }
        $this->log[] = 'getDetails() starts';
        if ($this->sessions) {
            $this->log[] = 'Sessions enabled';
            if (isset($_SESSION['rimsec-ban']['last_check'])) {
                if (time() - $_SESSION['rimsec-ban']['last_check'] < $this->checkTimeout) {
                    $this->log[] = 'Valid session found';
                    if ($_SESSION['rimsec-ban']['banned'] == true) {
                        $this->log[] = 'Already banned session is found';
                        if (is_array($_SESSION['rimsec-ban']['ip_details'])) {
                            $this->log[] = 'And found ip details';
                            $this->ipDetails = $_SESSION['rimsec-ban']['ip_details'];
                            $this->log[] = 'getDetails() ends successfully FROM SESSIONS';
                            return true;
                        }
                    } else {
                        $this->ipDetails = false;
                        $this->log[] = 'getDetails() ends successfully FROM SESSIONS';
                        return true;
                    }
                } else {
                    $this->log[] = 'Session lastcheck timeout ended';
                }
            }
        }
        $query = $this->connection->prepare("SELECT * FROM ip_details WHERE ip = :ip");
        $query->execute(['ip' => $this->getIP()]);
        $getDetail = $query->fetch(PDO::FETCH_ASSOC);
        if ($getDetail) {
            if ($this->sessions) {
                $this->log[] = 'Banned SESSION created';
                $_SESSION['rimsec-ban'] = [
                    'banned' => true,
                    'ip_details' => $getDetail,
                    'last_check' => time()
                ];
            }
            $this->ipDetails = $getDetail;
        } else {
            if ($this->sessions) {
                $this->log[] = 'No detail has been found SESSION created';
                $_SESSION['rimsec-ban'] = [
                    'banned' => false,
                    'last_check' => time()
                ];
            }
            $this->log[] = 'No detail has been found';
            $this->ipDetails = false;
        }
        $this->log[] = 'getDetails() ends successfully';
        return true;
    }
}
