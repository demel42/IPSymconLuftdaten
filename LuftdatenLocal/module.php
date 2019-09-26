<?php

require_once __DIR__ . '/../libs/common.php';  // globale Funktionen
require_once __DIR__ . '/../libs/library.php';  // modul-bezogene Funktionen

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

        $statuscode = IS_ACTIVE;

        $sensors = $this->getSensors();
        if ($sensors == []) {
            $statuscode = IS_NOSENSOR;
        }

        $this->SetStatus($statuscode);

        // Inspired by module SymconTest/HookServe
        // Only call this in READY state. On startup the WebHook instance might not be available yet
        if (IPS_GetKernelRunlevel() == KR_READY) {
            $this->RegisterHook('/hook/Luftdaten');
        }
    }

    public function GetConfigurationForm()
    {
        $formElements = [];
        $formElements[] = ['type' => 'Label', 'label' => 'receive data from local sensor-station'];
        $formElements[] = ['type' => 'Label', 'label' => 'installed sensors (see config-page of sensor-station)'];
        $formElements[] = ['type' => 'CheckBox', 'name' => 'sensor_sds', 'caption' => ' ... SDS011'];
        $formElements[] = ['type' => 'CheckBox', 'name' => 'sensor_pms', 'caption' => ' ... PMS1003, PMS3003, PMS5003, PMS6003, PMS7003'];
        $formElements[] = ['type' => 'CheckBox', 'name' => 'sensor_dht22', 'caption' => ' ... DHT22'];
        $formElements[] = ['type' => 'CheckBox', 'name' => 'sensor_htu21d', 'caption' => ' ... HTU21D'];
        $formElements[] = ['type' => 'CheckBox', 'name' => 'sensor_ppd', 'caption' => ' ... PPD42NS'];
        $formElements[] = ['type' => 'CheckBox', 'name' => 'sensor_bmp180', 'caption' => ' ... BMP180'];
        $formElements[] = ['type' => 'CheckBox', 'name' => 'sensor_bmp280', 'caption' => ' ... BMP280'];
        $formElements[] = ['type' => 'CheckBox', 'name' => 'sensor_bme280', 'caption' => ' ... BME280'];
        $formElements[] = ['type' => 'CheckBox', 'name' => 'sensor_ds18b20', 'caption' => ' ... DS18B20'];

        $formActions = [];
        if (IPS_GetKernelVersion() < 5.2) {
            $formActions[] = ['type' => 'Label', 'label' => '____________________________________________________________________________________________________'];
            $formActions[] = ['type' => 'Button', 'label' => 'Module description', 'onClick' => 'echo \'https://github.com/demel42/IPSymconLuftdaten/blob/master/README.md\';'];
        }

        $formStatus = [];
        $formStatus[] = ['code' => IS_CREATING, 'icon' => 'inactive', 'caption' => 'Instance getting created'];
        $formStatus[] = ['code' => IS_ACTIVE, 'icon' => 'active', 'caption' => 'Instance is active'];
        $formStatus[] = ['code' => IS_DELETING, 'icon' => 'inactive', 'caption' => 'Instance is deleted'];
        $formStatus[] = ['code' => IS_INACTIVE, 'icon' => 'inactive', 'caption' => 'Instance is inactive'];
        $formStatus[] = ['code' => IS_NOTCREATED, 'icon' => 'inactive', 'caption' => 'Instance is not created'];

        $formStatus[] = ['code' => IS_NOSENSOR, 'icon' => 'error', 'caption' => 'Instance is inactive (no sensor)'];

        return json_encode(['elements' => $formElements, 'actions' => $formActions, 'status' => $formStatus]);
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
