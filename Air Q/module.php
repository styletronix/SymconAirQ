<?php

declare(strict_types=1);
class AirQ extends IPSModule
{
	private static $StatusVars = [
		'timestamp',
		'Status',
		'uptime',
		'DeviceID',
		'measuretime'
	];
	private static $defaultSensors = [
		'no2' => [
			'friendlyName' => 'Nitrogen dioxide (NO₂)',
			'suffix' => 'µg/m³'
		],
		'pm1' => [
			'friendlyName' => 'Particulate matter PM₁',
			'suffix' => 'µg/m³'
		],
		'pm2_5' => [
			'friendlyName' => 'Particulate matter PM₂,₅',
			'suffix' => 'µg/m³'
		],
		'pm10' => [
			'friendlyName' => 'Particulate matter PM₁₀',
			'suffix' => 'µg/m³'
		],
		'co2' => [
			'friendlyName' => 'Carbon dioxide (CO₂)',
			'suffix' => 'ppm'
		],
		'oxygen' => [
			'friendlyName' => 'Oxygen (O₂)',
			'suffix' => '%'
		],
		'o3' => [
			'friendlyName' => 'Ozone (O₃)',
			'suffix' => 'µg/m³'
		],
		'so2' => [
			'friendlyName' => 'Sulfur dioxide (SO₂)',
			'suffix' => 'mg/m³'
		],
		'rn' => [
			'friendlyName' => 'Radon (Rn)'
		],
		'humidity_abs' => [
			'friendlyName' => 'Absolute humidity (φ)',
			'suffix' => 'g/m³'
		],
		'humidity' => [
			'friendlyName' => 'Relative humidity (ρ)',
			'suffix' => '%'
		],
		'ch2o' => [
			'friendlyName' => 'Formaldehyde (CH₂O)'
		],
		'ch4' => [
			'friendlyName' => 'Methane (CH₄)'
		],
		'pressure' => [
			'friendlyName' => 'Air pressure (p)',
			'suffix' => 'hPa'
		],
		'sound' => [
			'friendlyName' => 'Noise (Lp)',
			'suffix' => 'dB'
		],
		'sound_max' => [
			'friendlyName' => 'Noise maximum (Lp_max)',
			'suffix' => 'dB'
		],
		'temperature' => [
			'friendlyName' => 'Air temperatur (T)',
			'suffix' => '°C'
		],
		'tvoc' => [
			'friendlyName' => 'Volatile Organic Compounds (VOC)',
			'suffix' => 'ppb'
		],
		'h2s' => [
			'friendlyName' => 'Hydrogen Sulfide (H₂S)',
			'suffix' => 'µg/m³'
		],
		'n2o' => [
			'friendlyName' => 'Nitrous Oxide (N₂O)',
		],
		'nh3' => [
			'friendlyName' => 'Ammonia (NH₃)',
			'suffix' => 'ppm'
		],
		'h2' => [
			'friendlyName' => 'Hydrogen (H₂)',
			'suffix' => '%'
		],
		'cl2' => [
			'friendlyName' => 'Chlorine / chlorine gas (Cl₂)',
			'suffix' => 'ppm'
		],
		'dewpt' => [
			'friendlyName' => 'Dew point',
			'suffix' => '°C'
		],
		'dHdt' => [
			'friendlyName' => 'dH / dt'
		],
		'dCO2dt' => [
			'friendlyName' => 'dCO2 / dt'
		],
		'TypPS' => [
			'friendlyName' => 'Particulate matter type',
			'suffix' => 'PM'
		],
		'health' => [
			'friendlyName' => 'Health',
			'suffix' => '%'
		],
		'performance' => [
			'friendlyName' => 'Performance',
			'suffix' => '%'
		],
		'virus' => [
			'friendlyName' => 'Virus free index',
			'suffix' => '%'
		],
		'Status' => [
			'friendlyName' => 'Status'
		]
	];
	private static $knownTimeSpansMinute = [
		'Year' => 525600,
		'Month' => 43200,
		'Week' => 10080,
		'Day' => 1440,
		'Hour' => 60,
		'Minute' => 1
	];

	public function Create()
	{
		parent::Create();

		$this->RegisterAttributeInteger('NewID', 1);

		$this->CreateProfileIfNotExists('oxygen', 2, '%', 0, 25);
		$this->CreateProfileIfNotExists('co', 3, 'mg/m³', 0, 5700);
		$this->CreateProfileIfNotExists('co2', 0, 'ppm', 0, 5000);
		$this->CreateProfileIfNotExists('o3', 2, 'µg/m', 0, 10000);
		$this->CreateProfileIfNotExists('no2', 1, 'µg/m³', 0, 52000);
		$this->CreateProfileIfNotExists('humidity_abs', 1, 'g/m³', 0, 200);
		$this->CreateProfileIfNotExists('temperature', 2, '°C', -40, 125);
		$this->CreateProfileIfNotExists('dewpt', 2, '°C', -88, 125);
		$this->CreateProfileIfNotExists('TypPs', 1, 'PM', 0, 10);
		$this->CreateProfileIfNotExists('sound', 1, 'dB', 40, 109);
		$this->CreateProfileIfNotExists('sound_max', 1, 'db', 40, 109);
		$this->CreateProfileIfNotExists('humidity', 1, '%', 0, 100);
		$this->CreateProfileIfNotExists('virus', 1, '%', 0, 100);
		$this->CreateProfileIfNotExists('performance', 1, '%', 0, 100);
		$this->CreateProfileIfNotExists('pressure', 1, 'hPa', 300, 1200);
		$this->CreateProfileIfNotExists('tvoc', 0, 'ppb', 0, 60000);
		$this->CreateProfileIfNotExists('h2s', 1, 'µg/m³', 0, 70000);
		$this->CreateProfileIfNotExists('pm1', 0, 'µg/m³', 0, 1000);
		$this->CreateProfileIfNotExists('pm10', 0, 'µg/m³', 0, 1000);
		$this->CreateProfileIfNotExists('pm2_5', 0, 'µg/m³', 0, 1000);
		$this->CreateProfileIfNotExists('dCO2dt', 3, '', -100, 100);
		$this->CreateProfileIfNotExists('dHdt', 3, '', -100, 100);
		$this->CreateProfileIfNotExists('dCO2dt', 2, '', -100, 100);

		$name = 'SXAIRQ.Status';
		if (!IPS_VariableProfileExists($name)) {
			IPS_CreateVariableProfile($name, 1);
			IPS_SetVariableProfileAssociation($name, 0, $this->Translate('OK'), '', 0x00FF00);
			IPS_SetVariableProfileAssociation($name, 1, $this->Translate('Information'), '', 0x0000DD);
			IPS_SetVariableProfileAssociation($name, 2, $this->Translate('Warning'), '', 0xFFFF00);
			IPS_SetVariableProfileAssociation($name, 3, $this->Translate('Danger'), '', 0xFF0000);
		}

		$this->RegisterPropertyBoolean('active', false);
		$this->RegisterPropertyString('url', 'http://');
		$this->RegisterPropertyString('password', '');
		$this->RegisterPropertyInteger("refresh", 10);
		$this->RegisterPropertyInteger("refreshAverage", 20);
		$this->RegisterPropertyString('Sensors', '');

		$this->RegisterVariableInteger('timestamp', $this->Translate('Timestamp'), '~UnixTimestamp');
		$this->RegisterVariableString('DeviceID', $this->Translate('DeviceID'));
		$this->RegisterVariableString('Status', $this->Translate('Status'));
		$this->RegisterVariableInteger('uptime', $this->Translate('Uptime'), '');
		$this->RegisterVariableInteger('measuretime', $this->Translate('Measuretime'), '');


		$this->RegisterTimer("update", ($this->ReadPropertyBoolean('active') ? $this->ReadPropertyInteger('refresh') * 1000 : 0), 'IPS_RequestAction($_IPS["TARGET"], "TimerCallback", "update");');
		$this->RegisterTimer("updateAverage", ($this->ReadPropertyBoolean('active') ? $this->ReadPropertyInteger('refreshAverage') * 1000 : 0), 'IPS_RequestAction($_IPS["TARGET"], "TimerCallback", "updateAverage");');
	}

	public function Destroy()
	{
		parent::Destroy();
	}

	public function TestConnection()
	{
		try {
			$pw = $this->ReadPropertyString('password');
			if (!$pw) {
				echo $this->Translate('Password missing');
				return false;
			}
			$url = trim($this->ReadPropertyString('url'), '\\') . '/data';
			if (!$url) {
				echo $this->Translate('URL missing');
				return false;
			}

			$json = $this->getDataFromUrl($url);
			$this->SendDebug("1. getDataFromUrl", $json, 0);
			if (!$json) {
				echo $this->Translate('Could not get data from device.');
				return false;
			}

			$data = json_decode($json, true);
			$this->SendDebug("2. json_decode encrypted", $data['content'], 0);
			if (!is_array($data) || count($data) == 0) {
				echo $this->Translate('Could not get data from device.');
				return false;
			}

			$data = $this->decryptString($data['content'], $pw);
			$this->SendDebug("3. decryptString", $data, 0);
			if (!$data) {
				echo $this->Translate('Could not decrypt data.');
				return false;
			}

			$data = json_decode($data, true);
			if (!is_array($data) || count($data) == 0) {
				echo $this->Translate('Could not parse decrypted data.');
				return false;
			}

			echo $this->Translate('OK - This is the received Data:') . "\n\n" . json_encode($data);

			return true;
		} catch (Exception $ex) {
			$this->SendDebug($this->Translate("Error"), $ex, 0);
		}

		echo $this->Translate("Failed. See Debug window for details.");
		return false;
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
	private function CreateProfileIfNotExists(string $name, int $digits, string $suffix, float $min, float $max, int $type = 2)
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
	private function GetFriendlySensorName(int $sensorID)
	{
		foreach (self::$defaultSensornames as $key => $val) {
			if (strtolower($key) == strtolower($sensorID)) {
				if ($val['friendlyName']) {
					return $this->Translate($val['friendlyName']);
				}
			}
		}
		return $this->Translate($sensorID);
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
	public function Update(bool $includeAggregated = false)
	{
		$data = $this->GetDataDecoded();
		if ($data) {
			$this->WriteSensorDataValues($data, $includeAggregated);
			$this->WriteStatusValues($data);

			$this->SetStatus(102);
		}
	}
	private function GetProfileNameForSensor(array $sensor)
	{
		$profileName = 'SXAIRQ.' . $sensor['Sensor'];
		if (IPS_VariableProfileExists($profileName)) {
			return $profileName;
		} else {
			return '';
		}
	}

	/**
	 * Returns a String in the form of "1 Year 1 Week 2 Months 6 Days 8 Minutes" based on the given minutes. Details will be rounded up or down based on the $divergence to get a compact string.
	 * @param int $timespan Total Minutes to convert to String.
	 * @param float $divergenceMax Defines how detailed the String should be. 0.00 = Exact Match, 0.05 = 5% Missmatch ok
	 * @return string Returns a string  in the form of "1 Year 1 Week 2 Months 6 Days 8 Minutes"
	 */
	private function minuteTimeSpanToFriendlyName(int $timespan, float $divergenceMax = 0.04)
	{
		$timespans = self::$knownTimeSpansMinute;
		arsort($timespans, SORT_NUMERIC);

		$arr = [];
		$span = null;
		$result = [];
		$total = 0;

		foreach ($timespans as $key => $val) {
			if ($timespan <= 0) {
				break;
			}

			$f = floor($timespan / $val);
			if ($f == 0 && $val / ($total + $timespan) < (1.0 + $divergenceMax)) {
				$f = 1;
			}

			if ($f == 1) {
				$result[] = $f . ' ' . $this->Translate($key);
			} elseif ($f > 1) {
				$result[] = $f . ' ' . $this->Translate($key . 's');
			}

			if ($f > 0) {
				$total = $total + $f * $val;
				if ($total / $timespan > (1.0 - $divergenceMax)) {
					// Ignore remaining if more than 95% of the value has been evaluated because the precision is not required and shorter result is better.
					break;
				}
				$timespan = $timespan - ($f * $val);
			}
		}

		if (count($result) == 0) {
			return $timespan;
		}

		return implode(' ', $result);
	}

	private function GetVariableIDForSensor(array $sensor)
	{
		return $this->RegisterVariableFloat($sensor['Sensor'], $sensor['FriendlyName'], $this->GetProfileNameForSensor($sensor));
	}
	private function WriteSensorDataValues(array $data, bool $includeAggregated = false)
	{
		$sensorlist = json_decode($this->ReadPropertyString("Sensors"), true);
		$newSeverity = [];

		foreach ($sensorlist as $sensor) {
			if (!$sensor['Enabled'] || in_array($sensor['Sensor'], self::$StatusVars)) {
				// Sensor disabled or is in StatusVars'
				continue;
			}
			$statusCreated = false;
			$newSensorSeverity = 0;

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
						$sensor['FriendlyName'] . ' (' . $this->Translate('deviation') . ')',
					);

					SetValue($devID, $value2);
				}
			}

			foreach ($sensor['Limits'] as $limit) {
				if (!$statusCreated && ($limit['UpperLimit'] != 0 || $limit['LowerLimit'] != 0)) {
					$this->RegisterVariableInteger(
						$indentSensorStatus,
						$sensor['FriendlyName'] . ' - ' . $this->Translate('Status'),
						'SXAIRQ.Status'
					);
					$this->levelUp($newSeverity, $indentSensorStatus);
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

				} else {
					$indentValue = $sensor['Sensor'] . '_' . $limit['Timespan'];
					$indentStatus = $sensor['Sensor'] . '_' . $limit['Timespan'] . '_status';
					$variableID = $this->RegisterVariableFloat(
						$indentValue,
						$sensor['FriendlyName'] . ' (' . $this->minuteTimeSpanToFriendlyName($limit['Timespan']) . ')',
						$this->GetProfileNameForSensor($sensor)
					);

					$statusVariableID = $this->RegisterVariableInteger(
						$indentStatus,
						$sensor['FriendlyName'] . ' (' . $this->minuteTimeSpanToFriendlyName($limit['Timespan']) . ') - Status',
						'SXAIRQ.Status'
					);
					$this->levelUp($newSeverity, $indentStatus);

					$t = time();
					if ($includeAggregated) {
						$rollingAverage = @$this->GetAggregatedRollingAverage($SensorValueID, $t - ($limit['Timespan'] * 60), $t);
					} else {
						// Use existing value if aggregation is not selected
						$rollingAverage = [
							'Avg' => GetValue($variableID)
						];
					}

					if ($rollingAverage) {
						$value = $rollingAverage['Avg'];
						if ($includeAggregated) {
							SetValue($variableID, $value);
						}

						if (
							($limit['UpperLimit'] != 0 && $value > $limit['UpperLimit']) ||
							($limit['LowerLimit'] != 0 && $value < $limit['LowerLimit'])
						) {
							$this->levelUp($newSeverity, $indentStatus, $limit['Severity']);
							$this->levelUp($newSeverity, $indentSensorStatus, $limit['Severity']);
						}
					}
				}
			}
		}

		//Transfer final Status to Variables
		foreach ($newSeverity as $key => $val) {
			$statusID = @$this->GetIDForIdent($key);
			if ($statusID) {
				SetValue($statusID, $val);
			}
		}
	}
	private function levelUp(array &$arr, string $indent, int $level = null)
	{
		if (!array_key_exists($indent, $arr) || $level === null) {
			if ($level === null) {
				$level = 0;
			}
			$arr[$indent] = $level;
		} elseif (!$arr[$indent] >= $level) {
			$arr[$indent] = $level;
		}
	}

	private function WriteStatusValues(array $data)
	{
		foreach (self::$StatusVars as $StatusVar) {
			if (array_key_exists($StatusVar, $data)) {
				$val = $data[$StatusVar];
				$valID = @$this->GetIDForIdent($StatusVar);
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
	private function getDataFromUrl(string $url)
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
	private function getStartOfWeekDate($timestamp)
	{
		$date = new \DateTime();
		$date->setTimestamp((int) $timestamp);

		$date->setTime(0, 0, 0);

		if ($date->format('N') == 1) {
			return $date->getTimestamp();
		} else {
			return $date->modify('last monday')->getTimestamp();
		}
	}
	/**
	 * Returns a single Average, Max, Min and Duration from Start to End by using previousely aggregated values for minutes, hours, days and weeks to efficiently calculate a rolling average down to 1 minute resolution.
	 * @param int $varId Variable ID
	 * @param mixed $start	Start time as UnixTime 
	 * @param mixed $end End time as UnixTime
	 * @param mixed $archiveControlID	Optional: ID of the Archive. If not supplied, the default Archiv will be used.
	 * @return array Array of Key, Value pairs.
	 */
	private function GetAggregatedRollingAverage(int $varId, int $start, int $end, int $archiveControlID = null)
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
			$startOfWeek = $this->getStartOfWeekDate($end);
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
	public function UpdateSensorList()
	{
		$data = $this->GetDataDecoded();
		if ($data) {
			$sensorlist = json_decode($this->ReadPropertyString("Sensors"), true);

			foreach ($data as $key => $val) {
				if (!in_array($key, self::$StatusVars)) {
					$found = false;
					foreach ($sensorlist as $sensor) {
						if ($sensor['Sensor'] == $key) {
							$found = true;
							break;
						}
					}
					if (!$found) {
						$sensorlist[] = [
							"Sensor" => $key,
							"FriendlyName" => $this->GetFriendlySensorName($key),
							"Enabled" => false,
							"Limits" => []
						];
					}
				}
			}

			$this->UpdateFormField('Sensors', 'values', json_encode($sensorlist));
		}
	}
	/**
	 * Decrypt a String with AES256
	 * @param string $data	The String to decrypt 
	 * @param string $password	The passwod for decryption
	 * @return bool|string Returns the decrypted String
	 */
	private function decryptString(string $data, string $password)
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

	private function encryptString(string $data, string $password)
	{

	}

	private function TimerCallback(string $timer)
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
