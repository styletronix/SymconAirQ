<?php

class AirQWebHook extends IPSModule
{
    private $hook = "sxairq";

    public function Create()
    {
        parent::Create();

        //We need to call the RegisterHook function on Kernel READY
        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();


        if (IPS_GetKernelRunlevel() == KR_READY) {
            $this->RegisterHook('/hook/' . $this->hook);
        }
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

        if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) {
            $this->RegisterHook('/hook/' . $this->hook);
        }
    }
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

    /**
     * This function will be called by the hook control. Visibility should be protected!
     */
    protected function ProcessHookData()
    {
        $rawData = file_get_contents("php://input");
        $this->SendDebug('SXAirQWebHook', 'Data: ' . $rawData, 0);

        $data = @json_decode($rawData, true);
        if ($data) {
            if (!key_exists('DeviceID', $data)) {
                $msg = 'DeviceID not supplied';
                $this->LogMessage($msg, KL_WARNING);
                print($msg . "\n");
                http_response_code(400);
                return;
            }

            $found = false;
            foreach (IPS_GetInstanceListByModuleID('{75D0E69C-5431-A726-2ADC-D6EBA6B623E9}') as $id) {
                if (GetValueString(IPS_GetObjectIDByIdent('DeviceID', $id)) == $data['DeviceID']) {
                    try {
                        SXAIRQ_StoreDataFromHTTPPost($id, $data);

                        $msg = 'Data OK - Processed by InstanceID ' . $id;
                        $this->LogMessage($msg, KL_DEBUG);
                        print($msg . "\n");
                        http_response_code(200);
                        $found = true;
                        
                    } catch (Exception $ex) {
                        print('Error: ' . $ex->getMessage() . "\n");
                        http_response_code(406);
                        return;
                    }
                }
            }

            if ($found) {
                return;
            }

            $msg = 'No AirQ Instance with supplied DeviceID found.';
            $this->LogMessage($msg, KL_WARNING);
            print($msg . "\n");
            http_response_code(404);
            return;
        }

        $msg = 'No Data supplied. This WebHook awaits data from an AirQ device.';
        $this->LogMessage($msg, KL_WARNING);
        print($msg . "\n");
        http_response_code(400);
    }
}
?>
