<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';
require_once __DIR__ . '/../libs/local.php';

class LuftdatenPublic extends IPSModule
{
    use Luftdaten\StubsCommonLib;
    use LuftdatenLocalLib;

    public function __construct(string $InstanceID)
    {
        parent::__construct($InstanceID);

        $this->CommonConstruct(__DIR__);
    }

    public function __destruct()
    {
        $this->CommonDestruct();
    }

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyBoolean('module_disable', false);

        $this->RegisterPropertyString('sensor_id', '');
        $this->RegisterPropertyInteger('update_interval', 60);

        $this->createGlobals();

        $this->RegisterAttributeString('UpdateInfo', json_encode([]));
        $this->RegisterAttributeString('ModuleStats', json_encode([]));

        $this->InstallVarProfiles(false);

        $this->RegisterTimer('UpdateData', 0, 'IPS_RequestAction(' . $this->InstanceID . ', "UpdateData", "");');

        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    private function CheckModuleConfiguration()
    {
        $r = [];

        $sensor_id = $this->ReadPropertyString('sensor_id');
        if ($sensor_id == '') {
            $this->SendDebug(__FUNCTION__, '"$sensor_id" is needed', 0);
            $r[] = $this->Translate('Sensor-ID is missing');
        }

        $sensors = $this->getSensors();
        if ($sensors == []) {
            $this->SendDebug(__FUNCTION__, 'no sensorѕ defined', 0);
            $r[] = $this->Translate('no sensors defined');
        }

        return $r;
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $this->MaintainReferences();

        if ($this->CheckPrerequisites() != false) {
            $this->MaintainTimer('UpdateData', 0);
            $this->MaintainStatus(self::$IS_INVALIDPREREQUISITES);
            return;
        }

        if ($this->CheckUpdate() != false) {
            $this->MaintainTimer('UpdateData', 0);
            $this->MaintainStatus(self::$IS_UPDATEUNCOMPLETED);
            return;
        }

        if ($this->CheckConfiguration() != false) {
            $this->MaintainTimer('UpdateData', 0);
            $this->MaintainStatus(self::$IS_INVALIDCONFIG);
            return;
        }

        $this->maintainVariables(false);

        $sensor_id = $this->ReadPropertyString('sensor_id');
        $info = "Sensor $sensor_id";
        $sensors = $this->getSensors();
        if ($sensors != []) {
            $info .= ' (' . implode(',', $sensors) . ')';
        }
        $this->SetSummary($info);

        $module_disable = $this->ReadPropertyBoolean('module_disable');
        if ($module_disable) {
            $this->MaintainTimer('UpdateData', 0);
            $this->MaintainStatus(IS_INACTIVE);
            return;
        }

        $this->MaintainStatus(IS_ACTIVE);

        if (IPS_GetKernelRunlevel() == KR_READY) {
            $this->SetUpdateInterval();
        }
    }

    public function MessageSink($timestamp, $senderID, $message, $data)
    {
        parent::MessageSink($timestamp, $senderID, $message, $data);

        if ($message == IPS_KERNELMESSAGE && $data[0] == KR_READY) {
            $this->SetUpdateInterval();
        }
    }

    private function GetFormElements()
    {
        $formElements = $this->GetCommonFormElements('get data from data.sensor.community');

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            return $formElements;
        }

        $formElements[] = [
            'type'    => 'CheckBox',
            'name'    => 'module_disable',
            'caption' => 'Disable instance',
        ];

        $formElements[] = [
            'type'    => 'NumberSpinner',
            'suffix'  => 'Seconds',
            'minimum' => 0,
            'name'    => 'update_interval',
            'caption' => 'Update interval',
        ];

        $formElements[] = [
            'type'    => 'ValidationTextBox',
            'name'    => 'sensor_id',
            'caption' => 'Sensor-ID',
        ];

        $formElements[] = [
            'type'    => 'ExpansionPanel',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'sensor_sds',
                    'caption' => 'SDS011',
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'sensor_pms',
                    'caption' => 'PMS1003, PMS3003, PMS5003, PMS6003, PMS7003',
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'sensor_dht22',
                    'caption' => 'DHT22',
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'sensor_htu21d',
                    'caption' => 'HTU21D',
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'sensor_ppd',
                    'caption' => 'PPD42NS',
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'sensor_bmp180',
                    'caption' => 'BMP180',
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'sensor_bmp280',
                    'caption' => 'BMP280',
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'sensor_bme280',
                    'caption' => 'BME280',
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'sensor_ds18b20',
                    'caption' => 'DS18B20',
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'sensor_dnms',
                    'caption' => 'DNMS',
                ],
            ],
            'caption' => 'Sensor',
        ];

        return $formElements;
    }

    private function GetFormActions()
    {
        $formActions = [];

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            $formActions[] = $this->GetCompleteUpdateFormAction();

            $formActions[] = $this->GetInformationFormAction();
            $formActions[] = $this->GetReferencesFormAction();

            return $formActions;
        }

        $formActions[] = [
            'type'    => 'Button',
            'caption' => 'Verify Configuration',
            'onClick' => 'IPS_RequestAction(' . $this->InstanceID . ', "VerifyConfiguration", "");',
        ];
        $formActions[] = [
            'type'    => 'Button',
            'caption' => 'Update Data',
            'onClick' => 'IPS_RequestAction(' . $this->InstanceID . ', "UpdateData", "");',
        ];

        $formActions[] = $this->GetInformationFormAction();
        $formActions[] = $this->GetReferencesFormAction();

        return $formActions;
    }

    private function LocalRequestAction($ident, $value)
    {
        $r = true;
        switch ($ident) {
            case 'VerifyConfiguration':
                $this->VerifyConfiguration();
                break;
            case 'UpdateData':
                $this->UpdateData();
                break;
            default:
                $r = false;
                break;
        }
        return $r;
    }

    public function RequestAction($ident, $value)
    {
        if ($this->LocalRequestAction($ident, $value)) {
            return;
        }
        if ($this->CommonRequestAction($ident, $value)) {
            return;
        }
        switch ($ident) {
            default:
                $this->SendDebug(__FUNCTION__, 'invalid ident ' . $ident, 0);
                break;
        }
    }

    private function VerifyConfiguration()
    {
        if ($this->GetStatus() == IS_INACTIVE) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            $msg = $this->GetStatusText();
            $this->PopupMessage($msg);
            return;
        }

        $sensor_id = $this->ReadPropertyString('sensor_id');
        $url = 'https://data.sensor.community/airrohr/v1/sensor/' . $sensor_id . '/';

        $jdata = $this->do_HttpRequest($url);
        if ($jdata == '') {
            $msg = $this->Translate('configuration incorrect: unknown sensor-id');
            $this->PopupMessage($msg);
            return;
        }

        $sensor = $jdata[0]['sensor'];
        $got_sensor = $sensor['sensor_type']['name'];

        $cfg_sensors = $this->getSensors();

        if ($cfg_sensors == []) {
            $msg = $this->TranslateFormat('configuration incomplete: no sensor configured, got: {$got_sensor}', ['{$got_sensor}' => $got_sensor]);
        } elseif (!in_array($got_sensor, $cfg_sensors)) {
            $s = $cfg_sensors == [] ? $this->Translate('none') : implode(',', $cfg_sensors);
            $msg = $this->TranslateFormat('configuration mismatch: got: {$got_sensor}, configured: {$cfg_sensors}', ['{$got_sensor}' => $got_sensor, '{$cfg_sensors}' => $s]);
        } elseif (count($cfg_sensors) > 1) {
            $msg = $this->TranslateFormat('configuration improvable: too much sensorѕ configured, got: {$got_sensor}', ['{$got_sensor}' => $got_sensor]);
        } else {
            $msg = $this->TranslateFormat('configuration ok: sensor {$got_sensor}', ['{$got_sensor}' => $got_sensor]);
        }
        $this->PopupMessage($msg);
    }

    private function SetUpdateInterval()
    {
        $sec = $this->ReadPropertyInteger('update_interval');
        $msec = $sec > 0 ? $sec * 1000 : 0;
        $this->MaintainTimer('UpdateData', $msec);
    }

    private function UpdateData()
    {
        if ($this->CheckStatus() == self::$STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return;
        }

        $sensor_id = $this->ReadPropertyString('sensor_id');
        $url = 'https://data.sensor.community/airrohr/v1/sensor/' . $sensor_id . '/';

        $jdata = $this->do_HttpRequest($url);
        if ($jdata == '') {
            return;
        }
        $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);

        $max_ts = 0;
        $idx = 0;
        for ($i = 0; $i < count($jdata); $i++) {
            $ts = strtotime($jdata[$i]['timestamp']);
            if ($ts > $max_ts) {
                $max_ts = $ts;
                $idx = $i;
            }
        }

        $ts = strtotime($jdata[$idx]['timestamp'] . ' GMT');
        $this->SetValue('LastTransmission', $ts);

        $sensordatavalues = $jdata[$idx]['sensordatavalues'];
        $this->decodeData($sensordatavalues, false);
        $this->MaintainStatus(IS_ACTIVE);
    }

    private function do_HttpRequest($url)
    {
        $this->SendDebug(__FUNCTION__, 'http-get: url=' . $url, 0);
        $time_start = microtime(true);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $cdata = curl_exec($ch);
        $cerrno = curl_errno($ch);
        $cerror = $cerrno ? curl_error($ch) : '';
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $duration = round(microtime(true) - $time_start, 2);
        $this->SendDebug(__FUNCTION__, ' => errno=' . $cerrno . ', httpcode=' . $httpcode . ', duration=' . $duration . 's', 0);

        $statuscode = 0;
        $err = '';
        $jdata = '';
        if ($cerrno) {
            $statuscode = self::$IS_SERVERERROR;
            $err = 'got curl-errno ' . $cerrno . ' (' . $cerror . ')';
        } elseif ($httpcode != 200) {
            if ($httpcode == 404) {
                $err = 'got http-code ' . $httpcode . ' (page not found)';
                $statuscode = self::$IS_PAGENOTFOUND;
            } elseif ($httpcode >= 500 && $httpcode <= 599) {
                $statuscode = self::$IS_SERVERERROR;
                $err = 'got http-code ' . $httpcode . ' (server error)';
            } else {
                $err = 'got http-code ' . $httpcode;
                $statuscode = self::$IS_HTTPERROR;
            }
        } elseif ($cdata == '') {
            $statuscode = self::$IS_INVALIDDATA;
            $err = 'no data';
        } elseif ($cdata == '[]') {
            $statuscode = self::$IS_INVALIDDATA;
            $err = 'empty response (unknown sensor?)';
        } else {
            $jdata = json_decode($cdata, true);
            if ($jdata == '') {
                $statuscode = self::$IS_INVALIDDATA;
                $err = 'malformed response';
            }
        }

        if ($statuscode) {
            $this->LogMessage('url=' . $url . ' => statuscode=' . $statuscode . ', err=' . $err, KL_WARNING);
            $this->SendDebug(__FUNCTION__, ' => statuscode=' . $statuscode . ', err=' . $err, 0);
            $this->MaintainStatus($statuscode);
        }

        return $jdata;
    }
}
