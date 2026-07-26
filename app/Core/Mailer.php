<?php

namespace App\Core;

class Mailer
{
    private $host;
    private $port;
    private $user;
    private $pass;
    private $from;
    private $fromName;

    public function __construct()
    {
        $this->host = Env::get('MAIL_HOST', 'smtp.gmail.com');
        $this->port = (int) Env::get('MAIL_PORT', 587);
        $this->user = Env::get('MAIL_USER', '');
        $this->pass = Env::get('MAIL_PASS', '');
        $this->from = Env::get('MAIL_FROM', '');
        if ($this->from === '') {
            $this->from = $this->user;
        }
        $this->fromName = Env::get('MAIL_FROM_NAME', 'TodoPHP');
    }

    public function isConfigured()
    {
        return $this->host !== '' && $this->user !== '' && $this->pass !== '' && $this->from !== '';
    }

    public function send($to, $subject, $html, $text = '')
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('MAIL_USER/MAIL_PASS chua duoc cau hinh trong .env');
        }

        $socket = stream_socket_client("tcp://{$this->host}:{$this->port}", $errno, $errstr, 20);
        if (!$socket) {
            throw new \RuntimeException("Khong ket noi duoc SMTP: {$errstr}");
        }

        stream_set_timeout($socket, 20);
        $this->expect($socket, [220]);
        $this->cmd($socket, 'EHLO localhost', [250]);
        $this->cmd($socket, 'STARTTLS', [220]);

        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            throw new \RuntimeException('Khong bat duoc TLS cho SMTP');
        }

        $this->cmd($socket, 'EHLO localhost', [250]);
        $this->cmd($socket, 'AUTH LOGIN', [334]);
        $this->cmd($socket, base64_encode($this->user), [334]);
        $this->cmd($socket, base64_encode($this->pass), [235]);
        $this->cmd($socket, 'MAIL FROM:<' . $this->from . '>', [250]);
        $this->cmd($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
        $this->cmd($socket, 'DATA', [354]);

        $body = $this->message($to, $subject, $html, $text);
        fwrite($socket, str_replace("\n.", "\n..", $body) . "\r\n.\r\n");
        $this->expect($socket, [250]);
        $this->cmd($socket, 'QUIT', [221]);
        fclose($socket);

        return true;
    }

    private function message($to, $subject, $html, $text)
    {
        $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $fromName = '=?UTF-8?B?' . base64_encode($this->fromName) . '?=';
        $text = $text !== '' ? $text : strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html));

        return implode("\r\n", [
            'From: ' . $fromName . ' <' . $this->from . '>',
            'To: <' . $to . '>',
            'Subject: ' . $subject,
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            '',
            $html,
        ]);
    }

    private function cmd($socket, $command, array $okCodes)
    {
        fwrite($socket, $command . "\r\n");
        return $this->expect($socket, $okCodes);
    }

    private function expect($socket, array $okCodes)
    {
        $response = '';
        do {
            $line = fgets($socket, 515);
            if ($line === false) {
                throw new \RuntimeException('SMTP khong phan hoi');
            }
            $response .= $line;
        } while (isset($line[3]) && $line[3] === '-');

        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $okCodes, true)) {
            throw new \RuntimeException('SMTP loi: ' . trim($response));
        }

        return $response;
    }

    /**
     * Bổ sung: Phương thức tĩnh hỗ trợ gọi gửi nhanh Mailer::quickSend(...) 
     * không cần tự tạo đối tượng new Mailer() ở các file khác.
     */
    public static function quickSend($to, $subject, $html, $text = '')
    {
        $mailer = new self();
        return $mailer->send($to, $subject, $html, $text);
    }
}