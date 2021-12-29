<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';  // globale Funktionen
require_once __DIR__ . '/../libs/local.php';   // lokale Funktionen

class LuftdatenLocal extends IPSModule
{
    use LuftdatenCommonLib;
    use LuftdatenLocalLib;

    public function Create()
    {
        parent::Create();

        $this->createGlobals();

        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

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
            $statuscode = self::$IS_NOSENSOR;
        }

        $this->SetStatus($statuscode);

        if (IPS_GetKernelRunlevel() == KR_READY) {
            $this->RegisterHook('/hook/Luftdaten');
        }
    }

    protected function ProcessHookData()
    {
        $this->SendDebug(__FUNCTION__, print_r($_SERVER, true), 0);

        $root = realpath(__DIR__);
        $uri = $_SERVER['REQUEST_URI'];
        if (substr($uri, -1) == '/') {
            http_response_code(404);
            die('File not found!');
        }
        if ($uri == '/hook/Luftdaten') {
            $data = file_get_contents('php://input');
            $this->SendDebug(__FUNCTION__, 'data=' . $data, 0);

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

    private function GetFormElements()
    {
        $formElements = [];

        $formElements[] = [
            'type'    => 'Label',
            'caption' => 'receive data from local sensor-station'
        ];
        $formElements[] = [
            'type'    => 'Label',
            'caption' => 'installed sensors (see config-page of sensor-station)'
        ];
        $formElements[] = [
            'type' => 'CheckBox',
            'name' => 'sensor_sds', 'caption' => ' ... SDS011'
        ];
        $formElements[] = [
            'type' => 'CheckBox',
            'name' => 'sensor_pms', 'caption' => ' ... PMS1003, PMS3003, PMS5003, PMS6003, PMS7003'
        ];
        $formElements[] = [
            'type' => 'CheckBox',
            'name' => 'sensor_dht22', 'caption' => ' ... DHT22'
        ];
        $formElements[] = [
            'type' => 'CheckBox',
            'name' => 'sensor_htu21d', 'caption' => ' ... HTU21D'
        ];
        $formElements[] = [
            'type' => 'CheckBox',
            'name' => 'sensor_ppd', 'caption' => ' ... PPD42NS'
        ];
        $formElements[] = [
            'type' => 'CheckBox',
            'name' => 'sensor_bmp180', 'caption' => ' ... BMP180'
        ];
        $formElements[] = [
            'type' => 'CheckBox',
            'name' => 'sensor_bmp280', 'caption' => ' ... BMP280'
        ];
        $formElements[] = [
            'type' => 'CheckBox',
            'name' => 'sensor_bme280', 'caption' => ' ... BME280'
        ];
        $formElements[] = [
            'type' => 'CheckBox',
            'name' => 'sensor_ds18b20', 'caption' => ' ... DS18B20'
        ];
        $formElements[] = [
            'type' => 'CheckBox',
            'name' => 'sensor_dnms', 'caption' => ' ... DNMS'
        ];

        return $formElements;
    }

    private function GetFormActions()
    {
        $formActions = [];

        $formActions[] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Information',
            'items'   => [
                [
                    'type'    => 'Label',
                    'caption' => $this->InstanceInfo($this->InstanceID),
                ],
            ],
        ];

        return $formActions;
    }
}
