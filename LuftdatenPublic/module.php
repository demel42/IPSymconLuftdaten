<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';  // globale Funktionen
require_once __DIR__ . '/../libs/library.php';  // modul-bezogene Funktionen

class LuftdatenPublic extends IPSModule
{
    use LuftdatenCommon;
    use LuftdatenLibrary;

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyBoolean('module_disable', false);

        $this->RegisterPropertyString('sensor_id', '');
        $this->RegisterPropertyInteger('update_interval', 60);

        $this->createGlobals();

        $this->RegisterTimer('UpdateData', 0, 'LuftdatenPublic_UpdateData(' . $this->InstanceID . ');');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $this->maintainVariables(false);

        $sensor_id = $this->ReadPropertyString('sensor_id');
        $info = "Sensor $sensor_id";
        $sensors = $this->getSensors();
        if ($sensors != []) {
            $info .= ' (' . implode(',', $sensors) . ')';
        }
        $this->SetSummary($info);

        $ok = true;

        $sensor_id = $this->ReadPropertyString('sensor_id');
        if ($sensor_id == '') {
            $ok = false;
        }

        if ($sensors == []) {
            $ok = false;
        }

        $module_disable = $this->ReadPropertyBoolean('module_disable');
        if ($module_disable) {
            $this->SetTimerInterval('UpdateData', 0);
            $this->SetStatus(IS_INACTIVE);
            return;
        }

        $this->SetStatus($ok ? IS_ACTIVE : IS_INVALIDCONFIG);
        $this->SetUpdateInterval();
    }

    public function GetConfigurationForm()
    {
        $formElements = [];
        $formElements[] = ['type' => 'CheckBox', 'name' => 'module_disable', 'caption' => 'Instance is disabled'];
        $formElements[] = ['type' => 'Label', 'caption' => 'get data from api.luftdaten.info'];
        $formElements[] = ['type' => 'ValidationTextBox', 'name' => 'sensor_id', 'caption' => 'Sensor-ID'];
        $formElements[] = ['type' => 'Label', 'caption' => 'Update data every X seconds'];
        $formElements[] = ['type' => 'NumberSpinner', 'name' => 'update_interval', 'caption' => 'Seconds'];
        $formElements[] = ['type' => 'Label', 'caption' => 'Sensor'];
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
        $formActions[] = ['type' => 'Button', 'caption' => 'Verify Configuration', 'onClick' => 'LuftdatenPublic_VerifyConfiguration($id);'];
        $formActions[] = ['type' => 'Button', 'caption' => 'Update Data', 'onClick' => 'LuftdatenPublic_UpdateData($id);'];

        $formStatus = [];
        $formStatus[] = ['code' => IS_CREATING, 'icon' => 'inactive', 'caption' => 'Instance getting created'];
        $formStatus[] = ['code' => IS_ACTIVE, 'icon' => 'active', 'caption' => 'Instance is active'];
        $formStatus[] = ['code' => IS_DELETING, 'icon' => 'inactive', 'caption' => 'Instance is deleted'];
        $formStatus[] = ['code' => IS_INACTIVE, 'icon' => 'inactive', 'caption' => 'Instance is inactive'];
        $formStatus[] = ['code' => IS_NOTCREATED, 'icon' => 'inactive', 'caption' => 'Instance is not created'];

        $formStatus[] = ['code' => IS_INVALIDCONFIG, 'icon' => 'error', 'caption' => 'Instance is inactive (invalid configuration)'];
        $formStatus[] = ['code' => IS_SERVERERROR, 'icon' => 'error', 'caption' => 'Instance is inactive (server error)'];
        $formStatus[] = ['code' => IS_HTTPERROR, 'icon' => 'error', 'caption' => 'Instance is inactive (http error)'];
        $formStatus[] = ['code' => IS_PAGENOTFOUND, 'icon' => 'error', 'caption' => 'Instance is inactive (page not found)'];
        $formStatus[] = ['code' => IS_INVALIDDATA, 'icon' => 'error', 'caption' => 'Instance is inactive (invalid data)'];

        return json_encode(['elements' => $formElements, 'actions' => $formActions, 'status' => $formStatus]);
    }

    public function VerifyConfiguration()
    {
        $inst = IPS_GetInstance($this->InstanceID);
        if ($inst['InstanceStatus'] == IS_INACTIVE) {
            $this->SendDebug(__FUNCTION__, 'instance is inactive, skip', 0);
            echo $this->translate('Instance is inactive') . PHP_EOL;
            return;
        }

        $sensor_id = $this->ReadPropertyString('sensor_id');
        $url = 'http://api.luftdaten.info/v1/sensor/' . $sensor_id . '/';

        $jdata = $this->do_HttpRequest($url);
        if ($jdata == '') {
            return;
        }

        $sensor = $jdata[0]['sensor'];
        $sensor_type = $sensor['sensor_type']['name'];

        $sensors = $this->getSensors();

        if ($sensors == []) {
            echo "configuration incomplete: no sensor configured, got sensor=$sensor_type";
        } elseif (!in_array($sensor_type, $sensors)) {
            $s = $sensors == [] ? 'none' : implode(',', $sensors);
            echo "configuration mismatch: got sensor=$sensor_type, configured are: $s";
        } elseif (count($sensors) > 1) {
            echo "configuration improvable: too much sensorѕ configured, got sensor=$sensor_type";
        } else {
            echo "configuration ok: sensor=$sensor_type";
        }
    }

    protected function SetUpdateInterval()
    {
        $sec = $this->ReadPropertyInteger('update_interval');
        $msec = $sec > 0 ? $sec * 1000 : 0;
        $this->SetTimerInterval('UpdateData', $msec);
    }

    public function UpdateData()
    {
        $inst = IPS_GetInstance($this->InstanceID);
        if ($inst['InstanceStatus'] == IS_INACTIVE) {
            $this->SendDebug(__FUNCTION__, 'instance is inactive, skip', 0);
            return;
        }

        $sensor_id = $this->ReadPropertyString('sensor_id');
        $url = 'http://api.luftdaten.info/v1/sensor/' . $sensor_id . '/';

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
        $this->SetStatus(IS_ACTIVE);
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
            $statuscode = IS_SERVERERROR;
            $err = 'got curl-errno ' . $cerrno . ' (' . $cerror . ')';
        } elseif ($httpcode != 200) {
            if ($httpcode == 404) {
                $err = 'got http-code ' . $httpcode . ' (page not found)';
                $statuscode = IS_PAGENOTFOUND;
            } elseif ($httpcode >= 500 && $httpcode <= 599) {
                $statuscode = IS_SERVERERROR;
                $err = 'got http-code ' . $httpcode . ' (server error)';
            } else {
                $err = 'got http-code ' . $httpcode;
                $statuscode = IS_HTTPERROR;
            }
        } elseif ($cdata == '') {
            $statuscode = IS_INVALIDDATA;
            $err = 'no data';
        } elseif ($cdata == '[]') {
            $statuscode = IS_INVALIDDATA;
            $err = 'empty response (unknown sensor?)';
        } else {
            $jdata = json_decode($cdata, true);
            if ($jdata == '') {
                $statuscode = IS_INVALIDDATA;
                $err = 'malformed response';
            }
        }

        if ($statuscode) {
            $this->LogMessage('url=' . $url . ' => statuscode=' . $statuscode . ', err=' . $err, KL_WARNING);
            $this->SendDebug(__FUNCTION__, ' => statuscode=' . $statuscode . ', err=' . $err, 0);
            $this->SetStatus($statuscode);
        }

        return $jdata;
    }
}
