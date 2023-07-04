<?php

declare(strict_types=1);
	class HMRepeat extends IPSModule
	{
		public function Create()
			{
				parent::Create();

				$this->RegisterPropertyString("repeatingVariables", "");

				$this->RegisterAttributeString("repeatingStatus", "[]");

				$this->RegisterScript("ActionScript","Externer ActionScript", "<?\n\nSXHMREP_RequestExternalAction(IPS_GetParent(\$_IPS['SELF']), \$_IPS['VARIABLE'], \$_IPS['VALUE']);");
				$this->RegisterScript("ActionScriptBoolean","Externer ActionScript Boolean", "<?\n\nSXHMREP_RequestExternalActionBoolean(IPS_GetParent(\$_IPS['SELF']), \$_IPS['VARIABLE'], \$_IPS['VALUE']);");
				$this->RegisterScript("ActionScriptFloat","Externer ActionScript Float", "<?\n\nSXHMREP_RequestExternalActionFloat(IPS_GetParent(\$_IPS['SELF']), \$_IPS['VARIABLE'], \$_IPS['VALUE']);");
				$this->RegisterScript("ActionScriptInteger","Externer ActionScript Integer", "<?\n\nSXHMREP_RequestExternalActionInteger(IPS_GetParent(\$_IPS['SELF']), \$_IPS['VARIABLE'], \$_IPS['VALUE']);");
				$this->RegisterScript("ActionScriptString","Externer ActionScript String", "<?\n\nSXHMREP_RequestExternalActionString(IPS_GetParent(\$_IPS['SELF']), \$_IPS['VARIABLE'], \$_IPS['VALUE']);");

				$this->RegisterTimer("UpdateIterval",5,'IPS_RequestAction($_IPS["TARGET"], "TimerCallback", "UpdateIterval");');	
			}

		public function Destroy()
			{
				parent::Destroy();
			}

		
		public function ApplyChanges()
			{
				parent::ApplyChanges();

				$this->UpdateVariables();
			}

		public function MessageSink($TimeStamp, $SenderID, $Message, $Data) 
			{
				$this->SendDebug("MessageSink", "Message from SenderID ".$SenderID." with Message ".$Message."\r\n Data: ".print_r($Data, true), 0);

				switch($Message){
					case OM_CHILDADDED:
					case VM_CREATE:
					case IM_CREATE:
						if ($this->GetRepeatingVariableTreeUp($SenderID)){
							$this->SendDebug("MessageSink", "UpdateVariablesRecursive f�r " .$SenderID, 0);
							$this->UpdateVariablesRecursive([$SenderID]);
						}
						
						break;
					case OM_CHILDREMOVED:
					case OM_UNREGISTER:
					case VM_DELETE:
					case IM_DELETE:

						break;
				}
			}

		private function GetListItems($List){
			$arrString = $this->ReadPropertyString($List);
			if ($arrString){
				return json_decode($arrString, true);
			}	
			return null;
		}

		private function TimerCallback($timer){
			switch($timer) {
				case "UpdateIterval":
					$this->UpdateIterval();
					break;

				default:
					throw new Exception("Invalid TimerCallback");
			}
		}

		public function RequestAction($Ident, $Value) {
			switch($Ident) {
				case "TestVariable":
					// SetValue($this->GetIDForIdent($Ident), $Value);
					$this->SetValue($Ident, $Value);
					break;

				case "TimerCallback":
					$this->TimerCallback($Value);
					break;

				default:
					throw new Exception("Invalid Ident");
			}
		}
	}
