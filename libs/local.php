<?php

declare(strict_types=1);

trait LuftdatenLocalLib
{
    public static $IS_NOSENSOR = IS_EBASE + 1;
    public static $IS_INVALIDCONFIG = IS_EBASE + 2;
    public static $IS_SERVERERROR = IS_EBASE + 3;
    public static $IS_HTTPERROR = IS_EBASE + 4;
    public static $IS_PAGENOTFOUND = IS_EBASE + 5;
    public static $IS_INVALIDDATA = IS_EBASE + 6;

    private function GetFormStatus()
    {
        $formStatus = [];
        $formStatus[] = ['code' => IS_CREATING, 'icon' => 'inactive', 'caption' => 'Instance getting created'];
        $formStatus[] = ['code' => IS_ACTIVE, 'icon' => 'active', 'caption' => 'Instance is active'];
        $formStatus[] = ['code' => IS_DELETING, 'icon' => 'inactive', 'caption' => 'Instance is deleted'];
        $formStatus[] = ['code' => IS_INACTIVE, 'icon' => 'inactive', 'caption' => 'Instance is inactive'];
        $formStatus[] = ['code' => IS_NOTCREATED, 'icon' => 'inactive', 'caption' => 'Instance is not created'];

        $formStatus[] = ['code' => self::$IS_NOSENSOR, 'icon' => 'error', 'caption' => 'Instance is inactive (no sensor)'];
        $formStatus[] = ['code' => self::$IS_INVALIDDATA, 'icon' => 'error', 'caption' => 'Instance is inactive (invalid data)'];
        $formStatus[] = ['code' => self::$IS_INVALIDCONFIG, 'icon' => 'error', 'caption' => 'Instance is inactive (invalid configuration)'];
        $formStatus[] = ['code' => self::$IS_SERVERERROR, 'icon' => 'error', 'caption' => 'Instance is inactive (server error)'];
        $formStatus[] = ['code' => self::$IS_HTTPERROR, 'icon' => 'error', 'caption' => 'Instance is inactive (http error)'];
        $formStatus[] = ['code' => self::$IS_PAGENOTFOUND, 'icon' => 'error', 'caption' => 'Instance is inactive (page not found)'];
        return $formStatus;
    }

    private function getSensors()
    {
        $sensor_sds = $this->ReadPropertyBoolean('sensor_sds');
        $sensor_pms = $this->ReadPropertyBoolean('sensor_pms');
        $sensor_dht22 = $this->ReadPropertyBoolean('sensor_dht22');
        $sensor_htu21d = $this->ReadPropertyBoolean('sensor_htu21d');
        $sensor_ppd = $this->ReadPropertyBoolean('sensor_ppd');
        $sensor_bmp180 = $this->ReadPropertyBoolean('sensor_bmp180');
        $sensor_bmp280 = $this->ReadPropertyBoolean('sensor_bmp280');
        $sensor_bme280 = $this->ReadPropertyBoolean('sensor_bme280');
        $sensor_ds18b20 = $this->ReadPropertyBoolean('sensor_ds18b20');
        $sensor_dnms = $this->ReadPropertyBoolean('sensor_dnms');

        $sensors = [];
        if ($sensor_sds) {
            $sensors[] = 'SDS011';
        }
        if ($sensor_pms) {
            $sensors[] = 'PMS';
        }
        if ($sensor_dht22) {
            $sensors[] = 'DHT22';
        }
        if ($sensor_htu21d) {
            $sensors[] = 'HTU21D';
        }
        if ($sensor_ppd) {
            $sensors[] = 'PPD42NS';
        }
        if ($sensor_bmp180) {
            $sensors[] = 'BMP180';
        }
        if ($sensor_bmp280) {
            $sensors[] = 'BMP280';
        }
        if ($sensor_bme280) {
            $sensors[] = 'BME280';
        }
        if ($sensor_ds18b20) {
            $sensors[] = 'DS18B20';
        }
        if ($sensor_dnms) {
            $sensors[] = 'DNMS (Laerm)';
        }

        return $sensors;
    }

    private function getIdents(bool $isLocal)
    {
        // Werte pro Sensor
        $sensor_map = [];
        if ($isLocal) {
            // lokale Installation
            $sensor_map['SDS011'] = ['SDS_P1', 'SDS_P2'];
            $sensor_map['PMS'] = ['PMS_P0', 'PMS_P1', 'PMS_P2'];
            $sensor_map['DHT22'] = ['temperature', 'humidity'];
            $sensor_map['HTU21D'] = ['HTU21D_temperature', 'HTU21D_humidity'];
            $sensor_map['PPD42NS'] = ['P1', 'P2']; // 'durP1', 'ratioP1', 'durP2', 'ratioP2'
            $sensor_map['BMP180'] = ['BMP_temperature', 'BMP_pressure'];
            $sensor_map['BMP280'] = ['BMP280_temperature', 'BMP280_pressure'];
            $sensor_map['BME280'] = ['BME280_temperature', 'BME280_pressure', 'BME280_humidity'];
            $sensor_map['DS18B20'] = ['DS18B20_temperature'];
            $sensor_map['GPS (NEO 6M)'] = []; // 'GPS_lat', 'GPS_lon', 'GPS_height', 'GPS_date', 'GPS_time'
            $sensor_map['DNMS'] = ['DNMS_noise_LAeq', 'DNMS_noise_LA_min', 'DNMS_noise_LA_max'];
        } else {
            // Daten von api.luftdate.info
            $sensor_map['SDS011'] = ['P1', 'P2'];
            $sensor_map['PMS'] = ['P0', 'P1', 'P2'];
            $sensor_map['DHT22'] = ['temperature', 'humidity'];
            $sensor_map['HTU21D'] = ['temperature', 'humidity'];
            $sensor_map['PPD42NS'] = ['P1', 'P2'];
            $sensor_map['BMP180'] = ['temperature', 'pressure', 'pressure_at_sealevel'];
            $sensor_map['BMP280'] = ['temperature', 'pressure', 'pressure_at_sealevel'];
            $sensor_map['BME280'] = ['temperature', 'pressure', 'pressure_at_sealevel', 'humidity'];
            $sensor_map['DS18B20'] = ['temperature'];
            $sensor_map['GPS (NEO 6M)'] = [];
            $sensor_map['DNMS'] = ['noise_LAeq', 'noise_LA_min', 'noise_LA_max'];
        }

        $sensor_sds = $this->ReadPropertyBoolean('sensor_sds');
        $sensor_pms = $this->ReadPropertyBoolean('sensor_pms');
        $sensor_dht22 = $this->ReadPropertyBoolean('sensor_dht22');
        $sensor_htu21d = $this->ReadPropertyBoolean('sensor_htu21d');
        $sensor_ppd = $this->ReadPropertyBoolean('sensor_ppd');
        $sensor_bmp180 = $this->ReadPropertyBoolean('sensor_bmp180');
        $sensor_bmp280 = $this->ReadPropertyBoolean('sensor_bmp280');
        $sensor_bme280 = $this->ReadPropertyBoolean('sensor_bme280');
        $sensor_ds18b20 = $this->ReadPropertyBoolean('sensor_ds18b20');
        $sensor_dnms = $this->ReadPropertyBoolean('sensor_dnms');

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
        if ($sensor_dnms) {
            $idents = array_merge($idents, $sensor_map['DNMS']);
        }

        // Lokale Installation mit Wifi-Stärke
        if ($isLocal) {
            $idents[] = 'signal';
        }

        return $idents;
    }

    private function getIdentMap()
    {
        // Werte-Tabelle mit Bezeichnung und Datentyp
        $ident_map = [];

        // Werte übernehmen
        $ident_map['P0'] = ['name' => 'PM1', 'datatype' => 'pm'];
        $ident_map['P1'] = ['name' => 'PM10', 'datatype' => 'pm'];
        $ident_map['P2'] = ['name' => 'PM2.5', 'datatype' => 'pm'];
        $ident_map['temperature'] = ['name' => 'temperature', 'datatype' => 'temperature'];
        $ident_map['humidity'] = ['name' => 'humidity', 'datatype' => 'humidity'];
        $ident_map['pressure'] = ['name' => 'pressure', 'datatype' => 'pressure'];
        $ident_map['pressure_at_sealevel'] = ['name' => 'absolute pressure', 'datatype' => 'pressure'];

        $ident_map['SDS_P1'] = ['name' => 'PM10', 'datatype' => 'pm'];
        $ident_map['SDS_P2'] = ['name' => 'PM2.5', 'datatype' => 'pm'];
        $ident_map['BMP_temperature'] = ['name' => 'temperature', 'datatype' => 'temperature'];
        $ident_map['BMP_pressure'] = ['name' => 'pressure', 'datatype' => 'pressure'];
        $ident_map['BMP280_temperature'] = ['name' => 'temperature', 'datatype' => 'temperature'];
        $ident_map['BMP280_pressure'] = ['name' => 'pressure', 'datatype' => 'pressure'];
        $ident_map['BME280_temperature'] = ['name' => 'temperature', 'datatype' => 'temperature'];
        $ident_map['BME280_humidity'] = ['name' => 'humidity', 'datatype' => 'humidity'];
        $ident_map['BME280_pressure'] = ['name' => 'pressure', 'datatype' => 'pressure'];

        $ident_map['noise_LAeq'] = ['name' => 'noise', 'datatype' => 'noise'];
        $ident_map['noise_LA_min'] = ['name' => 'noise min', 'datatype' => 'noise'];
        $ident_map['noise_LA_max'] = ['name' => 'noise max', 'datatype' => 'noise'];
        $ident_map['DNMS_noise_LAeq'] = ['name' => 'noise', 'datatype' => 'noise'];
        $ident_map['DNMS_noise_LA_min'] = ['name' => 'noise min', 'datatype' => 'noise'];
        $ident_map['DNMS_noise_LA_max'] = ['name' => 'noise max', 'datatype' => 'noise'];

        $ident_map['signal'] = ['name' => 'wifi-signal', 'datatype' => 'signal'];

        // ignorieren
        $ident_map['samples'] = [];
        $ident_map['min_micro'] = [];
        $ident_map['max_micro'] = [];

        return $ident_map;
    }

    private function maintainVariables($isLocal)
    {
        $sensor_sds = $this->ReadPropertyBoolean('sensor_sds');
        $sensor_pms = $this->ReadPropertyBoolean('sensor_pms');
        $sensor_dht22 = $this->ReadPropertyBoolean('sensor_dht22');
        $sensor_htu21d = $this->ReadPropertyBoolean('sensor_htu21d');
        $sensor_ppd = $this->ReadPropertyBoolean('sensor_ppd');
        $sensor_bmp180 = $this->ReadPropertyBoolean('sensor_bmp180');
        $sensor_bmp280 = $this->ReadPropertyBoolean('sensor_bmp280');
        $sensor_bme280 = $this->ReadPropertyBoolean('sensor_bme280');
        $sensor_ds18b20 = $this->ReadPropertyBoolean('sensor_ds18b20');
        $sensor_dnms = $this->ReadPropertyBoolean('sensor_dnms');

        $ident_map = $this->getIdentMap();
        $idents = $this->getIdents($isLocal);

        $vpos = 1;
        $this->MaintainVariable('LastTransmission', $this->Translate('last transmission'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, true);
        foreach ($ident_map as $ident => $entry) {
            $this->SendDebug(__FUNCTION__, 'ident=' . $ident . ', entry=' . print_r($entry, true), 0);
            $use = in_array($ident, $idents);
            if ($entry == []) {
                continue;
            }
            $name = $entry['name'];
            $datatype = $entry['datatype'];
            switch ($datatype) {
                case 'pm':
                    $this->MaintainVariable($ident, $this->Translate($name), VARIABLETYPE_FLOAT, 'Luftdaten.PM', $vpos++, $use);
                    break;
                case 'temperature':
                    $this->MaintainVariable($ident, $this->Translate($name), VARIABLETYPE_FLOAT, 'Luftdaten.Temperatur', $vpos++, $use);
                    break;
                case 'humidity':
                    $this->MaintainVariable($ident, $this->Translate($name), VARIABLETYPE_FLOAT, 'Luftdaten.Humidity', $vpos++, $use);
                    break;
                case 'signal':
                    $this->MaintainVariable($ident, $this->Translate($name), VARIABLETYPE_INTEGER, 'Luftdaten.Wifi', $vpos++, $use);
                    break;
                case 'pressure':
                    $this->MaintainVariable($ident, $this->Translate($name), VARIABLETYPE_FLOAT, 'Luftdaten.Pressure', $vpos++, $use);
                    break;
                case 'noise':
                    $this->MaintainVariable($ident, $this->Translate($name), VARIABLETYPE_FLOAT, 'Luftdaten.Noise', $vpos++, $use);
                    break;
                default:
                    break;
            }
        }
    }

    private function createGlobals()
    {
        $this->RegisterPropertyBoolean('sensor_sds', false);
        $this->RegisterPropertyBoolean('sensor_pms', false);
        $this->RegisterPropertyBoolean('sensor_dht22', false);
        $this->RegisterPropertyBoolean('sensor_htu21d', false);
        $this->RegisterPropertyBoolean('sensor_ppd', false);
        $this->RegisterPropertyBoolean('sensor_bmp180', false);
        $this->RegisterPropertyBoolean('sensor_bmp280', false);
        $this->RegisterPropertyBoolean('sensor_bme280', false);
        $this->RegisterPropertyBoolean('sensor_ds18b20', false);
        $this->RegisterPropertyBoolean('sensor_dnms', false);

        $this->CreateVarProfile('Luftdaten.PM', VARIABLETYPE_FLOAT, ' µg/m³', 0, 0, 0, 1, 'Snow');
        $this->CreateVarProfile('Luftdaten.Temperatur', VARIABLETYPE_FLOAT, ' °C', -10, 30, 0, 1, 'Temperature');
        $this->CreateVarProfile('Luftdaten.Humidity', VARIABLETYPE_FLOAT, ' %', 0, 0, 0, 0, 'Drops');
        $this->CreateVarProfile('Luftdaten.Pressure', VARIABLETYPE_FLOAT, ' mbar', 0, 0, 0, 0, 'Gauge');
        $this->CreateVarProfile('Luftdaten.Noise', VARIABLETYPE_FLOAT, ' dB(A)', 0, 0, 0, 1, 'Speaker');
        $this->CreateVarProfile('Luftdaten.Wifi', VARIABLETYPE_INTEGER, ' dBm', 0, 0, 0, 0, 'Intensity');
    }

    private function decodeData($sensordatavalues, $isLocal)
    {
        $this->SendDebug(__FUNCTION__, 'sensordatavalues=' . print_r($sensordatavalues, true), 0);

        $idents = $this->getIdents($isLocal);
        $this->SendDebug(__FUNCTION__, 'idents=' . implode(',', $idents), 0);

        $ident_map = $this->getIdentMap();

        foreach ($sensordatavalues as $sensordatavalue) {
            $ident = $sensordatavalue['value_type'];
            $value = $sensordatavalue['value'];
            if (!isset($ident_map[$ident])) {
                echo "no mapping for ident $ident\n";
                continue;
            }
            if (!isset($ident_map[$ident]['datatype'])) {
                continue;
            }
            if (!in_array($ident, $idents)) {
                continue;
            }
            switch ($ident_map[$ident]['datatype']) {
                case 'pm':
                case 'temperature':
                case 'humidity':
                case 'noise':
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
            $this->SendDebug(__FUNCTION__, ' ... ' . $ident . '=' . $value, 0);
            $this->SetValue($ident, $value);
        }
    }
}
