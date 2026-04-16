<?php
// SimpleSMTP - Utilitário para envio direto de e-mail usando socket TCP nativo do PHP
// Usado para garantir dependência zero.

class SimpleSMTP {
    private $host;
    private $port;
    private $user;
    private $pass;
    private $connection;
    
    public function __construct($host = null, $port = null, $user = null, $pass = null) {
        $this->host = $host ?? $_ENV['SMTP_HOST'] ?? '127.0.0.1';
        $this->port = $port ?? $_ENV['SMTP_PORT'] ?? 2525;
        $this->user = $user ?? $_ENV['SMTP_USER'] ?? '';
        $this->pass = $pass ?? $_ENV['SMTP_PASS'] ?? '';
    }
    
    public function send($to, $subject, $message, $from = 'noreply@superpage.com.br', $fromName = 'SuperPage') {
        if (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] == 'true' && empty($this->user)) {
            // Em dev sem SMTP configurado real, apenas retorna true e loga (silencioso)
            error_log("Simulated Email Sent: To: $to, Subject: $subject");
            return true;
        }

        $this->connection = fsockopen($this->host, $this->port, $errno, $errstr, 10);
        if (!$this->connection) {
            error_log("Erro SMTP: $errstr ($errno)");
            return false;
        }
        
        $this->readResponse(); // Pega banner inicial
        
        $this->sendCommand("EHLO {$this->host}");
        
        if (!empty($this->user)) {
            $this->sendCommand("AUTH LOGIN");
            $this->sendCommand(base64_encode($this->user));
            $this->sendCommand(base64_encode($this->pass));
        }
        
        $this->sendCommand("MAIL FROM: <$from>");
        $this->sendCommand("RCPT TO: <$to>");
        $this->sendCommand("DATA");
        
        $headers = "From: $fromName <$from>\r\n";
        $headers .= "Reply-To: $from\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        $body = $headers . "\r\n" . $message . "\r\n.";
        
        fputs($this->connection, $body . "\r\n");
        $this->readResponse();
        
        $this->sendCommand("QUIT");
        fclose($this->connection);
        
        return true;
    }
    
    private function sendCommand($command) {
        fputs($this->connection, $command . "\r\n");
        return $this->readResponse();
    }
    
    private function readResponse() {
        $res = '';
        while ($str = fgets($this->connection, 515)) {
            $res .= $str;
            if (substr($str, 3, 1) == " ") break;
        }
        return $res;
    }
}
