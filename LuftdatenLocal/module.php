<?php

require_once __DIR__ . '/../libs/common.php';  // globale Funktionen
require_once __DIR__ . '/../libs/library.php';  // modul-bezogene Funktionen

// Constants will be defined with IP-Symcon 5.0 and newer
if (!defined('IPS_KERNELMESSAGE')) {
    define('IPS_KERNELMESSAGE', 10100);
}
if (!defined('KR_READY')) {
    define('KR_READY', 10103);
}

if (!defined('VARIABLETYPE_BOOLEAN')) {
    define('VARIABLETYPE_BOOLEAN', 0);
    define('VARIABLETYPE_INTEGER', 1);
    define('VARIABLETYPE_FLOAT', 2);
    define('VARIABLETYPE_STRING', 3);
}

class LuftdatenLocal extends IPSModule
{
    use LuftdatenCommon;
    use LuftdatenLibrary;

    public function Create()
    {
        parent::Create();

        $this->createGlobals();

        // Inspired by module SymconTest/HookServe
        // We need to call the RegisterHook function on Kernel READY
        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    // Inspired by module SymconTest/HookServe
    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

        if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) {
            $this->RegisterHook('/hook/Luftdaten');
        }
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $this->maintainVariables(true);

        $info = 'lokal';
        $sensors = $this->getSensors();
        if ($sensors != []) {
            $info .= ' (' . implode(',', $sensors) . ')';
        }

        $this->SetSummary($info);

        $statuscode = 102;

        $sensors = $this->getSensors();
        if ($sensors == []) {
            $statuscode = 201;
        }

        $this->SetStatus($statuscode);

        // Inspired by module SymconTest/HookServe
        // Only call this in READY state. On startup the WebHook instance might not be available yet
        if (IPS_GetKernelRunlevel() == KR_READY) {
            $this->RegisterHook('/hook/Luftdaten');
        }
    }

    // Inspired from module SymconTest/HookServe
    protected function ProcessHookData()
    {
        $this->SendDebug('WebHook SERVER', print_r($_SERVER, true), 0);

        $root = realpath(__DIR__);
        $uri = $_SERVER['REQUEST_URI'];
        if (substr($uri, -1) == '/') {
            http_response_code(404);
            die('File not found!');
        }
        if ($uri == '/hook/Luftdaten') {
            $data = file_get_contents('php://input');
            $jdata = json_decode($data, true);
            if ($jdata == '') {
                echo 'malformed data: ' . $data;
                $this->SendDebug(__FUNCTION__, 'malformed data: ' . $data, 0);
                return;
            }
            $this->SetValue('LastTransmission', time());
            $sensordatavalues = $jdata['sensordatavalues'];
            $this->decodeData($sensordatavalues, true);
            return;
        }
        http_response_code(404);
        die('File not found!');
    }
}
