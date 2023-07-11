<?php

declare(strict_types=1);
class AirQ extends IPSModule
{
	public function Create()
	{
		parent::Create();

		$this->RegisterAttributeInteger('NewID', 1);

		$this->CreateProfileIfNotExists('oxygen', 2, '%', 0, 100);
		$this->CreateProfileIfNotExists('co', 3, 'mg/m³', 0, 100);
		$this->CreateProfileIfNotExists('co2', 0, 'ppm', 0, 10000);
		$this->CreateProfileIfNotExists('o3', 2, 'µg/m', 0, 1000);
		$this->CreateProfileIfNotExists('no2', 1, 'µg/m³', 0, 1000);
		$this->CreateProfileIfNotExists('humidity_abs', 1, 'g/m³', 0, 100);
		$this->CreateProfileIfNotExists('temperature', 2, '°C', -30, 100);
		$this->CreateProfileIfNotExists('dewpt', 2, '°C', 0, 100);
		$this->CreateProfileIfNotExists('TypPs', 1, 'PM', 0, 10);
		$this->CreateProfileIfNotExists('sound', 1, 'dB', 0, 100);
		$this->CreateProfileIfNotExists('sound_max', 1, 'db', 0, 100);
		$this->CreateProfileIfNotExists('humidity', 1, '%', 0, 100);
		$this->CreateProfileIfNotExists('virus', 1, '%', 0, 100);
		$this->CreateProfileIfNotExists('performance', 1, '%', 0, 100);
		$this->CreateProfileIfNotExists('pressure', 1, 'hPa', 500, 1600);
		$this->CreateProfileIfNotExists('tvoc', 0, 'ppb', 0, 10000);
		$this->CreateProfileIfNotExists('h2s', 1, 'µg/m³', 0, 100);
		$this->CreateProfileIfNotExists('performance', 1, '%', 0, 100);
		$this->CreateProfileIfNotExists('pm1', 0, 'µg/m³', 0, 1000);
		$this->CreateProfileIfNotExists('pm10', 0, 'µg/m³', 0, 1000);
		$this->CreateProfileIfNotExists('pm2_5', 0, 'µg/m³', 0, 1000);
		$this->CreateProfileIfNotExists('dCO2dt', 3, '', -1000, 1000);
		$this->CreateProfileIfNotExists('dHdt', 3, '', -1000, 1000);
		$this->CreateProfileIfNotExists('dCO2dt', 2, '', -1000, 1000);

		$name = 'SXAIRQ.Status';
		if (!IPS_VariableProfileExists($name)) {
			IPS_CreateVariableProfile($name, 1);
			IPS_SetVariableProfileAssociation($name, 0, $this->Translate('OK'));
			IPS_SetVariableProfileAssociation($name, 1, $this->Translate('Information'));
			IPS_SetVariableProfileAssociation($name, 2, $this->Translate('Warning'));
			IPS_SetVariableProfileAssociation($name, 3, $this->Translate('Danger'));
		}

		$this->RegisterPropertyBoolean('active', false);
		$this->RegisterPropertyString('url', 'http://');
		$this->RegisterPropertyString('password', '');
		$this->RegisterPropertyInteger("refresh", 10);
		$this->RegisterPropertyInteger("refreshAverage", 20);
		$this->RegisterPropertyString('Sensors', '');

		$this->RegisterVariableInteger('timestamp', $this->Translate('Measure Time'), '~UnixTimestamp');
		$this->RegisterVariableString('DeviceID', $this->Translate('DeviceID'));
		$this->RegisterVariableString('Status', $this->Translate('Status'));
		$this->RegisterVariableInteger('uptime', $this->Translate('Uptime'), '');

		$this->RegisterTimer("update", ($this->ReadPropertyBoolean('active') ? $this->ReadPropertyInteger('refresh') * 1000 : 0), 'IPS_RequestAction($_IPS["TARGET"], "TimerCallback", "update");');
		$this->RegisterTimer("updateAverage", ($this->ReadPropertyBoolean('active') ? $this->ReadPropertyInteger('refreshAverage') * 1000 : 0), 'IPS_RequestAction($_IPS["TARGET"], "TimerCallback", "updateAverage");');
	}

	public function Destroy()
	{
		parent::Destroy();
	}
	// public function CreateUnknownVariables()
	// {
	// 	$data = $this->GetDataDecoded();
	// 	if ($data) {
	// 		foreach ($data as $key => $value) {
	// 			$valID = @$this->GetIDForIdent($key);
	// 			if (!$valID) {
	// 				switch ($key) {
	// 					case 'DeviceID':
	// 					case 'Status':
	// 						$valID = IPS_CreateVariable(3);
	// 						break;


	// 					case 'TypPS':
	// 						$valID = IPS_CreateVariable(1);
	// 						break;

	// 					case 'uptime':
	// 						$valID = IPS_CreateVariable(1);
	// 					//IPS_SetVariableCustomProfile($valID, ???);

	// 					case 'timestamp':
	// 					case 'measuretime':
	// 						$valID = IPS_CreateVariable(1);
	// 						IPS_SetVariableCustomProfile($valID, "~UnixTimestamp");
	// 						break;

	// 					default:
	// 						$valID = IPS_CreateVariable(2);
	// 				}
	// 				IPS_SetParent($valID, $this->InstanceID);
	// 				IPS_SetIdent($valID, $key);
	// 				IPS_SetName($valID, $key);
	// 			}

	// 			if (is_array($value)) {
	// 				for ($i = 1; $i < count($value); $i++) {
	// 					$indent = 'value_' . $i;
	// 					$val2ID = @IPS_GetObjectIDByIdent($indent, $valID);
	// 					if (!$val2ID) {
	// 						$val2ID = IPS_CreateVariable(3);
	// 						IPS_SetParent($val2ID, $valID);
	// 						IPS_SetIdent($val2ID, $indent);
	// 						IPS_SetName($val2ID, $key . ' (' . $i . ')');
	// 					}
	// 				}
	// 			}
	// 		}
	// 	}
	// }

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

		$this->Update(true);
	}
	// private function parseData($data)
	// {
	// 	foreach ($data as $key => $value) {
	// 		$valID = @$this->GetIDForIdent($key);
	// 		if (!$valID) {
	// 			continue;
	// 		}

	// 		if (is_array($value)) {
	// 			SetValue($valID, $value[0]);

	// 			for ($i = 1; $i < count($value); $i++) {
	// 				$indent = 'value_' . $i;
	// 				$val2ID = @IPS_GetObjectIDByIdent($indent, $valID);
	// 				if ($val2ID) {
	// 					SetValue($val2ID, $value[$i]);
	// 				}
	// 			}
	// 		} else {
	// 			switch ($key) {
	// 				case 'timestamp':
	// 				case 'measuretime':
	// 					SetValue($valID, $value / 1000);
	// 					break;

	// 				case 'performance':
	// 				case 'health':
	// 					SetValue($valID, $value / 10);
	// 					break;

	// 				default:
	// 					SetValue($valID, $value);
	// 			}
	// 		}
	// 	}
	// }
	private function CreateProfileIfNotExists($name, $digits, $suffix, $min, $max, $type = 2)
	{
		$name = 'SXAIRQ.' . $name;
		if (!IPS_VariableProfileExists($name)) {
			IPS_CreateVariableProfile($name, $type);
			IPS_SetVariableProfileDigits($name, $digits);
			IPS_SetVariableProfileText($name, '', ' ' . $suffix);
			IPS_SetVariableProfileValues($name, $min, $max, 1);
		}

		return $name;
	}
	public function GetDataDecoded()
	{
		$pw = $this->ReadPropertyString('password');
		$url = trim($this->ReadPropertyString('url'), '\\') . '/data';

		if (!$pw || !$url) {
			$this->SetStatus(204);
			return null;
		}

		try {
			$json = $this->getDataFromUrl($url);
			$this->SendDebug("getDataFromUrl", $json, 0);
		} catch (Exception $ex) {
			$this->SetStatus(201);
			return null;
		}

		try {
			$data = json_decode($json, true);
			if (!$data || !$data['content']) {
				$this->SetStatus(202);
				return null;
			}
			$this->SendDebug("json_decode", $data['content'], 0);

		} catch (Exception $ex) {
			$this->SetStatus(202);
			return null;
		}

		try {
			$data = $this->decryptString($data['content'], $pw);
			$this->SendDebug("decryptString", $data, 0);
			if (!$data) {
				$this->SetStatus(203);
				return null;
			}
			return json_decode($data, true);
		} catch (Exception $ex) {
			$this->SetStatus(203);
			return null;
		}

	}
	public function Update($includeAggregated = false)
	{
		$data = $this->GetDataDecoded();
		if ($data) {
			$this->WriteSensorDataValues($data, $includeAggregated);
			$this->WriteStatusValues($data);

			$this->SetStatus(102);
		}
	}
	private function GetProfileNameForSensor($sensor)
	{
		$profileName = 'SXAIRQ.' . $sensor['Sensor'];
		if (IPS_VariableProfileExists($profileName)) {
			return $profileName;
		} else {
			return '';
		}
	}
	private function GetVariableIDForSensor($sensor)
	{
		return $this->RegisterVariableFloat($sensor['Sensor'], $sensor['FriendlyName'], $this->GetProfileNameForSensor($sensor));
	}
	private function WriteSensorDataValues($data, $includeAggregated = false)
	{
		$sensorlist = json_decode($this->ReadPropertyString("Sensors"), true);
		$newSeverity = [];

		foreach ($sensorlist as $sensor) {
			if (!$sensor['Enabled'] || in_array($sensor['Sensor'], $this->StatusVars)) {
				// Sensor disabled or is in StatusVars'
				continue;
			}
			$statusCreated = false;
			$indentSensorStatus = $sensor['Sensor'] . '_status';
			$indentSensorValue = $sensor['Sensor'];
			if (array_key_exists($indentSensorValue, $data)) {
				if (is_array($data[$indentSensorValue])) {
					$value = $data[$indentSensorValue][0];
					$value2 = $data[$indentSensorValue][1];
				} else {
					$value = $data[$indentSensorValue];
					$value2 = null;
				}
				$currentValue = ($value + ($sensor['Offset'] ?? 0.0)) * ($sensor['Multiplicator'] ?? 1.0);
				$SensorValueID = $this->GetVariableIDForSensor($sensor);
				SetValue($SensorValueID, $currentValue);

				if ($value2) {
					$devID = $this->RegisterVariableFloat(
						$indentSensorValue . '_dev',
						$sensor['FriendlyName'] . ' (' . $this->Translate('deviation') . ')'
					);

					SetValue($devID, $value2);
				}
			}

			foreach ($sensor['Limits'] as $limit) {
				if (!$statusCreated && ($limit['UpperLimit'] != 0 || $limit['LowerLimit'] != 0)) {
					
					$this->RegisterVariableInteger($indentSensorStatus, $sensor['FriendlyName'] . ' - ' . $this->Translate('Status'));
					
					if (!array_key_exists($indentSensorStatus, $newSeverity)) {
						$newSeverity[$indentSensorStatus] = 0;
					}
					$statusCreated = true;
				}

				if ($limit['Timespan'] == 0) {
					$variableID = $SensorValueID;

					if (
						($limit['UpperLimit'] != 0 && $currentValue > $limit['UpperLimit']) ||
						($limit['LowerLimit'] != 0 && $currentValue < $limit['LowerLimit'])
					) {
						if ($limit['Severity'] > $newSeverity[$indentSensorStatus]) {
							$newSeverity[$indentSensorStatus] = $limit['Severity'];
						}
					}

				} elseif ($includeAggregated) {
					$indentValue = $sensor['Sensor'] . '_' . $limit['Timespan'];
					$indentStatus = $sensor['Sensor'] . '_' . $limit['Timespan'] . '_status';
					$variableID = $this->RegisterVariableFloat(
						$indentValue,
						$sensor['FriendlyName'] . ' (' . $limit['Timespan'] . ')',
						$this->GetProfileNameForSensor($sensor)
					);

					$statusVariableID = $this->RegisterVariableInteger(
						$indentStatus,
						$sensor['FriendlyName'] . ' (' . $limit['Timespan'] . ') - Status',
						'SXAIRQ.Status'
					);

					if (!array_key_exists($indentStatus, $newSeverity)) {
						$newSeverity[$indentStatus] = 0;
					}

					$t = time();
					$rollingAverage = $this->GetAggregatedRollingAverage($SensorValueID, $t - ($limit['Timespan'] * 60), $t);
					if ($rollingAverage) {
						$value = $rollingAverage['Avg'];
						SetValue($variableID, $value);

						if (
							($limit['UpperLimit'] != 0 && $value > $limit['UpperLimit']) ||
							($limit['LowerLimit'] != 0 && $value < $limit['LowerLimit'])
						) {
							if (!$newSeverity[$indentStatus] >= $limit['Severity']) {
								$newSeverity[$indentStatus] = $limit['Severity'];
							}
						}
					}
				}
			}
		}

		foreach ($newSeverity as $key => $val) {
			$statusID = @$this->GetIDForIdent($key);
			if ($statusID) {
				SetValue($statusID, $val);
			}
		}
	}
	private $StatusVars = ['timestamp', 'Status', 'uptime', 'DeviceID', 'measuretime'];
	private function WriteStatusValues($data)
	{
		foreach ($this->StatusVars as $StatusVar) {
			if (array_key_exists($StatusVar, $data)) {
				$val = $data[$StatusVar];
				$valID = $this->GetIDForIdent($StatusVar);
				if ($valID) {

					switch ($StatusVar) {
						case 'timestamp':
							$val = $val / 1000;
							break;
					}

					SetValue($valID, $val);
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
	/**
	 * Returns the first Monday after a given date or date if it already is a monday.
	 */
	function getStartOfWeekDate($date)
	{
		if ($date instanceof \DateTime) {
			$date = clone $date;
		} else {
			$date = new \DateTime($date);
		}

		$date->setTime(0, 0, 0);

		if ($date->format('N') == 1) {
			return $date;
		} else {
			return $date->modify('last monday');
		}
	}
	/**
	 * Returns a single Average, Max, Min and Duration from Start to End by using previousely aggregated values for minutes, hours, days and weeks to efficiently calculate a rolling average down to 1 minute resolution.
	 */
	private function GetAggregatedRollingAverage($varId, $start, $end, $archiveControlID = null)
	{
		if (!$archiveControlID) {
			$archiveControlID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
		}

		$endtime = $end;
		$avgs = [];
		$diff = $end - $start;

		#### Get minutes to previous full hour
		$date = getdate($end);
		$fullHr = mktime($date['hours'], 00, 00, $date['mon'], $date['mday'], $date['year']);
		$diffToFit = $end - $fullHr;
		if ($diffToFit > $diff) {
			$diffToFit = $diff;
		}
		$diffToFit = floor($diffToFit / 60) * 60;

		if ($diffToFit > 0) {
			$werte = @AC_GetAggregatedValues($archiveControlID, $varId, 6, $end - $diffToFit, $end - 1, 0);
			if ($werte) {
				$avgs = array_merge($avgs, $werte);
			}
			$end = $fullHr;
			$diff = $end - $start;
		}


		# Get hours to previous midnight
		if ($diff > 0) {
			$date = getdate($end);
			$fullDay = mktime(00, 00, 00, $date['mon'], $date['mday'], $date['year']);
			$diffToFit = $end - $fullDay;
			if ($diffToFit > $diff) {
				$diffToFit = $diff;
			}
			$diffToFit = floor($diffToFit / 3600) * 3600;

			if ($diffToFit >= 3600) {
				$werte = @AC_GetAggregatedValues($archiveControlID, $varId, 0, $end - $diffToFit, $end - 1, 0);
				if ($werte) {
					$avgs = array_merge($avgs, $werte);
				}
				$diff = $diff - $diffToFit;
			}
			$end = $start + $diff;
		}

		#Get days to previous Monday
		if ($diff > 86400) {
			$startOfWeek = getStartOfWeekDate($end);
			$diffToFit = $end - $startOfWeek;
			if ($diffToFit > $diff) {
				$diffToFit = $diff;
			}
			$diffToFit = floor($diffToFit / 86400) * 86400;
			if ($diffToFit >= 86400) {
				$werte = @AC_GetAggregatedValues($archiveControlID, $varId, 1, $end - $diffToFit, $end - 1, 0);
				if ($werte) {
					$avgs = array_merge($avgs, $werte);
				}
				$diff = $diff - $diffToFit;
			}
			$end = $start + $diff;
		}

		// I don't think monthly or yearly aggregated values would make any sense because the most averages needed here are below 1 day or at max. 1 year

		### Now full the rest in Steps of Weeks, Days Hours and Minutes
		while ($diff > 0) {
			if ($diff >= 604800) {
				## Full Week
				$diffToFit = floor($diff / 604800) * 604800;
				$level = 2;
			}
			if ($diff >= 86400) {
				## Full Days
				$diffToFit = floor($diff / 86400) * 86400;
				$level = 1;
			} elseif ($diff >= 3600) {
				## Full Hours
				$level = 0;
				$diffToFit = floor($diff / 3600) * 3600;
			} elseif ($diff >= 60) {
				## Remaining as Minutes
				$level = 6;
				$diffToFit = floor($diff / 60) * 60;
				;
			} else {
				## Ignore everything below 1 minute
				break;
			}

			$end = $start + $diff;
			$werte = @AC_GetAggregatedValues($archiveControlID, $varId, $level, $end - $diffToFit, $end - 1, 0);
			if ($werte) {
				$avgs = array_merge($avgs, $werte);
			}

			$diff = $diff - $diffToFit;
		}

		### Create the overall Average of all collected aggregated values.
		$avgSum = 0;
		$avgCount = 0;
		$max = 0;
		$min = INF;

		foreach ($avgs as $avg) {
			$avgSum = $avgSum + ($avg['Avg'] * $avg['Duration'] / 60);
			$avgCount = $avgCount + (max($avg['Duration'], 1) / 60);
			if ($avg['Max'] > $max) {
				$max = $avg['Max'];
			}
			if ($avg['Min'] < $min) {
				$min = $avg['Min'];
			}
		}

		return [
			"Duration" => $avgCount * 60,
			"Avg" => $avgSum / max($avgCount, 1),
			"Max" => $max,
			"Min" => $min,
			"DurationDifference" => ($avgCount * 60) - ($endtime - $start),
			"Avgs" => $avgs
		];
	}

	/** Decrypt from AES256-CEB  */
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
				$this->Update(true);
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
