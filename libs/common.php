<?php

declare(strict_types=1);

trait LuftdatenCommonLib
{
    protected function SetValue($Ident, $Value)
    {
        @$varID = $this->GetIDForIdent($Ident);
        if ($varID == false) {
            $this->SendDebug(__FUNCTION__, 'missing variable ' . $Ident, 0);
            return;
        }

        @$ret = parent::SetValue($Ident, $Value);
        if ($ret == false) {
            $this->SendDebug(__FUNCTION__, 'mismatch of value "' . $Value . '" for variable ' . $Ident, 0);
        }
    }

    protected function GetValue($Ident)
    {
        @$varID = $this->GetIDForIdent($Ident);
        if ($varID == false) {
            $this->SendDebug(__FUNCTION__, 'missing variable ' . $Ident, 0);
            return false;
        }

        $ret = parent::GetValue($Ident);
        return $ret;
    }

    private function CreateVarProfile($Name, $ProfileType, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits, $Icon, $Associations = '')
    {
        if (!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, $ProfileType);
            IPS_SetVariableProfileText($Name, '', $Suffix);
            if (in_array($ProfileType, [VARIABLETYPE_INTEGER, VARIABLETYPE_FLOAT])) {
                IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
                IPS_SetVariableProfileDigits($Name, $Digits);
            }
            IPS_SetVariableProfileIcon($Name, $Icon);
            if ($Associations != '') {
                foreach ($Associations as $a) {
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
    private function GetMimeType($extension)
    {
        $lines = file(IPS_GetKernelDirEx() . 'mime.types');
        foreach ($lines as $line) {
            $type = explode("\t", $line, 2);
            if (count($type) == 2) {
                $types = explode(' ', trim($type[1]));
                foreach ($types as $ext) {
                    if ($ext == $extension) {
                        return $type[0];
                    }
                }
            }
        }
        return 'text/plain';
    }

    private function GetArrayElem($data, $var, $dflt)
    {
        $ret = $data;
        $vs = explode('.', $var);
        foreach ($vs as $v) {
            if (!isset($ret[$v])) {
                $ret = $dflt;
                break;
            }
            $ret = $ret[$v];
        }
        return $ret;
    }

    public function GetConfigurationForm()
    {
        $formElements = $this->GetFormElements();
        $formActions = $this->GetFormActions();
        $formStatus = $this->GetFormStatus();

        $form = json_encode(['elements' => $formElements, 'actions' => $formActions, 'status' => $formStatus]);
        if ($form == '') {
            $this->SendDebug(__FUNCTION__, 'json_error=' . json_last_error_msg(), 0);
            $this->SendDebug(__FUNCTION__, '=> formElements=' . print_r($formElements, true), 0);
            $this->SendDebug(__FUNCTION__, '=> formActions=' . print_r($formActions, true), 0);
            $this->SendDebug(__FUNCTION__, '=> formStatus=' . print_r($formStatus, true), 0);
        }
        return $form;
    }

    private function GetStatusText()
    {
        $txt = false;
        $status = $this->GetStatus();
        $formStatus = $this->GetFormStatus();
        foreach ($formStatus as $item) {
            if ($item['code'] == $status) {
                $txt = $item['caption'];
                break;
            }
        }

        return $txt;
    }

    private function InstanceInfo(int $instID)
    {
        $obj = IPS_GetObject($instID);
        $inst = IPS_GetInstance($instID);
        $mod = IPS_GetModule($inst['ModuleInfo']['ModuleID']);
        $lib = IPS_GetLibrary($mod['LibraryID']);

        $s = '';

        $s .= 'Modul "' . $mod['ModuleName'] . '"' . PHP_EOL;
        $s .= '  GUID: ' . $mod['ModuleID'] . PHP_EOL;

        $s .= PHP_EOL;

        $s .= 'Library "' . $lib['Name'] . '"' . PHP_EOL;
        $s .= '  GUID: ' . $lib['LibraryID'] . PHP_EOL;
        $s .= '  Version: ' . $lib['Version'] . PHP_EOL;
        if ($lib['Build'] > 0) {
            $s .= '  Build: ' . $lib['Build'] . PHP_EOL;
        }
        $ts = $lib['Date'];
        $d = $ts > 0 ? date('d.m.Y H:i:s', $ts) : '';
        $s .= '  Date: ' . $d . PHP_EOL;

        $src = '';
        $scID = IPS_GetInstanceListByModuleID('{F45B5D1F-56AE-4C61-9AB2-C87C63149EC3}')[0];
        $scList = SC_GetModuleInfoList($scID);
        foreach ($scList as $sc) {
            if ($sc['LibraryID'] == $lib['LibraryID']) {
                $src = ($src != '' ? ' + ' : '') . 'ModuleStore';
                switch ($sc['Channel']) {
                    case 1:
                        $src .= '/Beta';
                        break;
                    case 2:
                        $src .= '/Testing';
                        break;
                    default:
                        break;
                }
                break;
            }
        }
        $mcID = IPS_GetInstanceListByModuleID('{B8A5067A-AFC2-3798-FEDC-BCD02A45615E}')[0];
        $mcList = MC_GetModuleList($mcID);
        foreach ($mcList as $mc) {
            @$g = MC_GetModule($mcID, $mc);
            if ($g == false) {
                continue;
            }
            if ($g['LibraryID'] == $lib['LibraryID']) {
                @$r = MC_GetModuleRepositoryInfo($mcID, $mc);
                if ($r == false) {
                    continue;
                }
                $url = $r['ModuleURL'];
                if (preg_match('/^([^:]*):\/\/[^@]*@(.*)$/', $url, $p)) {
                    $url = $p[1] . '://' . $p[2];
                }
                $src = ($src != '' ? ' + ' : '') . $url;
                $branch = $r['ModuleBranch'];
                switch ($branch) {
                    case 'master':
                    case 'main':
                        break;
                    default:
                        $src .= ' [' . $branch . ']';
                        break;
                }
                break;
            }
        }
        $s .= '  Source: ' . $src . PHP_EOL;

        return $s;
    }
}
