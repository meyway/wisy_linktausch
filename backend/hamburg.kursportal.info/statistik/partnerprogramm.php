<?php
    /*
     * F†R PHP 5.3.26, Autor: jm@meyway.com, Stand: 02/2015
     * 
     * Diese Datei wertet die Zugriffe auf Hamburg Kursportal WISY aus, die auf "#partner" enden,
     * d.h. die Ÿber Partnern erfolgen, die unseren Banner in deren Homepage eingebaut haben.
     * 
     * Aufgezeichent werden Domain und Zugriffsdatum (d.m.Y).
     * 
     * Ca. 1x pro Monat wird dies ausgewertet an WHSB gesendet. Vgl. Cron-Job im DF-Adminbereich!
     * 
     * GET-Parameter:
     * type:[partner|NULL], referrer:[<Domain>|NULL], actions:[do_sort[,do_record[,do_inform[,NULL]]]
     * 
     */    
    error_reporting(E_ALL);
    
    define("URL_THIS", "http://hamburg.kursportal.info/files/hh/linktausch/statistik/partnerprogramm.php");
    
    define("MAIL_FROM", "nobody@hamburg.kursportal.info");
    define("MAIL_TO", ""); // E-Mailadresse ergänzen!
    define("MAIL_BCC", "");
    define("MAIL_SUBJECT", "HH Kursportal WISY // Monatliche Auswertung: Linktausch-Banner");    
    define("MAIL_SIGNATURE", "\n\n\n-- \nDiese E-Mail wurde automatisch erzeugt von\n/files/hh/linktausch/statistik/partnerprogramm.php\nFragen: ".MAIL_BCC);    
    
    define("ZUGRIFFE_SORT_BY", "Domain");
    
    $partnerprogramm = new Partnerprogramm();    
        
    class Partnerprogramm {
        
        private $type = "";
        private $referrer = "";
        private $actions = "";
        
        private $zugriffe_filename = "zugriffe.dat";
        private $anonyme_domain = "Anonym";
        private $sortZugriffeBy = "Domain";
        
        
        function __construct () {
            $this->eval_env();
            
            $fh = new FileHandler($this->zugriffe_filename);
            
            $zugriffe = $fh->read();
            
            if($zugriffe != "" && !is_array($zugriffe)) {
                die("Bisherige Zugriffe konnten nicht eingelesen werden!");
            }
            
            // Initialiserung bei Zählungsbeginn (leere Datei)
            if($zugriffe == "") {
                $zugriffe = array();
            }
            
            // AKTUELLEN ZUGRIFF SPEICHERN
            $neuer_zugriff = array("Domain" => $this->referrer, "Timestamp" => date("d.m.Y"));

            if(!$this->do_graph()) {
                array_push($zugriffe, $neuer_zugriff);
            }

            if($this->do_sort() || $this->do_graph()) {            
                $this->zugriffe_sortieren($zugriffe);
            }
            
            if($this->do_record()) {    
                $fh->write($zugriffe);
            }
            
            if($this->do_graph()) {
                $this->output_eventdrops($zugriffe);
            }
            
            // ADMINS †BER ERGEBNISSE INFORMIEREN        
            if($this->do_inform()) {
                $informer = new Informer($this->actions);

                if(!$informer->sendMail($informer->generate_zugriffsauswertung($zugriffe))) {
                    die("Fehler beim Mailversand!");
                }
            }
            
            if(!$this->do_graph()) {
                echo("Done.");
            }
        
        } // Ende: __construct
        
        
        private function eval_env() {
            $this->evalType();
            $this->evalReferrer();
            $this->evalUserAgent();
            $this->evalActions();
        }
    
        
        private function setType() {
            $this->type = trim(urldecode($_GET['type']));
        }
        
        private function getType() {
            return $this->type;
        }
        
        private function evalType() {
            $this->setType();
            
            if($this->getType() != "partner") {
                die("Falscher Partner-Typ!");
            }
        }
        
        private function setReferrer() {
            $this->referrer = trim(urldecode($_GET['referrer']));
        }
        
        private function getReferrer() {
            return $this->referrer;
        }
        
        private function evalReferrer() {
            $this->setReferrer();
            
            if($this->getReferrer() == "") {
                $this->referrer = $this->anonyme_domain;
            }
        }
        
        private function evalUserAgent() {
            // Robots raus
            if(!empty($_SERVER['HTTP_USER_AGENT']) && preg_match('~(bot|crawl)~i', $_SERVER['HTTP_USER_AGENT'])){
                die("Wir m&uuml;ssen drau&szlig;en bleiben!");
            }
        }
        
        private function evalActions() {
            $this->actions = array_map("trim", explode(",", urldecode($_GET['actions'])));    
        }
        
        private function do_sort() {
            return in_array("do_sort", $this->actions);
        }
        
        private function do_graph() {
            return in_array("do_graph", $this->actions);
        }
        
        private function do_record() {
            return in_array("do_record", $this->actions);
        }
        
        private function do_inform() {
            return in_array("do_inform", $this->actions);
        }
        
        private function zugriffe_sortieren(&$zugriffe) {        
            // ZUGRIFFE NACH DOMAIN SORTIEREN
            uasort($zugriffe, function ($i, $j) {
                    $a = $i[ZUGRIFFE_SORT_BY];
                    $b = $j[ZUGRIFFE_SORT_BY];
                    if ($a == $b) return 0;
                    elseif ($a > $b) return 1;
                    else return -1;
            });
        }
        
        private function output_eventdrops($zugriffe) {
            $graph_data = '';
                
            $zugriffe_stapel = array();
           
            $last_key = "";
            
            
            foreach($zugriffe AS $zugriff) {
                
                $JS_Y = date("Y", strtotime($zugriff["Timestamp"]));
                $JS_m = (date("m", strtotime($zugriff["Timestamp"]))-1); // Javascript-Monat: von 0 - 11!
                $JS_d = date("d", strtotime($zugriff["Timestamp"]));
            
                if($last_key == "") { // Erste Domain
                   $last_key = $zugriff["Domain"];
                   $graph_data = '{name:"'.$zugriff["Domain"].'", dates:[new Date('.$JS_Y.",".$JS_m.",".$JS_d.'),';
                } elseif($zugriff["Domain"] == $last_key) { // Gleiche Domain
                    $graph_data .= 'new Date('.$JS_Y.",".$JS_m.",".$JS_d.'),';
                }   
                else { // NäŠchste Domain, alte speichern
                    $graph_data = rtrim($graph_data, ",");
                    $graph_data .= '] }, {name:"'.$zugriff["Domain"].'", dates:[new Date('.$JS_Y.",".$JS_m.",".$JS_d.'),';
                    $last_key = $zugriff["Domain"];
                }
            }
           
           
            $graph_data = rtrim($graph_data, ",");
            $graph_data .= '] }';
                
            require_once("eventdrops/index.inc.php");
        }

    } // Ende: Partnerporgramm 
    
    class FileHandler {
        
        private $filename = "";
        
        function __construct($filename) {
            $this->filename = $filename;
            
            // BISHERIGE ZUGRIFFE EINLESEN
            if(!is_file($this->filename)) {
               die("Bisherige Zugriffe fehlen!");   
            }
        }
        
        public function read() {
            return unserialize(file_get_contents($this->filename));
        }
    
        public function write($data) {
            $serializedData = serialize($data); 
            file_put_contents($this->filename, $serializedData);
        }
        
    } // Ende: FileHandler
        
        
        
    class Informer {
        
        function __construct() {
            ;
        }
        
        public function generate_zugriffsauswertung($zugriffe) {
            $zugriffe_counter = 0;
            $zugriffe_stapel = array();
           
            $last_key = "";
           
            foreach($zugriffe AS $zugriff) {   
                if($last_key == "") { // Erste Domain
                   $last_key = $zugriff["Domain"];
                   $zugriffe_counter = 1;
                } elseif($zugriff["Domain"] == $last_key) { // Gleiche Domain
                    $zugriffe_counter++;  
                }   
                else { // NŠchste Domain, alte speichern
                    $zugriffe_stapel[$last_key] = $zugriffe_counter;
                    $last_key = $zugriff["Domain"];
                    $zugriffe_counter = 1;
                }
            }
           
            // Letzte Domain
            $zugriffe_stapel[$last_key] = $zugriffe_counter;
           
            // Nach Zugriffen sortieren, Achtung: &array
            arsort($zugriffe_stapel);
            
            return $zugriffe_stapel;
            
        } // Ende: generate_zugriffsauswertung
        
        
        public function sendMail($zugriffe_stapel) {
            
            if(!is_array($zugriffe_stapel)) {
                die("Keine Zugriffe vorhanden. Versand gestoppt.");
            }
            
            $mailBody = "Zugriffe\t-\tvon Domain\n\n";
            
            // Mail-Inhalt generieren
            foreach($zugriffe_stapel AS $z_key => $z_value) {
                $mailBody .= "********\t-\t********\n";
                $mailBody .= $z_value."\t\t-\t{$z_key}"."\n";
            }
            
            $mailBody .= "\n\n"."Grafische Auswertung:\n".URL_THIS."?type=partner&referrer=&actions=do_graph";
            
            $mailBody .= MAIL_SIGNATURE;
    
            $mailHeader  = "From: ".MAIL_FROM."\r\n";
            $mailHeader .= "Reply-To: ".MAIL_FROM."\r\n";
            $mailHeader .= "X-Mailer: http://{$_SERVER['HTTP_HOST']}\r\n";    
            $mailHeader .= "X-Sender-IP: {$_SERVER['REMOTE_ADDR']}\r\n";
            $mailHeader .= "Bcc: ".MAIL_BCC."\r\n";	
                
            $mailParams = "-f".MAIL_FROM;
    
            return mail(MAIL_TO, MAIL_SUBJECT, $mailBody, $mailHeader, $mailParams);
        }
        
    } // Ende: Informer
    

?>