<?php
    
//******************************************************************************
//	Name		:	Grafana Modul.php
//	Aufruf		:	
//	Info		:	
//
//******************************************************************************


	//**********************************************************************
	//	
	//**********************************************************************
	class Grafana extends IPSModule
	{
	//**********************************************************************
	//
	//**********************************************************************    
	public function Create()
		{
		//Never delete this line!
		parent::Create();

    }



	//**************************************************************************
	//
	//**************************************************************************    
	public function ApplyChanges()
		{
		//Never delete this line!
		parent::ApplyChanges();


		$this->SetStatus(102);


		}


	//******************************************************************************
	//	Erstelle Hook
	//******************************************************************************
	protected function SubscribeHook()
		{
		$WebHook = "/hook/Grafana".$this->InstanceID;

		$ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
		if (count($ids) > 0) 
			{
			$hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
			$found = false;
			foreach ($hooks as $index => $hook) 
				{
				if ($hook['Hook'] == $WebHook) 
					{
					if ($hook['TargetID'] == $this->InstanceID) 
						{
						$this->SendDebug(__FUNCTION__,"Hook bereits vorhanden : ". $hook['TargetID'], 0);
						return;		// bereits vorhanden
						}
					$hooks[$index]['TargetID'] = $this->InstanceID;
					$found = true;
					}
				}
				
				if (!$found) 
					{
					$hooks[] = ['Hook' => $WebHook, 'TargetID' => $this->InstanceID];
					}
				$this->SendDebug(__FUNCTION__, $WebHook ." erstellt" , 0);
				IPS_SetProperty($ids[0], 'Hooks', json_encode($hooks));
				IPS_ApplyChanges($ids[0]);
			}
		}

		//******************************************************************************
		// Hook loeschen
		//******************************************************************************
		protected function UnregisterHook()
			{
			$WebHook = "/hook/Grafana".$this->InstanceID;

			$ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
			if (count($ids) > 0) {
				$hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
				$found = false;
				foreach ($hooks as $index => $hook) {
					if ($hook['Hook'] == $WebHook) {
						$found = $index;
						break;
					}
				}
	
				if ($found !== false) {
					array_splice($hooks, $index, 1);
					IPS_SetProperty($ids[0], 'Hooks', json_encode($hooks));
					IPS_ApplyChanges($ids[0]);
				}
			}
		}



	//**************************************************************************
	//
	//**************************************************************************    
	public function Destroy()
		{

		$this->UnregisterHook();

		//Never delete this line!
		parent::Destroy();
		}

