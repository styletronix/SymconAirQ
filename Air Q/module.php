<?php

declare(strict_types=1);
class AirQ extends IPSModule
{
	public function Create()
	{
		parent::Create();

		$this->RegisterPropertyBoolean('active');
		$this->RegisterPropertyString('url');
		$this->RegisterPropertyString('password');
		$this->RegisterPropertyInteger("refresh", 5);
		$this->RegisterPropertyBoolean('dynamicValueCreation');

		$this->RegisterVariableInteger('timestamp', 'Zeitpunkt der Messung');
		$this->RegisterVariableString('DeviceID', 'DeviceID') ;
		$this->RegisterVariableFloat('health', 'Gesundheit') ;
		$this->RegisterVariableFloat('performance', 'Leistungsfähigkeit');
		$this->RegisterVariableFloat('virus', 'Virusfrei-Index');
		$this->RegisterVariableFloat('co2', 'Kohlendioxid (CO2)') ;
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
		

		$this->RegisterTimer("update", 0, 'IPS_RequestAction($_IPS["TARGET"], "TimerCallback", "update");');
	}

	public function Destroy()
	{
		parent::Destroy();
	}

	public function ApplyChanges()
	{
		parent::ApplyChanges();

		$this->SetTimerInterval('update', $this->ReadPropertyInteger('refresh'));
		$this->Update();
	}
	private function parseData($data){
		foreach ($data as $key => $value) {
			$valID = $this->GetIDForIdent($key);
			if (!$valID) {
				if ($this->ReadPropertyBoolean ('dynamicValueCreation') == false){
					continue;
				}
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
				SetValue($valID, $value[0]);

				for ($i = 1; $i < count($value); $i++) {
					$indent = 'value_' . $i;
					$val2ID = IPS_GetObjectIDByIdent($indent, $valID);
					if (!$val2ID) {
						if ($this->ReadPropertyBoolean('dynamicValueCreation') == false) {
							continue;
						}

						$val2ID = IPS_CreateVariable(3);
						IPS_SetParent($val2ID, $valID);
						IPS_SetIdent($val2ID, $indent);
						IPS_SetName($val2ID, $key . ' (' . $i . ')');
					}
					SetValue($val2ID, $value[$i]);
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
		$url = trim($this->ReadPropertyString('url'),'\\') . '/data';

		if (!$pw || !$url) {
			$this->SetStatus(204);
			return;
		}

		try{
			$json = $this->getDataFromUrl($url);
		}catch(Exception $ex){
			$this->SetStatus(201);
			return;
		}

		try {
			$data = json_decode($json, true);
		} catch (Exception $ex) {
			$this->SetStatus(202);
			return;
		}

		try {
			$data = $this->decryptString($data['content'], $pw);
		} catch (Exception $ex) {
			$this->SetStatus(203);
			return;
		}

		$this->parseData($data);

		$this->SetStatus(102);
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
