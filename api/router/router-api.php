<?php

class RouterOSAPI {

    private $host;
    private $port;
    private $timeout;
    private $socket;
    private $connected = false;
    private $isSSL = false;

    public function __construct($host, $port = 8728, $timeout = 5, $ssl = false) {
        $this->host    = $host;
        $this->port    = $port;
        $this->timeout = $timeout;
        $this->isSSL   = $ssl;
    }

    public function connect($username, $password) {
        $proto = $this->isSSL ? 'ssl://' : '';
        $this->socket = @fsockopen($proto . $this->host, $this->port, $errno, $errstr, $this->timeout);

        if (!$this->socket) {
            throw new Exception("Cannot connect: $errstr ($errno)");
        }

        stream_set_timeout($this->socket, $this->timeout);

        // Login (plaintext mode for ROS v6/v7)
        $this->write('/login', [
            'name' => $username,
            'password' => $password
        ]);

        $response = $this->read();

        if (isset($response[0]) && $response[0] == '!done') {
            $this->connected = true;
            return true;
        }

        throw new Exception("Login failed.");
    }

    public function write($command, $params = []) {
        if (!$this->connected && $command !== '/login') {
            throw new Exception("Not connected to RouterOS.");
        }

        $this->writeWord($command);

        foreach ($params as $key => $value) {
            $this->writeWord("=$key=$value");
        }

        $this->writeWord(""); // end of command
    }

    private function writeWord($word) {
        $len = strlen($word);
        $this->writeLength($len);
        fwrite($this->socket, $word);
    }

    private function writeLength($length) {
        if ($length < 0x80) {
            fwrite($this->socket, chr($length));
        } elseif ($length < 0x4000) {
            $length |= 0x8000;
            fwrite($this->socket, chr(($length >> 8) & 0xFF) . chr($length & 0xFF));
        } elseif ($length < 0x200000) {
            $length |= 0xC00000;
            fwrite($this->socket, chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF));
        } else {
            throw new Exception("Word too long.");
        }
    }

    public function read() {
        $response = [];
        while (true) {
            $word = $this->readWord();
            if ($word === false) break;
            $response[] = $word;
            if ($word == "!done") break;
        }
        return $response;
    }

    private function readWord() {
        $len = $this->readLength();
        if ($len === false) return false;
        return $len > 0 ? fread($this->socket, $len) : "";
    }

    private function readLength() {
        $c = ord(fread($this->socket, 1));
        if ($c < 0x80) return $c;
        if (($c & 0xC0) == 0x80) {
            $c2 = ord(fread($this->socket, 1));
            return (($c & 0x3F) << 8) + $c2;
        }
        if (($c & 0xE0) == 0xC0) {
            $c2 = ord(fread($this->socket, 1));
            $c3 = ord(fread($this->socket, 1));
            return (($c & 0x1F) << 16) + ($c2 << 8) + $c3;
        }
        return false;
    }

    public function disconnect() {
        if ($this->socket) fclose($this->socket);
        $this->connected = false;
    }
}

?>
