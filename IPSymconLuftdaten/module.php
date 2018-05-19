<?php

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

class IPSymconLuftdaten extends IPSModule
{
    public function Create()
    {
        parent::Create();

		$this->RegisterPropertyString('sensor_id', '');
		$this->RegisterPropertyInteger('update_interval', 0);
		$this->RegisterPropertyBoolean('sensor_sds', false);
		$this->RegisterPropertyBoolean('sensor_pms', false);
		$this->RegisterPropertyBoolean('sensor_dht22', false);
		$this->RegisterPropertyBoolean('sensor_htu21d', false);
		$this->RegisterPropertyBoolean('sensor_ppd', false);
		$this->RegisterPropertyBoolean('sensor_bmp180', false);
		$this->RegisterPropertyBoolean('sensor_bmp280', false);
		$this->RegisterPropertyBoolean('sensor_bme280', false);
		$this->RegisterPropertyBoolean('sensor_ds18b20', false);

		$this->CreateVarProfile('Luftdaten.PM', IPS_FLOAT, ' µg/m³', 0, 0, 0, 1, 'Snow');
		$this->CreateVarProfile('Luftdaten.Temperatur', IPS_FLOAT, ' °C', -10, 30, 0, 1, 'Temperature');
		$this->CreateVarProfile('Luftdaten.Humidity', IPS_FLOAT, ' %', 0, 0, 0, 0, 'Drops');
		$this->CreateVarProfile('Luftdaten.Pressure', IPS_FLOAT, ' mbar', 0, 0, 0, 0, 'Gauge');
		$this->CreateVarProfile('Luftdaten.Wifi', IPS_INTEGER, ' dBm', 0, 0, 0, 0, 'Intensity');

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

	private function getIdents()
	{
		// Werte pro Sensor
		$sensor_map = [];
		// SDS011
		$sensor_map['SDS011'] = [ 'SDS_P1', 'SDS_P2' ];
		// PMS1003, PMS3003, PMS5003, PMS6003, PMS7003
		$sensor_map['PMS'] = [ 'PMS_P0', 'PMS_P1', 'PMS_P2' ];
		// DHT22
		$sensor_map['DHT22'] = [ 'temperature', 'humidity' ];
		// HTU21D
		$sensor_map['HTU21D'] = [ 'HTU21D_temperature', 'HTU21D_humidity' ];
		// PPD42NS
		$sensor_map['PPD42NS'] = [ 'P1', 'P2' ]; // 'durP1', 'ratioP1', 'durP2', 'ratioP2'
		// BMP180
		$sensor_map['BMP180'] = [ 'BMP_temperature', 'BMP_pressure' ];
		// BMP280
		$sensor_map['BMP280'] = [ 'BMP_temperature', 'BMP_pressure' ];
		// BME280
		$sensor_map['BME280'] = [ 'BME280_temperature', 'BME280_pressure', 'BME280_humidity' ];
		// DS18B20
		$sensor_map['DS18B20'] = [ 'DS18B20_temperature' ];
		// GPS (NEO 6M)

		$sensor_map['GPS (NEO 6M)'] = [ ]; // 'GPS_lat', 'GPS_lon', 'GPS_height', 'GPS_date', 'GPS_time' 
        $sensor_id = $this->ReadPropertyString('sensor_id');
        $sensor_sds = $this->ReadPropertyBoolean('sensor_sds');
        $sensor_pms = $this->ReadPropertyBoolean('sensor_pms');
        $sensor_dht22 = $this->ReadPropertyBoolean('sensor_dht22');
        $sensor_htu21d = $this->ReadPropertyBoolean('sensor_htu21d');
        $sensor_ppd = $this->ReadPropertyBoolean('sensor_ppd');
        $sensor_bmp180 = $this->ReadPropertyBoolean('sensor_bmp180');
        $sensor_bmp280 = $this->ReadPropertyBoolean('sensor_bmp280');
        $sensor_bme280 = $this->ReadPropertyBoolean('sensor_bme280');
        $sensor_ds18b20 = $this->ReadPropertyBoolean('sensor_ds18b20');

		$idents = [];
		if ($sensor_sds) {
			$idents = array_merge($idents, $sensor_map['SDS011']);
		}
		if ($sensor_pms) {
			$idents = array_merge($idents, $sensor_map['PMS']);
		}
		if ($sensor_dht22) {
			$idents = array_merge($idents, $sensor_map['DHT22']);
		}
		if ($sensor_htu21d) {
			$idents = array_merge($idents, $sensor_map['HTU21D']);
		}
		if ($sensor_ppd) {
			$idents = array_merge($idents, $sensor_map['PPD42NS']);
		}
		if ($sensor_bmp180) {
			$idents = array_merge($idents, $sensor_map['BMP180']);
		}
		if ($sensor_bmp280) {
			$idents = array_merge($idents, $sensor_map['BMP280']);
		}
		if ($sensor_bme280) {
			$idents = array_merge($idents, $sensor_map['BME280']);
		}
		if ($sensor_ds18b20) {
			$idents = array_merge($idents, $sensor_map['DS18B20']);
		}
		
		// Lokale Installation mit Wifi-Stärke
		if ($sensor_id == '') {
			$idents[] = 'signal';
		}

		return $idents;
	}

	private function getIdentMap()
	{
		// Werte-Tabelle mit Bezeichnung und Datentyp
		$ident_map = [];
		// Werte übernehmen
		$ident_map['P0'] = [ 'name' => 'PM1', 'datatype' => 'pm' ];
		$ident_map['P1'] = [ 'name' => 'PM10', 'datatype' => 'pm' ];
		$ident_map['P2'] = [ 'name' => 'PM2.5', 'datatype' => 'pm' ];
		$ident_map['SDS_P1'] = [ 'name' => 'PM10', 'datatype' => 'pm' ];
		$ident_map['SDS_P2'] = [ 'name' => 'PM2.5', 'datatype' => 'pm' ];
		$ident_map['temperature'] = [ 'name' => 'Temperature', 'datatype' => 'temperature' ];
		$ident_map['humidity'] = [ 'name' => 'Humidity', 'datatype' => 'humidity' ];
		$ident_map['BMP_temperature'] = [ 'name' => 'Temperature', 'datatype' => 'temperature' ];
		$ident_map['BMP_pressure'] = [ 'name' => 'Pressure', 'datatype' => 'pressure' ];
		$ident_map['BME280_temperature'] = [ 'name' => 'Temperature', 'datatype' => 'temperature' ];
		$ident_map['BME280_humidity'] = [ 'name' => 'Humidity', 'datatype' => 'humidity' ];
		$ident_map['BME280_pressure'] = [ 'name' => 'Pressure', 'datatype' => 'pressure' ];
		$ident_map['signal'] = [ 'name' => 'Signal', 'datatype' => 'signal' ];
		// ignorieren
		$ident_map['samples'] = [];
		$ident_map['min_micro'] = [];
		$ident_map['max_micro'] = [];

		return $ident_map;
	}

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $sensor_id = $this->ReadPropertyString('sensor_id');
        $update_interval = $this->ReadPropertyInteger('update_interval');

		$ident_map = $this->getIdentMap();
		$idents = $this->getIdents();

		$vpos = 1;
		$this->MaintainVariable('LastTransmission', $this->Translate('last transmission'), IPS_INTEGER, '~UnixTimestamp', $vpos++, true);
		foreach ($ident_map as $ident => $entry) {
			$use = in_array($ident, $idents);
			$name = $ident_map[$ident]['name'];
			$datatype = $ident_map[$ident]['datatype'];
			switch ($datatype) {
				case 'pm':
					$this->MaintainVariable($ident, $this->Translate($name), IPS_FLOAT, 'Luftdaten.PM', $vpos++, $use);
					break;
				case 'temperature':
					$this->MaintainVariable($ident, $this->Translate($name), IPS_FLOAT, 'Luftdaten.Temperatur', $vpos++, $use);
					break;
				case 'humidity':
					$this->MaintainVariable($ident, $this->Translate($name), IPS_FLOAT, 'Luftdaten.Humidity', $vpos++, $use);
					break;
				case 'signal':
					$this->MaintainVariable($ident, $this->Translate($name), IPS_INTEGER, 'Luftdaten.Wifi', $vpos++, $use);
					break;
				case 'pressure':
					$this->MaintainVariable($ident, $this->Translate($name), IPS_FLOAT, 'Luftdaten.Pressure', $vpos++, $use);
					break;
				default:
					break;
			}
		}

		$info = $sensor_id != '' ? "Sensor $sensor_id" : "lokal";
		$this->SetSummary($info);

		$ok = true;
		if ($sensor_id != '') {
			if ($update_interval == 0) {
				echo "update-interval must be given for fetching data from api.luftdaten.info";
				$ok = false;
			}
		} else {
			if ($update_interval != 0) {
				echo "update-interval is not needed in local mode";
				$ok = false;
			}
		}
		$this->SetStatus($ok ? 102 : 201);

		$this->SetUpdateInterval();
    }

    public function VerifyConfiguratio()
	{
        $sensor_id = $this->ReadPropertyString('sensor_id');
		$url = 'http://api.luftdaten.info/v1/sensor/' . $sensor_id . '/';

		$jdata = do_HttpRequest($url);
		if ($jdata == '')
			return;

		$sensor = $jdata[0]['sensor'];
		$sensor_type = $sensor['sensor_type']['name'];

		echo "sensor_type=$sensor_type";
	}

    protected function SetUpdateInterval()
    {
        $min = $this->ReadPropertyInteger('update_interval');
        $msec = $min > 0 ? $min * 1000 * 60 : 0;
        $this->SetTimerInterval('UpdateData', $msec);
    }

	public function UpdateData()
	{
        $sensor_id = $this->ReadPropertyString('sensor_id');
		$url = 'http://api.luftdaten.info/v1/sensor/' . $sensor_id . '/';

		$jdata = do_HttpRequest($url);
		if ($jdata == '')
			return;

		$timestamp = $jdata[0]['timestamp'];
		$sensordatavaluess = $jdata[0]['sensordatavalues'];

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
			$err = "got http-code $httpcode from luftdaten.info";
		} elseif ($cdata == '') {
			$statuscode = 204;
			$err = 'no data from luftdaten.info';
		} else {
			$jdata = json_decode($cdata, true);
			if ($jdata == '') {
				$statuscode = 204;
				$err = 'malformed response from luftdaten.info';
			}
		}
		if ($statuscode) {
			echo " => statuscode=$statuscode, err=$err";
			$this->SendDebug(__FUNCTION__, $err, 0);
			$this->SetStatus($statuscode);
		}
		return $jdata;
	}

    protected function SetValue($Ident, $Value)
    {
        @$varID = $this->GetIDForIdent($Ident);
        if ($varID == false) {
            $this->SendDebug(__FUNCTION__, 'missing variable ' . $Ident, 0);
            return;
        }

        if (IPS_GetKernelVersion() >= 5) {
            $ret = parent::SetValue($Ident, $Value);
        } else {
            $ret = SetValue($varID, $Value);
        }
        if ($ret == false) {
            $this->SendDebug(__FUNCTION__, 'mismatch of value "' . $Value . '" for variable ' . $Ident, 0);
        }
    }

    // Variablenprofile erstellen
    private function CreateVarProfile($Name, $ProfileType, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits, $Icon, $Asscociations = '')
    {
        if (!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, $ProfileType);
            IPS_SetVariableProfileText($Name, '', $Suffix);
            IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
            IPS_SetVariableProfileDigits($Name, $Digits);
            IPS_SetVariableProfileIcon($Name, $Icon);
            if ($Asscociations != '') {
                foreach ($Asscociations as $a) {
                    $w = isset($a['Wert']) ? $a['Wert'] : '';
                    $n = isset($a['Name']) ? $a['Name'] : '';
                    $i = isset($a['Icon']) ? $a['Icon'] : '';
                    $f = isset($a['Farbe']) ? $a['Farbe'] : 0;
                    IPS_SetVariableProfileAssociation($Name, $w, $n, $i, $f);
                }
            }
        }
    }

    // Inspired from module SymconTest/HookServe
    private function RegisterHook($WebHook)
    {
        $ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
        if (count($ids) > 0) {
            $hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
            $found = false;
            foreach ($hooks as $index => $hook) {
                if ($hook['Hook'] == $WebHook) {
                    if ($hook['TargetID'] == $this->InstanceID) {
                        return;
                    }
                    $hooks[$index]['TargetID'] = $this->InstanceID;
                    $found = true;
                }
            }
            if (!$found) {
                $hooks[] = ['Hook' => $WebHook, 'TargetID' => $this->InstanceID];
            }
            IPS_SetProperty($ids[0], 'Hooks', json_encode($hooks));
            IPS_ApplyChanges($ids[0]);
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
		if ($uri == '/hook/Luftdaten/') {
			// DOIT
		}
		http_response_code(404);
		die('File not found!');
    }
}

$sensordatavalues = $jdata['sensordatavalues'];

$idents = [];

$idents = array_merge($idents, $sensor_map['SDS011']);
$idents = array_merge($idents, $sensor_map['DHT22']);
$idents = array_merge($idents, $sensor_map['BMP180']);

$idents[] = 'signal';

echo "idents=" . print_r($idents, true) . "\n";
	echo "$ident => $name / $datatype\n";
}
echo "\n";

foreach ($sensordatavalues as $sensordatavalue) {
	$ident = $sensordatavalue['value_type'];
	$value = $sensordatavalue['value'];
	if (!isset($ident_map[$ident])) {
		echo "no mapping for ident $ident\n";
		continue;
	}
	if (!isset($ident_map[$ident]['datatype']))
		continue;
	if (!in_array($ident, $idents))
		continue;
	switch ($ident_map[$ident]['datatype']) {
		case 'pm':
		case 'temperature':
		case 'humidity':
			if (!floatval($value)) {
				$value = 0;
			}
			break;
		case 'signal':
			if (!intval($value)) {
				$value = 0;
			}
			break;
		case 'pressure':
			if (floatval($value) && $value > 0) {
				$value = $value / 100;
			} else {
				$value = 0;
			}
			break;
		default:
			break;
	}
	echo "$ident = $value\n";
}


?>
