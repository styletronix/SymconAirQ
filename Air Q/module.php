<?php

declare(strict_types=1);
class AirQ extends IPSModule
{
	// REaggragation führt oft zu einem Dedlock des Archive Controls... Und somit aller Module und Scripts die darauf zugreifen.
	// Ursache bisher unklar.
	const DEBUG_DoNotReaggreagae = false;


	const ATTRIB_LAST_FILE_IMPORTED = 'lastFileImported';
	const ATTRIB_LAST_FILE_ROW_IMPORTED = 'lastFileRowImported';
	const ATTRIB_NEWID = 'NewID';
	const ATTRIB_DEVICECONFIG = 'DeviceConfig';
	const ATTRIB_IMPORT_CANCEL = 'ImportCancel';

	const MODULE_PREFIX = 'SXAIRQ';

	const IDENT_DEVICEID = 'DeviceID';
	const IDENT_HTML_LIMITS = 'HtmlLimits';

	const TIMER_UPDATE = 'update';
	const TIMER_UPDATEAVERAGE = 'updateAverage';
	const TIMER_UPDATEHISTORICDATA = 'updateHistoricData';

	const PROP_URL = 'url';
	const PROP_ACTIVE = 'active';
	const PROP_MODE = 'mode';
	const PROP_PASSWORD = 'password';
	const PROP_REFRESH = 'refresh';
	const PROP_REFRESH_AVERAGE = 'refreshAverage';
	const PROP_SENSORS = 'Sensors';
	const PROP_WEBHOOKURL = 'WebHookUrl';
	const PROP_WEBHOOKINTERVAL = 'WebHookInterval';

	const ACTION_TIMERCALLBACK = 'TimerCallback';


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

	public function __construct($InstanceID)
	{
		parent::__construct($InstanceID);
	}
	public function Create()
	{
		parent::Create();

		$this->RegisterAttributeInteger(AirQ::ATTRIB_NEWID, 1);

		//Basic Profiles. They can dynamicallly created and changed with UpdateSensorProfiles()
		// They all start with AirQ::MODULE_PREFIX followed by dot (.)
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

		$name = AirQ::MODULE_PREFIX . '.Status';
		if (!IPS_VariableProfileExists($name)) {
			IPS_CreateVariableProfile($name, 1);
			IPS_SetVariableProfileAssociation($name, 0, $this->Translate('OK'), '', 0x00FF00);
			IPS_SetVariableProfileAssociation($name, 1, $this->Translate('Information'), '', 0x0000DD);
			IPS_SetVariableProfileAssociation($name, 2, $this->Translate('Warning'), '', 0xFFFF00);
			IPS_SetVariableProfileAssociation($name, 3, $this->Translate('Danger'), '', 0xFF0000);
		}

		$this->RegisterPropertyBoolean(AirQ::PROP_ACTIVE, false);
		$this->RegisterPropertyString(AirQ::PROP_URL, 'http://');
		$this->RegisterPropertyInteger(AirQ::PROP_MODE, 0);
		$this->RegisterPropertyString(AirQ::PROP_PASSWORD, '');
		$this->RegisterPropertyInteger(AirQ::PROP_REFRESH, 10);
		$this->RegisterPropertyInteger(AirQ::PROP_REFRESH_AVERAGE, 20);
		$this->RegisterPropertyString(AirQ::PROP_SENSORS, '');
		$this->RegisterPropertyString(AirQ::PROP_WEBHOOKURL, $this->GetCallbackURL());
		$this->RegisterPropertyInteger(AirQ::PROP_WEBHOOKINTERVAL, 120);

		$this->RegisterAttributeString(AirQ::ATTRIB_DEVICECONFIG, '');
		$this->RegisterAttributeString(AirQ::ATTRIB_LAST_FILE_IMPORTED, '');
		$this->RegisterAttributeInteger(AirQ::ATTRIB_LAST_FILE_ROW_IMPORTED, 0);
		$this->RegisterAttributeBoolean(AirQ::ATTRIB_IMPORT_CANCEL, false);

		$this->RegisterVariableInteger('timestamp', $this->Translate('Timestamp'), '~UnixTimestamp');
		$this->RegisterVariableString(AirQ::IDENT_DEVICEID, $this->Translate('DeviceID'));
		$this->RegisterVariableString('Status', $this->Translate('Status'));
		$this->RegisterVariableInteger('uptime', $this->Translate('Uptime'), '');
		$this->RegisterVariableInteger('measuretime', $this->Translate('Measuretime'), '');
		$this->RegisterVariableString(AirQ::IDENT_HTML_LIMITS, $this->Translate('HTML-Limits'), '');

		$this->RegisterTimer(AirQ::TIMER_UPDATE, ($this->ReadPropertyBoolean(AirQ::PROP_ACTIVE) ? $this->ReadPropertyInteger(AirQ::PROP_REFRESH) * 1000 : 0), 'IPS_RequestAction($_IPS["TARGET"], "' . AirQ::ACTION_TIMERCALLBACK . '", "' . AirQ::TIMER_UPDATE . '");');
		$this->RegisterTimer(AirQ::TIMER_UPDATEHISTORICDATA, 0, 'IPS_RequestAction($_IPS["TARGET"], "' . AirQ::ACTION_TIMERCALLBACK . '", "' . AirQ::TIMER_UPDATEHISTORICDATA . '");');
		$this->RegisterTimer(AirQ::TIMER_UPDATEAVERAGE, ($this->ReadPropertyBoolean(AirQ::PROP_ACTIVE) ? $this->ReadPropertyInteger(AirQ::PROP_REFRESH_AVERAGE) * 1000 : 0), 'IPS_RequestAction($_IPS["TARGET"], "' . AirQ::ACTION_TIMERCALLBACK . '", "' . AirQ::TIMER_UPDATEAVERAGE . '");');

		if ($this->ReadPropertyInteger(AirQ::PROP_MODE) == 1 && $this->ReadPropertyBoolean(AirQ::PROP_ACTIVE)) {
			$this->SetStatus(205);
			$this->UpdateFormField('WebHookRequiredLabel', 'visible', true);
			$this->UpdateFormField('WebHookRequiredButton', 'visible', true);
		}
	}

	public function Destroy()
	{
		parent::Destroy();
	}

	public function TestConnection()
	{
		try {
			$pw = $this->ReadPropertyString(AirQ::PROP_PASSWORD);
			if (!$pw) {
				echo $this->Translate('Password missing');
				return false;
			}
			$url = trim($this->ReadPropertyString(AirQ::PROP_URL), '\\') . '/data';
			if (!$url) {
				echo $this->Translate('URL missing');
				return false;
			}

			$json = $this->getDataFromUrl($url);
			$this->SendDebug("1. getDataFromUrl", $json, 0);
			if ($json === null) {
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
	// private function UpdateHTML(){
// 	$this->GetIDForIdent(AirQ::IDENT_HTML_LIMITS);

	// }
	public function ApplyChanges()
	{
		parent::ApplyChanges();

		if ($this->ReadPropertyBoolean(AirQ::PROP_ACTIVE) && $this->ReadPropertyInteger(AirQ::PROP_MODE) == 0) {
			$refresh = $this->ReadPropertyInteger(AirQ::PROP_REFRESH) * 1000;
		} else {
			$refresh = 0;
		}

		if ($this->ReadPropertyBoolean(AirQ::PROP_ACTIVE) && $this->ReadPropertyInteger(AirQ::PROP_MODE) == 0) {
			$refreshAverage = $this->ReadPropertyInteger(AirQ::PROP_REFRESH_AVERAGE) * 1000;
		} else {
			$refreshAverage = 0;
		}

		$this->SetTimerInterval(AirQ::TIMER_UPDATE, $refresh);
		$this->SetTimerInterval(AirQ::TIMER_UPDATEAVERAGE, $refreshAverage);

		if ($this->ReadPropertyBoolean(AirQ::PROP_ACTIVE) && $this->ReadPropertyInteger('mode') == 0) {
			$this->Update(true);
		}
	}

	protected function CreateWebhookInstance(){
		$hookId = IPS_GetInstanceListByModuleID('{9D7B695F-659C-4FBC-A6FF-9310E2CA54DD}')[0];
		if (!$hookId) {
			$hookId = IPS_CreateInstance("{9D7B695F-659C-4FBC-A6FF-9310E2CA54DD}");
			IPS_SetName($hookId, "AirQ WebHook");
			IPS_ApplyChanges($hookId);
		}
		return $hookId;
	}

	private function CreateProfileIfNotExists(string $name, int $digits, string $suffix, float $min, float $max, int $type = 2)
	{
		$name = AirQ::MODULE_PREFIX . '.' . $name;
		if (!IPS_VariableProfileExists($name)) {
			IPS_CreateVariableProfile($name, $type);
			IPS_SetVariableProfileDigits($name, $digits);
			IPS_SetVariableProfileText($name, '', ' ' . $suffix);
			IPS_SetVariableProfileValues($name, $min, $max, 1);
		}

		return $name;
	}
	public function UpdateSensorProfiles()
	{
		$data = $this->GetDeviceConfig();
		if ($data === null) {
			return false;
		}

		$sensors = $data['sensors'];

		foreach ($sensors as $sensor) {
			$info = $this->GetSensorInfoBySensorID($data, $sensor);
			if ($info) {
				$unit = $info['Unit'];
				if ($unit && is_array($unit)) {
					$unit = $unit[$sensor];
				}

				$digits = key_exists('Round Digits', $info) ? $info['Round Digits'] : $info['RoundDigits'];
				if ($digits && is_array($digits)) {
					$digits = $digits[$unit];
				}
				if (!$digits) {
					$digits = 0;
				}
				$digits = (int) $digits;


				$profileName = AirQ::MODULE_PREFIX . '.' . $sensor;
				if (!IPS_VariableProfileExists($profileName)) {
					IPS_CreateVariableProfile($profileName, 2);
				}
				if ($digits >= 0) {
					IPS_SetVariableProfileDigits($profileName, $digits);
				}
				if ($unit) {
					$unit = str_replace('^3', '³', $unit);
					$unit = str_replace('^2', '²', $unit);
					$unit = str_replace('deg', '°', $unit);
					$unit = str_replace('u', 'µ', $unit);

					if ($unit == '%') {
						IPS_SetVariableProfileValues($profileName, 0, 100, 1);
					}
					IPS_SetVariableProfileText($profileName, '', ' ' . $unit);
				}
			}
		}
		return true;
	}
	public function UpdateVariableNames()
	{
		$sensorlist = json_decode($this->ReadPropertyString("Sensors"), true);

		foreach ($sensorlist as $sensor) {
			$indentSensorStatus = $sensor['Sensor'] . '_status';
			$indentSensorValue = $sensor['Sensor'];

			$SensorValueID = @$this->GetIDForIdent($indentSensorValue);
			if ($SensorValueID) {
				IPS_SetName($SensorValueID, $sensor['FriendlyName']);
			}
			$SensorValueID = @$this->GetIDForIdent($indentSensorValue . '_dev');
			if ($SensorValueID) {
				IPS_SetName($SensorValueID, $sensor['FriendlyName'] . ' (' . $this->Translate('deviation') . ')');
			}

			$SensorValueID = @$this->GetIDForIdent($indentSensorValue . '_status');
			if ($SensorValueID) {
				IPS_SetName($SensorValueID, $sensor['FriendlyName'] . ' - ' . $this->Translate('Status'));
			}

			foreach ($sensor['Limits'] as $limit) {
				$SensorValueID = @$this->GetIDForIdent($sensor['Sensor'] . '_' . $limit['Timespan']);
				if ($SensorValueID) {
					IPS_SetName($SensorValueID, $sensor['FriendlyName'] . ' (' . $this->minuteTimeSpanToFriendlyName($limit['Timespan']) . ')');
				}

				$SensorValueID = @$this->GetIDForIdent($sensor['Sensor'] . '_' . $limit['Timespan'] . '_status');
				if ($SensorValueID) {
					IPS_SetName($SensorValueID, $sensor['FriendlyName'] . ' (' . $this->minuteTimeSpanToFriendlyName($limit['Timespan']) . ') - Status');
				}
			}
		}
	}
	private function GetSensorInfoBySensorID($config, $sensorid)
	{
		foreach ($config['SensorInfo'] as $key => $val) {
			if (is_array($val['Value Name'])) {
				foreach ($val['Value Name'] as $id) {
					if ($id == $sensorid) {
						return $val;
					}
				}
			} else {
				if ($val['Value Name'] == $sensorid) {
					return $val;
				}
			}
		}
		return null;
	}
	private function GetCallbackURL()
	{
		$cc_id = IPS_GetInstanceListByModuleID('{9486D575-BE8C-4ED8-B5B5-20930E26DE6F}')[0];
		$cc_url = @CC_GetConnectURL($cc_id);

		if ($cc_url) {
			return $cc_url . '/hook/' . strtolower(AirQ::MODULE_PREFIX);
		}

		return null;
	}
	public function SetWebHookConfig()
	{
		$config = [
			'httpPOST' => [
				'URL' => $this->ReadPropertyString(AirQ::PROP_WEBHOOKURL),
				'Headers' => ['Content-Type' => 'application/json'],
				'averages' => true,
				'delay' => $this->ReadPropertyInteger(AirQ::PROP_WEBHOOKINTERVAL)
			]
		];
		$result = $this->SetDeviceConfig($config);
		print_r($result);
		return $result;
	}
	public function RemoveWebHookConfig()
	{
		$config = [
			'httpPOST' => [
				'URL' => null
			]
		];

		$result = $this->SetDeviceConfig($config);
		print_r($result);
		return $result;
	}
	private function GetFriendlySensorName(int $sensorID)
	{
		foreach (AirQ::$defaultSensornames as $key => $val) {
			if (strtolower($key) == strtolower($sensorID)) {
				if ($val['friendlyName']) {
					return $this->Translate($val['friendlyName']);
				}
			}
		}
		return $this->Translate($sensorID);
	}
	public function GetDataDecoded(string $path = '/data')
	{
		$pw = $this->ReadPropertyString(AirQ::PROP_PASSWORD);
		$url = trim($this->ReadPropertyString(AirQ::PROP_URL), '/') . $path;

		if (!$pw || !$url) {
			$this->SetStatus(204);
			return null;
		}

		try {
			$json = $this->getDataFromUrl($url);
			if ($json === null) {
				$this->SetStatus(201);
				return null;
			}
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
	public function SendDataEncoded(string $path, array $data)
	{
		$pw = $this->ReadPropertyString(AirQ::PROP_PASSWORD);
		$url = trim($this->ReadPropertyString(AirQ::PROP_URL), '\\') . $path;

		if (!$pw || !$url) {
			$this->SetStatus(204);
			return null;
		}


		try {
			$request = 'request=' . $this->encryptString(json_encode($data), $pw);
			$this->SendDebug("postDataToUrl", $request, 0);
			$result = $this->postDataToUrl($url, $request);
		} catch (Exception $ex) {
			$this->SetStatus(201);
			return null;
		}

		try {
			$result = json_decode($result, true);
			if (!$result) {
				$this->SetStatus(202);
				return null;
			}

		} catch (Exception $ex) {
			$this->SetStatus(202);
			return null;
		}

		try {
			if ($result['content']) {
				$result['content'] = $this->decryptString($result['content'], $pw);
			}
			if (!$result) {
				$this->SetStatus(203);
				return null;
			}
			return $result;
			//return json_decode($result, true);
		} catch (Exception $ex) {
			$this->SetStatus(203);
			return null;
		}

	}
	public function Update(bool $includeAggregated = false)
	{
		$data = $this->GetDataDecoded();
		if ($data !== null) {
			$this->WriteSensorDataValues($data, $includeAggregated);
			$this->WriteStatusValues($data);

			if ($this->ReadPropertyInteger(AirQ::PROP_MODE) == 1 && $this->ReadPropertyBoolean(AirQ::PROP_ACTIVE)) {
				$this->SetStatus(205);
				$this->UpdateFormField('WebHookRequiredLabel', 'visible', true);
				$this->UpdateFormField('WebHookRequiredButton', 'visible', true);
			}else{
				$this->SetStatus(102);
			}
		}
	}
	private function GetProfileNameForSensor(array $sensor)
	{
		$profileName = AirQ::MODULE_PREFIX . '.' . $sensor['Sensor'];
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
		$timespans = AirQ::$knownTimeSpansMinute;
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
			if (!$sensor['Enabled'] || in_array($sensor['Sensor'], AirQ::$StatusVars)) {
				// Sensor disabled or is in StatusVars'
				continue;
			}

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
				if ($sensor['ignorebelowzero'] && $currentValue < 0.0) {
					$currentValue = 0.0;
				}

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

			$timespanProcessed = [];
			foreach ($sensor['Limits'] as $limit) {
				if (!key_exists('sensorstatus', $timespanProcessed) && ($limit['UpperLimit'] != 0 || $limit['LowerLimit'] != 0)) {
					$this->RegisterVariableInteger(
						$indentSensorStatus,
						$sensor['FriendlyName'] . ' - ' . $this->Translate('Status'),
						AirQ::MODULE_PREFIX . '.Status'
					);
					$this->levelUp($newSeverity, $indentSensorStatus, 0);
					$timespanProcessed['sensorstatus'] = null;
				}

				if ($limit['Timespan'] == 0) {
					if (
						($limit['UpperLimit'] != 0 && $currentValue > $limit['UpperLimit']) ||
						($limit['LowerLimit'] != 0 && $currentValue < $limit['LowerLimit'])
					) {
						$this->levelUp($newSeverity, $indentSensorStatus, $limit['Severity']);
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
						AirQ::MODULE_PREFIX . '.Status'
					);
					$this->levelUp($newSeverity, $indentStatus, 0);

					$t = time();
					if ($includeAggregated && !key_exists($limit['Timespan'], $timespanProcessed)) {
						$rollingAverage = @$this->GetAggregatedRollingAverage($SensorValueID, $t - ($limit['Timespan'] * 60), $t);
						$value = $rollingAverage['Avg'];
						if (!is_nan($value) && !is_infinite($value)) {
							SetValue($variableID, $value);
						}
						$timespanProcessed[$limit['Timespan']] = $value;

					} elseif (key_exists($limit['Timespan'], $timespanProcessed)) {
						$value = $timespanProcessed[$limit['Timespan']];

					} else {
						// Use existing value if aggregation is not selected or already processed

						$value = GetValue($variableID);
						$timespanProcessed[$limit['Timespan']] = $value;
					}
					if (
						($limit['UpperLimit'] != 0 && $value > $limit['UpperLimit']) ||
						($limit['LowerLimit'] != 0 && $value < $limit['LowerLimit'])
					) {
						$this->levelUp($newSeverity, $indentStatus, $limit['Severity']);
						$this->levelUp($newSeverity, $indentSensorStatus, $limit['Severity']);
					}
				}

				$timespanProcessed[] = $limit['Timespan'];
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
		foreach (AirQ::$StatusVars as $StatusVar) {
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
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		//curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; MSIE 7.0; Windows NT 6.0; de-DE)");

		$data = curl_exec($ch);
		$curl_errno = curl_errno($ch);
		//$curl_error = curl_error($ch);
		curl_close($ch);

		if ($curl_errno > 0) {
			return null;
		} else {
			return $data;
		}
	}
	private function postDataToUrl(string $url, $data)
	{
		$ch = curl_init();
		$timeout = 5;

		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array(
				'Content-type: application/x-www-form-urlencoded'
			)
		);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; MSIE 7.0; Windows NT 6.0; de-DE)");
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}

	/**
	 * Returns the first Monday after a given date or date if it already is a monday.
	 */
	private static function getStartOfWeekDate($timestamp)
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
	public function GetAggregatedRollingAverage(int $varId, int $start, int $end, int $archiveControlID = null)
	{
		if (!$archiveControlID) {
			$archiveControlID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
		}

		$avgs = [];

		### Remove Seconds from start and end
		$date = getdate($end);
		$end = mktime($date['hours'], $date['minutes'], 00, $date['mon'], $date['mday'], $date['year']);
		$date = getdate($start);
		$start = mktime($date['hours'], $date['minutes'], 00, $date['mon'], $date['mday'], $date['year']);

		$endtime = $end;
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
			$end = $end - $diffToFit;
			$diff = $end - $start;
		}

		# Get hours to previous midnight
		if ($diff > 0) {
			$date = getdate((int) $end);
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
			$startOfWeek = AirQ::getStartOfWeekDate($end);
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

		// I don't think monthly or yearly aggregated values would make any sense because the most averages needed here are below 1 day or at max. 1 year.
		// Month and Year requires special handling in caloculation because they are not always the same timespan.

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
		$timespanSum = 0;

		foreach ($avgs as &$avg) {
			$timespanInMinutes = $avg['Duration'] / 60;
			$avgCount += $timespanInMinutes;
			$avgSum += $avg['Avg'] * $timespanInMinutes;
			$timespanSum += $avg['Duration'];

			if ($avg['Max'] > $max) {
				$max = $avg['Max'];
			}
			if ($avg['Min'] < $min) {
				$min = $avg['Min'];
			}
		}

		return [
			"Duration" => $timespanSum,
			"Avg" => $avgSum / $avgCount,
			"Max" => $max,
			"Min" => $min,
			"DurationDifference" => $timespanSum - ($endtime - $start),
			"Avgs" => $avgs
		];
	}
	public function GetDeviceConfig()
	{
		$config = $this->GetDataDecoded('/config');
		if ($config !== null) {
			$this->WriteAttributeString(AirQ::ATTRIB_DEVICECONFIG, json_encode($config));
		}
		return $config;
	}
	public function GetDeviceConfigCached()
	{
		$config = $this->ReadAttributeString(AirQ::ATTRIB_DEVICECONFIG);
		if ($config) {
			return json_decode($config, true);
		}
		return null;
	}
	public function SetDeviceConfig(array $data)
	{
		return $this->SendDataEncoded('/config', $data);
	}
	public function StoreDataFromHTTPPost(array $data, bool $aggregate)
	{
		if ($data['DeviceID'] == GetValueString($this->GetIDForIdent(AirQ::IDENT_DEVICEID))) {
			$this->WriteSensorDataValues($data, $aggregate);
			$this->WriteStatusValues($data);
			return true;
		} else {
			return false;
		}
	}
	public function StoreHistoricData(array $data)
	{
		$deviceID = GetValueString($this->GetIDForIdent(AirQ::IDENT_DEVICEID));
		$sensorlist = json_decode($this->ReadPropertyString("Sensors"), true);
		$archiveControlID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
		$sensorData = [];
		$sensormapping = [];

		foreach ($sensorlist as $sensor) {
			if (!$sensor['Enabled'] || in_array($sensor['Sensor'], AirQ::$StatusVars)) {
				// Sensor disabled or is in StatusVars'
				$this->SendDebug("StoreHistoricData", 'Sensor disabled for import: ' . $sensor['Sensor'], 0);
				continue;
			}

			$indentSensorValue = $sensor['Sensor'];
			$item = [
				'config' => $sensor,
				'variableid' => @$this->GetIDForIdent($indentSensorValue),
				'variable2id' => @$this->GetIDForIdent($indentSensorValue . '_dev')
			];

			if ($item['variableid'] > 0 && AC_GetLoggingStatus($archiveControlID, $item['variableid'])) {
				$sensorData[$item['variableid']] = [];

				if ($item['variable2id'] > 0 && AC_GetLoggingStatus($archiveControlID, $item['variable2id'])) {
					$sensorData[$item['variable2id']] = [];
				}

				$sensormapping[$indentSensorValue] = $item;
			}
		}

		foreach ($data as $item) {
			if ($item['DeviceID'] == $deviceID) {
				$timestamp = (int) ($item['timestamp'] / 1000);

				foreach ($item as $key => $value) {
					if (key_exists($key, $sensormapping)) {
						$config = $sensormapping[$key];

						if (is_array($value)) {
							$val = $value[0];
							$val2 = $value[1];
						} else {
							$val = $value;
							$val2 = null;
						}

						$val = ($val + ($config['config']['Offset'] ?? 0.0)) * ($config['config']['Multiplicator'] ?? 1.0);
						if ($config['config']['ignorebelowzero'] && $val < 0.0) {
							$val = 0.0;
						}
						if ($config['variableid'] > 0) {
							$sensorData[$config['variableid']][] = [
								'TimeStamp' => $timestamp,
								'Value' => $val
							];
						}
						if ($config['variable2id'] > 0 && $val2 !== null) {
							$sensorData[$config['variable2id']][] = [
								'TimeStamp' => $timestamp,
								'Value' => $val2
							];
						}
					}
				}
			} else {
				$this->SendDebug("StoreHistoricData", 'Error: DeviceID mismatch', 0);
			}
		}

		$changedVars = [];
		foreach ($sensorData as $key => $value) {
			$this->SendDebug("StoreHistoricData", 'Import ' . count($value) . ' Values for Sensor ID ' . $key, 0);

			$result = AC_AddLoggedValues($archiveControlID, $key, $value);
			if ($result) {
				$changedVars[] = $key;
			} else {
				return false;
			}
		}
		$changedVars = array_values(array_unique($changedVars));
		return $changedVars;
	}
	public function StoreHistoricDataCompleted(array $resultfromStore)
	{
		if (AirQ::DEBUG_DoNotReaggreagae) {
			return;
		}

		$archiveControlID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
		$this->SendDebug("StoreHistoricDataCompleted", 'starting reaggregation of ' . count($resultfromStore) . ' variables.', 0);
		if (count($resultfromStore) > 0) {
			foreach ($resultfromStore as $id) {
				//TODO: Find reason for random DEADLOCK of Archive Control !!!
				AC_ReAggregateVariable($archiveControlID, $id);
			}
		}
	}


	public function GetFileList(string $folder, bool $fromBuffer = false)
	{
		$pw = $this->ReadPropertyString(AirQ::PROP_PASSWORD);
		$url = trim($this->ReadPropertyString(AirQ::PROP_URL), '/');
		if (!$pw || !$url) {
			return null;
		}

		if ($fromBuffer) {
			$url = $url . '/dirbuff';
		} else {
			$url = $url . '/dir?request=' . $this->encryptString($folder, $pw);
		}
		$this->SendDebug("GetFileList", 'URL: ' . $url, 0);

		$encrypted = $this->getDataFromUrl($url);
		if ($encrypted === null) {
			$this->SetStatus(201);
			return null;
		}
		$this->SendDebug("GetFileList", 'encrypted: ' . $encrypted, 0);
		if ($encrypted == '{}') {
			return [];
		}

		$decrypted = $this->decryptString($encrypted, $pw);
		$this->SendDebug("GetFileList", 'decrypted: ' . $decrypted, 0);

		return json_decode($decrypted, true);
	}
	public function GetFileContent(string $filepath, bool $returnUnencryptedOnFailure)
	{
		$pw = $this->ReadPropertyString(AirQ::PROP_PASSWORD);
		$url = trim($this->ReadPropertyString(AirQ::PROP_URL), '/');
		if (!$pw || !$url) {
			return null;
		}

		$url = $url . '/file?request=' . $this->encryptString($filepath, $pw);
		$this->SendDebug("GetFileList", 'URL: ' . $url, 0);

		$encrypted = $this->getDataFromUrl($url);
		if ($encrypted === null) {
			return null;
		}
		$this->SendDebug("GetFileContent", 'encrypted: ' . $encrypted, 0);

		$result = [];
		foreach (explode("\n", $encrypted) as $line) {
			if ($line) {
				$decr = $this->decryptString($line, $pw);
				if ($decr) {
					$result[] = $decr;
				} elseif ($returnUnencryptedOnFailure) {
					$result[] = $line;
				}
			}
		}

		foreach ($result as &$line) {
			$parsed = @json_decode($line, true);
			if ($parsed) {
				$line = $parsed;
			}
		}

		return $result;
	}

	public function UpdateSensorList()
	{
		$data = $this->GetDataDecoded();
		if ($data === null) {
			return false;
		}

		$sensorlist = json_decode($this->ReadPropertyString("Sensors"), true);

		foreach ($data as $key => $val) {
			if (!in_array($key, AirQ::$StatusVars)) {
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

		$this->UpdateFormField(AirQ::PROP_SENSORS, 'values', json_encode($sensorlist));
		return true;
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

		$iv = substr($ciphertext, 0, 16);
		$ciphertext = substr($ciphertext, 16);

		if (strlen($password) < 32) {
			for ($i = strlen($password); $i < 32; $i++) {
				$password = $password . '0';
			}
		} elseif (count($password) > 32) {
			$password = substr($password, 0, 32);
		}

		return openssl_decrypt($ciphertext, "AES-256-CBC", $password, OPENSSL_RAW_DATA, $iv);
	}

	private function encryptString(string $data, string $password)
	{
		$iv = openssl_random_pseudo_bytes(16);

		$password = mb_convert_encoding($password, "UTF-8");
		if (strlen($password) < 32) {
			for ($i = strlen($password); $i < 32; $i++) {
				$password = $password . '0';
			}
		} elseif (count($password) > 32) {
			$password = substr($password, 0, 32);
		}

		$raw = openssl_encrypt($data, "AES-256-CBC", $password, OPENSSL_RAW_DATA, $iv);
		return base64_encode($iv . $raw);

	}

	private function TimerCallback(string $timer)
	{
		switch ($timer) {
			case AirQ::TIMER_UPDATE:
				$this->Update();
				break;

			case AirQ::TIMER_UPDATEAVERAGE:
				$this->Update(true);
				break;

			case AirQ::TIMER_UPDATEHISTORICDATA:
				$this->ImportAllFiles(200);
				break;

			default:
				throw new Exception("Invalid TimerCallback");
		}
	}

	public function RequestAction($Ident, $Value)
	{
		switch ($Ident) {
			case AirQ::ACTION_TIMERCALLBACK:
				$this->TimerCallback($Value);
				break;

			default:
				throw new Exception("Invalid Ident");
		}
	}
	private function IsPathLowerThan($path1, $path2)
	{
		$p1 = explode('/', $path1);
		$p2 = explode('/', $path2);

		for ($x = 0; $x < min(count($p1), count($p2)); $x++) {
			if (is_numeric($p1[$x]) && is_numeric($p2[$x])) {
				if ((int) $p1[$x] < (int) $p2[$x]) {
					return true;
				}
			}
		}
	}
	public function ResetImportFileProgress()
	{
		$this->WriteAttributeString(AirQ::ATTRIB_LAST_FILE_IMPORTED, '0');
		$this->WriteAttributeInteger(AirQ::ATTRIB_LAST_FILE_ROW_IMPORTED, 0);
		print($this->Translate('Reset for imported file state successfull. Next time a Full Sync will be performed.'));
	}

	public function ImportAllFiles_Cancel()
	{
		$this->WriteAttributeBoolean(AirQ::ATTRIB_IMPORT_CANCEL, true);
	}
	public function ImportAllFiles(int $limit = 100)
	{
		$this->UpdateFormField('ProgressAlert', 'visible', true);
		$this->UpdateFormField('ImportProgress', 'caption', $this->Translate('Prepare Import'));

		if (!IPS_SemaphoreEnter('AirQImportFile', 1000)) {
			$txt = $this->Translate('Another import is already running');
			$this->UpdateFormField('ImportProgress', 'caption', $txt);
			echo $txt;
			return false;
		}

		try {
			$this->WriteAttributeBoolean(AirQ::ATTRIB_IMPORT_CANCEL, false);
			$this->UpdateFormField('ImportProgress', 'indeterminate', true);
			$this->UpdateFormField('ImportProgress', 'current', 0);

			$allFiles = [];
			$path = '';

			$lastFileImported = $this->ReadAttributeString(AirQ::ATTRIB_LAST_FILE_IMPORTED);
			$lastFileRowImported = $this->ReadAttributeInteger(AirQ::ATTRIB_LAST_FILE_ROW_IMPORTED);

			if (!$lastFileImported) {
				$lastFileImported = '0';
				$lastFileRowImported = 0;
			}
			if (!$lastFileImported) {
				$lastFileRowImported = 0;
			}

			$this->UpdateFormField('ImportProgress', 'caption', $this->Translate('Get Filelist from AirQ'));
			$this->SendDebug('ImportFile', 'Reading path recursive ' . $path, 0);
			$data = $this->GetFileList($path, false);
			if ($data === null) {
				$txt = $this->Translate('Could not connect to AirQ');
				$this->UpdateFormField('ImportProgress', 'caption', $this->Translate('Reaggregate Variables'));
				echo $txt;

				return;
			}

			$skippedPaths = 0;
			foreach ($data as $year) {
				if ($this->ReadAttributeBoolean(AirQ::ATTRIB_IMPORT_CANCEL)) {
					$this->UpdateFormField('ImportProgress', 'caption', $this->Translate('Import cancelled'));
					break;
				}

				if (is_numeric($year)) {
					if ($this->IsPathLowerThan((string) $year, $lastFileImported)) {
						$skippedPaths++;
						continue;
					}
					$months = $this->GetFileList((string) $year, false);
					foreach ($months as $month) {
						if ($this->ReadAttributeBoolean(AirQ::ATTRIB_IMPORT_CANCEL)) {
							$this->UpdateFormField('ImportProgress', 'caption', $this->Translate('Import cancelled'));
							break;
						}

						if ($this->IsPathLowerThan($year . '/' . $month, $lastFileImported)) {
							$skippedPaths++;
							continue;
						}

						$days = $this->GetFileList($year . '/' . $month, false);
						foreach ($days as $day) {
							if ($this->ReadAttributeBoolean(AirQ::ATTRIB_IMPORT_CANCEL)) {
								$this->UpdateFormField('ImportProgress', 'caption', $this->Translate('Import cancelled'));
								break;
							}

							if ($this->IsPathLowerThan($year . '/' . $month . '/' . $day, $lastFileImported)) {
								$skippedPaths++;
								continue;
							}

							$files = $this->GetFileList($year . '/' . $month . '/' . $day, false);
							foreach ($files as $file) {
								if ($this->ReadAttributeBoolean(AirQ::ATTRIB_IMPORT_CANCEL)) {
									$this->UpdateFormField('ImportProgress', 'caption', $this->Translate('Import cancelled'));
									break;
								}

								if ($this->IsPathLowerThan($year . '/' . $month . '/' . $day . '/' . $file, $lastFileImported)) {
									$skippedPaths++;
									continue;
								}
								$allFiles[] = $year . '/' . $month . '/' . $day . '/' . $file;
							}
						}
					}
				}
			}

			$importResult = [];
			$count = 0;
			$totalCount = count($allFiles);
			$totalRows = 0;

			$this->SendDebug('ImportFile', 'Found ' . $totalCount . ' files to import. Skipped ' . $skippedPaths . ' files and directories.', 0);

			$this->UpdateFormField('ImportProgress', 'maximum', $totalCount);
			$this->UpdateFormField('ImportProgress', 'indeterminate', false);

			foreach ($allFiles as $file) {
				$count++;
				if ($this->ReadAttributeBoolean(AirQ::ATTRIB_IMPORT_CANCEL)) {
					$this->UpdateFormField('ImportProgress', 'caption', $this->Translate('Import cancelled'));
					break;
				}
				$this->UpdateFormField('ImportProgress', 'caption', $this->Translate('Import file') . ' ' . $count . '//' . $totalCount);
				$this->UpdateFormField('ImportProgress', 'current', $count);

				$this->SendDebug('ImportFile', 'Importing file ' . $count . ' of ' . $totalCount, 0);
				$this->LogMessage('Importing Files: ' . $count . ' / ' . $totalCount, KL_NOTIFY);

				$this->WriteAttributeString(AirQ::ATTRIB_LAST_FILE_IMPORTED, $file);
				$this->WriteAttributeInteger(AirQ::ATTRIB_LAST_FILE_ROW_IMPORTED, 0);

				$data = $this->GetFileContent($file, false);
				$this->SendDebug('ImportFile', $file . ' DATA: ' . print_r($data, true), 0);
				if ($data === null) {
					$this->SendDebug('ImportFile', 'Import failed ', 0);
					echo 'Import failed.';
					break;
				}

				$newLastFileRowImported = count($data);
				if ($lastFileImported == $file && $lastFileRowImported > 0) {
					$this->SendDebug('ImportFile', count($data) . ' Rows in File. Resuming import at Row ' . $lastFileRowImported, 0);
					$data = array_slice($data, $lastFileRowImported);
				}
				$newRowsCount = count($data);

				if ($newRowsCount > 0) {
					$this->SendDebug('ImportFile', 'Storing ' . $newRowsCount . ' Rows...', 0);
					$tempResult = $this->StoreHistoricData($data);
					if (!$tempResult) {
						$this->SendDebug('ImportFile', 'Import failed ', 0);
						echo 'Import failed.';
						break;
					}
					$this->SendDebug('ImportFile', 'Total of ' . count($tempResult) . ' Variables affected.', 0);
					$importResult = array_unique(array_merge($importResult, $tempResult));
				}

				$totalRows += $newRowsCount;

				$this->WriteAttributeInteger(AirQ::ATTRIB_LAST_FILE_ROW_IMPORTED, $newLastFileRowImported);

				if ($count >= $limit) {
					$this->LogMessage('Limit of ' . $limit . ' files per Import reached.', KL_NOTIFY);
					$this->SendDebug('ImportFile', 'Limit of ' . $limit . ' files per Import reached.', 0);
					$this->UpdateFormField('ImportProgress', 'caption', $this->Translate('Limit of') . ' ' . $limit . ' ' . $this->Translate('files per Import reached'));
					break;
				}
			}

			$this->UpdateFormField('ImportProgress', 'indeterminate', true);
			$this->UpdateFormField('ImportProgress', 'caption', $this->Translate('Reaggregate Variables'));

			$this->StoreHistoricDataCompleted($importResult);

			$this->UpdateFormField('ImportProgress', 'indeterminate', false);
			echo $this->Translate('Import of') . ' ' . $totalRows . ' ' . $this->Translate('rows successfull');

			if ($this->ReadAttributeBoolean(AirQ::ATTRIB_IMPORT_CANCEL)) {
				echo $this->Translate('Import cancelled by User');
				$this->WriteAttributeBoolean(AirQ::ATTRIB_IMPORT_CANCEL, false);
				return false;
			}


			return true;

		} catch (Exception $ex) {
			echo $this->Translate('An unexpected Exception occured') . ': ' . $ex->getMessage();
			return false;

		} finally {
			IPS_SemaphoreLeave('AirQImportFile');
			$this->UpdateFormField('ProgressAlert', 'visible', false);
		}
	}

	public function Command_Reboot(){
		$this->SetDeviceConfig(
			[
				"reset" => true
			]
		);
	}
	public function Command_Shutdown()
	{
		$this->SetDeviceConfig(
			[
				"shutdown" =>true
			]
		);
	}
	public function Command_AddWifi(string $ssid, string $key, bool $WiFiIsHidden)
	{
		$this->SetDeviceConfig(
			[
				"WiFissid"=> $ssid,
  				"WiFipass"=> $key,
				"WiFihidden"=> $WiFiIsHidden,
  				"reset"=> true
			]
		);
	}
	public function Command_SetRoomType(string $RoomType)
	{
		$this->SetDeviceConfig(
			[
				"RoomType"=> $RoomType
			]
		);
	}
	public function Command_SetRoomSize(float $height, float $area)
	{
		$this->SetDeviceConfig(
			[
				"RoomHeight" => $height,
				"RoomArea" => $area
			]
		);
	}
	public function Command_SetAltitute(float $altitude, float $divergence)
	{
		$this->SetDeviceConfig(
			[
				"Altitude" => [$altitude, $divergence ],
				"reset"=> true
			]
		);
	}
	public function Command_SetLED_Theme(string  $left, string  $right)
	{
		$this->SetDeviceConfig(
			[
				"ledTheme" => [
					"left" => $left, 
					"right" => $right
				]
			]
		);
	}
}
?>
