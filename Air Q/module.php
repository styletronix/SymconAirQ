<?php

declare(strict_types=1);
class AirQ extends IPSModule
{
	public function Create()
	{
		parent::Create();

		$this->RegisterAttributeInteger('NewID', 1);

		$this->RegisterPropertyBoolean('active', false);
		$this->RegisterPropertyString('url', 'http://');
		$this->RegisterPropertyString('password', '');
		$this->RegisterPropertyInteger("refresh", 10);
		$this->RegisterPropertyInteger("refreshAverage", 20);
		$this->RegisterPropertyString('Sensors', '');

		$this->RegisterVariableInteger('timestamp', 'Zeitpunkt der Messung');
		$this->RegisterVariableString('DeviceID', 'DeviceID');
		$this->RegisterVariableFloat('health', 'Gesundheit');
		$this->RegisterVariableFloat('performance', 'Leistungsfähigkeit');
		$this->RegisterVariableFloat('virus', 'Virusfrei-Index');
		$this->RegisterVariableFloat('co2', 'Kohlendioxid (CO2)');
		$this->RegisterVariableFloat('co', 'Kohlenmonixod (CO)');
		$this->RegisterVariableFloat('o3', 'Ozon (O3)');
		$this->RegisterVariableFloat('pm1', 'Feinstaub (PM1)');
		$this->RegisterVariableFloat('pm2_5', 'Feinstaub (PM2.5)');
		$this->RegisterVariableFloat('pm10', 'Feinstaub (PM10)');
		$this->RegisterVariableInteger('TypPS', 'Feinstaub Partikelgröße');
		$this->RegisterVariableFloat('oxygen', 'Sauerstoff (O2)');
		$this->RegisterVariableFloat('h2s', 'Schwefelwasserstoff (H2S)');
		$this->RegisterVariableFloat('no2', 'Stickstoffdioxid (NO2)');
		$this->RegisterVariableFloat('tvoc', 'VOC');
		$this->RegisterVariableFloat('temperature', 'Temperatur');
		$this->RegisterVariableFloat('sound', 'Lautstärke');
		$this->RegisterVariableFloat('sound_max', 'Lautstärke (max)');
		$this->RegisterVariableFloat('pressure', 'Luftdruck');
		$this->RegisterVariableFloat('humidity', 'Luftfeuchtigkeit (relativ)');
		$this->RegisterVariableFloat('humidity', 'Luftfeuchtigkeit (absolut)');
		$this->RegisterVariableFloat('dewpt', 'Taupunkt');

		$this->RegisterTimer("update", ($this->ReadPropertyBoolean('active') ? $this->ReadPropertyInteger('refresh') * 1000 : 0), 'IPS_RequestAction($_IPS["TARGET"], "TimerCallback", "update");');
		$this->RegisterTimer("updateAverage", ($this->ReadPropertyBoolean('active') ? $this->ReadPropertyInteger('refreshAverage') * 1000 : 0), 'IPS_RequestAction($_IPS["TARGET"], "TimerCallback", "updateAverage");');
	}

	public function Destroy()
	{
		parent::Destroy();
	}
	public function CreateUnknownVariables()
	{
		$pw = $this->ReadPropertyString('password');
		$url = trim($this->ReadPropertyString('url'), '\\') . '/data';
		$json = $this->getDataFromUrl($url);
		$this->SendDebug("getDataFromUrl", $json, 0);

		$data = json_decode($json, true);
		$this->SendDebug("json_decode", $data['content'], 0);

		$data = $this->decryptString($data['content'], $pw);
		$this->SendDebug("decryptString", $data, 0);
		$data = json_decode($data, true);


		foreach ($data as $key => $value) {
			$valID = @$this->GetIDForIdent($key);
			if (!$valID) {
				switch ($key) {
					case 'DeviceID':
					case 'Status':
						$valID = IPS_CreateVariable(3);
						break;


					case 'TypPS':
						$valID = IPS_CreateVariable(1);
						break;

					case 'uptime':
						$valID = IPS_CreateVariable(1);
					//IPS_SetVariableCustomProfile($valID, ???);

					case 'timestamp':
					case 'measuretime':
						$valID = IPS_CreateVariable(1);
						IPS_SetVariableCustomProfile($valID, "~UnixTimestamp");
						break;

					default:
						$valID = IPS_CreateVariable(2);
				}
				IPS_SetParent($valID, $this->InstanceID);
				IPS_SetIdent($valID, $key);
				IPS_SetName($valID, $key);
			}

			if (is_array($value)) {
				for ($i = 1; $i < count($value); $i++) {
					$indent = 'value_' . $i;
					$val2ID = @IPS_GetObjectIDByIdent($indent, $valID);
					if (!$val2ID) {
						$val2ID = IPS_CreateVariable(3);
						IPS_SetParent($val2ID, $valID);
						IPS_SetIdent($val2ID, $indent);
						IPS_SetName($val2ID, $key . ' (' . $i . ')');
					}
				}
			}
		}
	}

	public function TestConnection()
	{
		try {
			$pw = $this->ReadPropertyString('password');
			if (!$pw) {
				echo ('Password missing');
				return false;
			}
			$url = trim($this->ReadPropertyString('url'), '\\') . '/data';
			if (!$url) {
				echo ('URL missing');
				return false;
			}

			$json = $this->getDataFromUrl($url);
			$this->SendDebug("1. getDataFromUrl", $json, 0);
			if (!$json) {
				echo ('Could not get data from device.');
				return false;
			}

			$data = json_decode($json, true);
			$this->SendDebug("2. json_decode encrypted", $data['content'], 0);
			if (!is_array($data) || count($data) == 0) {
				echo ('Could not get data from device.');
				return false;
			}

			$data = $this->decryptString($data['content'], $pw);
			$this->SendDebug("3. decryptString", $data, 0);
			if (!$data) {
				echo ('Could not decrypt data.');
				return false;
			}

			$data = json_decode($data, true);
			if (!is_array($data) || count($data) == 0) {
				echo ('Could not decrypt data from device.');
				return false;
			}

			echo "OK";
			return true;
		} catch (Exception $ex) {
			$this->SendDebug("Error", $ex, 0);
		}

		echo "Failed";
		return false;
	}

	public function NewID($Sensors)
	{
		$values = [];
		foreach ($Sensors as $target) {
			if ($target['ID'] == 0) {
				$target['ID'] = $this->generateIdentifier();
			}
			foreach ($target['Limits'] as $limit) {
				if ($limit['ID'] == 0) {
					$limit['ID'] = $this->generateIdentifier();
				}
			}
			$values[] = $target;
		}
		$this->UpdateFormField('Sensors', 'values', json_encode($values));
	}

	public function generateIdentifier()
	{
		$newID = $this->ReadAttributeInteger('NewID');
		$this->WriteAttributeInteger('NewID', $newID + 1);
		return $newID;
		// return sprintf('{%04X%04X-%04X-%04X-%04X-%04X%04X%04X}', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
	}

	public function ApplyChanges()
	{
		parent::ApplyChanges();

		$this->SetTimerInterval('update', ($this->ReadPropertyBoolean('active') ? $this->ReadPropertyInteger('refresh') * 1000 : 0));
		$this->SetTimerInterval('updateAverage', ($this->ReadPropertyBoolean('active') ? $this->ReadPropertyInteger('refreshAverage') * 1000 : 0));
		$this->Update();
	}
	private function parseData($data)
	{
		foreach ($data as $key => $value) {
			$valID = @$this->GetIDForIdent($key);
			if (!$valID) {
				continue;
			}

			if (is_array($value)) {
				SetValue($valID, $value[0]);

				for ($i = 1; $i < count($value); $i++) {
					$indent = 'value_' . $i;
					$val2ID = @IPS_GetObjectIDByIdent($indent, $valID);
					if ($val2ID) {
						SetValue($val2ID, $value[$i]);
					}
				}
			} else {
				switch ($key) {
					case 'timestamp':
					case 'measuretime':
						SetValue($valID, $value / 1000);
						break;

					case 'performance':
					case 'health':
						SetValue($valID, $value / 10);
						break;

					default:
						SetValue($valID, $value);
				}
			}
		}
	}
	public function Update()
	{
		$pw = $this->ReadPropertyString('password');
		$url = trim($this->ReadPropertyString('url'), '\\') . '/data';

		if (!$pw || !$url) {
			$this->SetStatus(204);
			return;
		}

		try {
			$json = $this->getDataFromUrl($url);
			$this->SendDebug("getDataFromUrl", $json, 0);
		} catch (Exception $ex) {
			$this->SetStatus(201);
			return;
		}

		try {
			$data = json_decode($json, true);
			if (!$data || !$data['content']) {
				$this->SetStatus(202);
				return;
			}
			$this->SendDebug("json_decode", $data['content'], 0);

		} catch (Exception $ex) {
			$this->SetStatus(202);
			return;
		}

		try {
			$data = $this->decryptString($data['content'], $pw);
			$this->SendDebug("decryptString", $data, 0);
			if (!$data) {
				$this->SetStatus(203);
				return;
			}
			$data = json_decode($data, true);
		} catch (Exception $ex) {
			$this->SetStatus(203);
			return;
		}


		$this->parseData($data);

		$this->SetStatus(102);
	}

	public function CheckLimits()
	{
		$sensorlist = json_decode($this->ReadPropertyString("Sensors"));
		$newSeverity = [];
		for ($x = 0; $y < count($sensorlist); $x++) {
			$sensor = $sensorlist[$x];
			$SensorValueID = @$this->GetIDForIdent($sensor('Sensor'));
			$indentSensorStatus = $sensor['Sensor'] . '_status';
			$SensorStatusID = @$this->GetIDForIdent($indentSensorStatus);

			for ($y = 0; $y < count($sensor['Limits']); $y++) {
				$limit = $sensor['Limits'][$y];

				if ($limit['Timespan'] == 0) {
					$variableID = $SensorValueID;
					$statusID = $SensorStatusID;
					$indentStatus = $indentSensorStatus;
					if (!$statusID) {
						$statusID = IPS_CreateVariable(1);
						IPS_SetParent($statusID, $this->InstanceID);
						IPS_SetIdent($statusID, $indentStatus);
						IPS_SetName($statusID, $sensor['FriendlyName'] . ' - Status');
					}

				} else {
					$indentValue = $sensor['Sensor'] . '_' . $limit['Timespan'];
					$indentStatus = $sensor['Sensor'] . '_' . $limit['Timespan'] . '_status';
					
					$variableID = @$this->GetIDForIdent($indentValue);
					
					if (!$variableID) {
						$variableID = IPS_CreateVariable(2);
						IPS_SetParent($variableID, $this->InstanceID);
						IPS_SetIdent($variableID, $indentValue);
						IPS_SetName($variableID, $sensor['FriendlyName'] . ' (' . $limit['Timespan'] . ')');
					}

					if (!$statusID) {
						$statusID = IPS_CreateVariable(1);
						IPS_SetParent($statusID, $this->InstanceID);
						IPS_SetIdent($statusID, $indentStatus);
						IPS_SetName($statusID, $sensor['FriendlyName'] . ' (' . $limit['Timespan'] . ' - Status)');
					}
				}

				if (!array_key_exists($indentStatus, $newSeverity)){
					$newSeverity[$indentStatus] = 0;
				}

				$value = GetValue($variableID);

				if (($limit['UpperLimit'] != 0 && $value > $limit['UpperLimit']) ||
					($limit['LowerLimit'] != 0 && $value < $limit['LowerLimit']) 
				) {
					if (!$newSeverity[$indentStatus] >= $limit['Severity']){
						$newSeverity[$indentStatus] = $limit['Severity'];
					}
				}
			}
		}

		foreach ($newSeverity as $key => $val){
			$statusID = $this->GetIDForIdent($key);
			if ($statusID){
				SetValue($statusID, $val);
			}
		}
	}

	public function UpdateAverage()
	{
		$sensorlist = json_decode($this->ReadPropertyString("Sensors"));

		foreach ($sensorlist as $sensor) {
			foreach ($sensor['Limits'] as $limit) {
				if ($limit['Timespan'] == 0) {

				}
			}
		}
	}

	private function getDataFromUrl($url)
	{
		$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; MSIE 7.0; Windows NT 6.0; en-US)");
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}

	private function decryptString($data, $password)
	{
		$password = mb_convert_encoding($password, "UTF-8");
		$ciphertext = base64_decode($data);

		$VI = substr($ciphertext, 0, 16);
		$ciphertext = substr($ciphertext, 16);

		if (strlen($password) < 32) {
			for ($i = strlen($password); $i < 32; $i++) {
				$password = $password . '0';
			}
		} elseif (count($password) > 32) {
			$password = substr($password, 0, 32);
		}

		return openssl_decrypt($ciphertext, "AES-256-CBC", $password, OPENSSL_RAW_DATA, $VI);
	}

	private function TimerCallback($timer)
	{
		switch ($timer) {
			case "update":
				$this->Update();
				break;

			case "updateAverage":
				$this->UpdateAverage();
				break;

			default:
				throw new Exception("Invalid TimerCallback");
		}
	}

	public function RequestAction($Ident, $Value)
	{
		switch ($Ident) {
			case "TimerCallback":
				$this->TimerCallback($Value);
				break;

			default:
				throw new Exception("Invalid Ident");
		}
	}
}
