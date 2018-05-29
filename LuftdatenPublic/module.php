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

if (!defined('IPS_BOOLEAN')) {
    define('IPS_BOOLEAN', 0);
}
if (!defined('IPS_INTEGER')) {
    define('IPS_INTEGER', 1);
}
if (!defined('IPS_FLOAT')) {
    define('IPS_FLOAT', 2);
}
if (!defined('IPS_STRING')) {
    define('IPS_STRING', 3);
}

class LuftdatenPublic extends IPSModule
{
    use LuftdatenCommon;
    use LuftdatenLibrary;

    public function Create()
    {
        parent::Create();

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

        $this->SetStatus($ok ? 102 : 201);

        $this->SetUpdateInterval();
    }

    public function VerifyConfiguration()
    {
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
        $sensor_id = $this->ReadPropertyString('sensor_id');
        $url = 'http://api.luftdaten.info/v1/sensor/' . $sensor_id . '/';

        $jdata = $this->do_HttpRequest($url);
        if ($jdata == '') {
            return;
        }

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
        $this->SetStatus(102);
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
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $duration = floor((microtime(true) - $time_start) * 100) / 100;
        $this->SendDebug(__FUNCTION__, ' => httpcode=' . $httpcode . ', duration=' . $duration . 's', 0);

        $statuscode = 0;
        $err = '';
        $jdata = '';
        if ($httpcode != 200) {
            if ($httpcode == 404) {
                $err = "got http-code $httpcode (page not found)";
                $statuscode = 204;
            } elseif ($httpcode >= 500 && $httpcode <= 599) {
                $statuscode = 202;
                $err = "got http-code $httpcode (server error)";
            } else {
                $err = "got http-code $httpcode";
                $statuscode = 203;
            }
        } elseif ($cdata == '') {
            $statuscode = 205;
            $err = 'no data';
        } else {
            $jdata = json_decode($cdata, true);
            if ($jdata == '') {
                $statuscode = 205;
                $err = 'malformed response';
            }
        }

        if ($statuscode) {
            echo "url=$url => statuscode=$statuscode, err=$err";
            $this->SendDebug(__FUNCTION__, ' => statuscode=' . $statuscode . ', err=' . $err, 0);
            $this->SetStatus($statuscode);
        }

        return $jdata;
    }
}
