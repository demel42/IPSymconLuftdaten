<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';
require_once __DIR__ . '/../libs/local.php';

class LuftdatenLocal extends IPSModule
{
    use Luftdaten\StubsCommonLib;
    use LuftdatenLocalLib;

    private $ModuleDir;

    public function __construct(string $InstanceID)
    {
        parent::__construct($InstanceID);

        $this->ModuleDir = __DIR__;
    }

    public function Create()
    {
        parent::Create();

        $this->createGlobals();

        $this->RegisterPropertyString('hook', '/hook/Luftdaten');

        $this->RegisterAttributeString('UpdateInfo', '');

        $this->InstallVarProfiles(false);

        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    private function CheckModuleConfiguration()
    {
        $r = [];

        $hook = $this->ReadPropertyString('hook');
        if ($hook != '' && $this->HookIsUsed($hook)) {
            $this->SendDebug(__FUNCTION__, '"hook" is already used', 0);
            $r[] = $this->Translate('Webhook is already used');
        }

        return $r;
    }

    public function MessageSink($timestamp, $senderID, $message, $data)
    {
        parent::MessageSink($timestamp, $senderID, $message, $data);

        if ($message == IPS_KERNELMESSAGE && $data[0] == KR_READY) {
            $hook = $this->ReadPropertyString('hook');
            if ($hook != '') {
                $this->RegisterHook($hook);
            }
        }
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $this->MaintainReferences();

        if ($this->CheckPrerequisites() != false) {
            $this->SetStatus(self::$IS_INVALIDPREREQUISITES);
            return;
        }

        if ($this->CheckUpdate() != false) {
            $this->SetStatus(self::$IS_UPDATEUNCOMPLETED);
            return;
        }

        if ($this->CheckConfiguration() != false) {
            $this->SetStatus(self::$IS_INVALIDCONFIG);
            return;
        }

        $this->maintainVariables(true);

        $info = 'lokal';
        $sensors = $this->getSensors();
        if ($sensors != []) {
            $info .= ' (' . implode(',', $sensors) . ')';
        }
        $this->SetSummary($info);

        $sensors = $this->getSensors();
        if ($sensors == []) {
            $this->SetStatus(self::$IS_NOSENSOR);
            return;
        }

        $this->SetStatus(IS_ACTIVE);

        if (IPS_GetKernelRunlevel() == KR_READY) {
            $hook = $this->ReadPropertyString('hook');
            if ($hook != '') {
                $this->RegisterHook($hook);
            }
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
        if ($uri == $this->ReadPropertyString('hook')) {
            $data = file_get_contents('php://input');
            $this->SendDebug(__FUNCTION__, 'data=' . $data, 0);

            $jdata = json_decode($data, true);
            if ($jdata == '') {
                $this->SetStatus(self::$IS_INVALIDDATA);
                $this->SendDebug(__FUNCTION__, 'malformed data: ' . $data, 0);
                return;
            }
            if (isset($jdata['sensordatavalues']) == false) {
                $this->SetStatus(self::$IS_INVALIDDATA);
                $this->SendDebug(__FUNCTION__, 'malformed data: ' . print_r($jdata, true), 0);
                return;
            }
            $this->decodeData($jdata['sensordatavalues'], true);
            $this->SetValue('LastTransmission', time());
            $this->SetStatus(IS_ACTIVE);
            return;
        }
        http_response_code(404);
        die('File not found!');
    }

    private function GetFormElements()
    {
        $formElements = $this->GetCommonFormElements('receive data from local sensor-station');

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            return $formElements;
        }

        $formElements[] = [
            'type'    => 'ExpansionPanel',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'sensor_sds',
                    'caption' => 'SDS011'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'sensor_pms',
                    'caption' => 'PMS1003, PMS3003, PMS5003, PMS6003, PMS7003'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'sensor_dht22',
                    'caption' => 'DHT22'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'sensor_htu21d',
                    'caption' => 'HTU21D'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'sensor_ppd',
                    'caption' => 'PPD42NS'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'sensor_bmp180',
                    'caption' => 'BMP180'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'sensor_bmp280',
                    'caption' => 'BMP280'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'sensor_bme280',
                    'caption' => 'BME280'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'sensor_ds18b20',
                    'caption' => 'DS18B20'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'sensor_dnms',
                    'caption' => 'DNMS'
                ],
            ],
            'caption' => 'installed sensors (see config-page of sensor-station)'
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

        $formActions[] = $this->GetInformationFormAction();
        $formActions[] = $this->GetReferencesFormAction();

        return $formActions;
    }

    public function RequestAction($ident, $value)
    {
        if ($this->CommonRequestAction($ident, $value)) {
            return;
        }
        switch ($ident) {
            default:
                $this->SendDebug(__FUNCTION__, 'invalid ident ' . $ident, 0);
                break;
        }
    }
}
