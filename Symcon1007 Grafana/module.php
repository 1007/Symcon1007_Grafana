<?php
    
//******************************************************************************
//	Name		:	Grafana Modul.php
//		
//		
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

		$this->SubscribeHook("");
		$this->SubscribeHook("/query");
		$this->SubscribeHook("/search");

		$this->RegisterPropertyString("BasicAuthUser", "");
		$this->RegisterPropertyString("BasicAuthPassword", "");

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

	//**************************************************************************
	// Hook Data auswerten
	//**************************************************************************
	protected function ProcessHookData()
		{
		GLOBAL $_IPS;

		if(!isset($_SERVER['PHP_AUTH_USER']))
			$_SERVER['PHP_AUTH_USER'] = "";
		if(!isset($_SERVER['PHP_AUTH_PW']))
			$_SERVER['PHP_AUTH_PW'] = "";

		$AuthUser = $this->ReadPropertyString("BasicAuthUser");	
		$AuthPassword = $this->ReadPropertyString("BasicAuthPassword");	
		
		$auth = false ;

		if ($_SERVER['PHP_AUTH_USER'] == $AuthUser or $_SERVER['PHP_AUTH_PW'] == $AuthPassword) 
		    {
            $this->SetStatus(102);
            $auth = true;
            }	
			
		$this->SendDebug(__FUNCTION__, "Grafana AUTH:".$_SERVER['PHP_AUTH_USER']."-".$_SERVER['PHP_AUTH_PW'], 0);
        $this->SendDebug(__FUNCTION__, "Modul AUTH:".$AuthUser."-".$AuthPassword, 0);
            
		if ( $auth == false )
			{
			$this->SendDebug(__FUNCTION__, "Modul AUTH fehlerhaft!!", 0);
			$this->SetStatus(202);
			
			return false;	
			}

		$data = file_get_contents("php://input");

		$d = json_decode($data,true);

		if ( isset($d['type'] ) )
			$data_type 	= $d['type'];
		else
			$data_type = "";

		// Sonderfall weil ohne 'type' ????????	
		if ( isset($d['target'] ) )
		   $data_target = $d['target'];

		$data_app 				= @$d['app'];
		$data_requestId 		= @$d['requestId'];
		$data_timezone 			= @$d['timezone'];
		$data_panelId 			= @$d['panelId'];
		$data_dashboardId		= @$d['dashboardId'];
		$data_interval			= @$d['interval'];
		$data_maxDataPoints		= @$d['maxDataPoints'];
		
    
        
    	$this->SendDebug(__FUNCTION__,"Raw:".$data,0);
    	$this->SendDebug(__FUNCTION__,"APP:".$data_app,0);
    	$this->SendDebug(__FUNCTION__,"TYPE:".$data_type,0);
    	$this->SendDebug(__FUNCTION__,"RequestID:".$data_requestId,0);
    	$this->SendDebug(__FUNCTION__,"Timezone:".$data_timezone,0);
    	$this->SendDebug(__FUNCTION__,"PanelID:".$data_panelId,0);
    	$this->SendDebug(__FUNCTION__,"Dashboard:".$data_dashboardId,0);
    	$this->SendDebug(__FUNCTION__,"Intervall:".$data_interval,0);
    	$this->SendDebug(__FUNCTION__,"MaxDatapoints:".$data_maxDataPoints,0);
    	
		
		if (isset($data_target)) 
			{
            $this->SendDebug(__FUNCTION__, "Target ist gesetzt", 0);
            $targetset = true;
        	}	
		else
			{
			$this->SendDebug(__FUNCTION__, "Target ist nicht gesetzt", 0);
			$targetset = false;
			}	

		if ( $data_type == "timeseries" or $targetset == true)		// Request Metrics
			{
			$string = $this->ReturnMetrics();	
			$this->SendDebug(__FUNCTION__,"RequestMetrics:".$string,0);
			echo $string;	
			return;	
			}

		if ( $data_app == "explore" )		// Explore
			{
			$string = $this->ReturnMetrics();	
			$this->SendDebug(__FUNCTION__,"Explore:".$string,0);
			// echo $string;	
			// return;	
			}
	
			
		$x = 0; 

		if ($data_app == "dashboard") ; 	// Manchmal fehlt dashboard
			{
			foreach ($d['targets'] as $target) 
				{
			
				if ( isset ($target['target']) == false )
					{
					$this->SendDebug(__FUNCTION__, "Target is empty! Panel:".$data_panelId." Dashboard:".$data_dashboardId, 0);
                	continue;
					}	

				$data_target[$x] = $target['target'];
				$data_hide[$x] = $target['hide'];

                $this->SendDebug(__FUNCTION__, "Target:".$data_target[$x], 0);
                $this->SendDebug(__FUNCTION__, "Hide:".$data_hide[$x], 0);
                $x++;
            	}
	
			// Keine Targets ?
			if (isset($data_target) == false) 
				{
				$this->SendDebug(__FUNCTION__, "Alle Targets sind leer ! Panel:".$data_panelId." Dashboard:".$data_dashboardId, 0);
                	
                return;
				}
				
            $data_rangefrom = $d['range']['from'];
            $data_rangeto   = $d['range']['to'];
			

			// if ( $data_hide == true )
				// return;

            $this->SendDebug(__FUNCTION__, "From:".$data_rangefrom, 0);
            $this->SendDebug(__FUNCTION__, "To:".$data_rangeto, 0);
			// $this->SendDebug(__FUNCTION__, "Hide:".$data_hide,0);

            $data_rangefrom = strtotime($d['range']['from']);
            $data_rangeto   = strtotime($d['range']['to']);


            $this->SendDebug(__FUNCTION__, "From:".$this->TimestampToDate($data_rangefrom), 0);
            $this->SendDebug(__FUNCTION__, "To:".$this->TimestampToDate($data_rangeto), 0);

			

            // $agstufe = $this->CheckZeitraumForAggregatedValues($data_rangefrom, $data_rangeto);

            $data_starttime = $d['startTime'];
            $data_starttime = intval($data_starttime/1000);
            $data_starttime = $this->TimestampToDate($data_starttime);


            $this->SendDebug(__FUNCTION__, "Startime:".$data_starttime, 0);

            $stringall = "";
            foreach ($data_target as $key => $dataID) {
                $pieces = explode(",", $dataID);

                $ID = $pieces[0];
                $target = @$pieces[1];

                $this->SendDebug(__FUNCTION__, "Data ID:".$ID, 0);
			
				if ($data_hide[$key] == true) 
					{
                    $this->SendDebug(__FUNCTION__, "Data ID: HIDE ", 0);
						continue; 
					}

                if (isset($ID) == false) {
                    continue;
                }
                if ($this->CheckVariable($ID) == false) {
                    continue;
                }
			   
				$agstufe = $this->CheckZeitraumForAggregatedValues($data_rangefrom, $data_rangeto,$ID);

                $array = IPS_GetVariable($ID);
                $typ = $array['VariableType'];

                // Archivdaten fuer eine Variable holen
                $data = $this->GetArchivData($ID, $data_rangefrom, $data_rangeto, $agstufe,$typ);
                // print_r($data);
                $count = count($data);
                $this->SendDebug(__FUNCTION__, "Data Count:".$count, 0);

				if( $count > 9999 )		// Maximale Anzahl Daten erreicht
					{
					if ( $agstufe == 99 )
						$agstufe = 0;
					else 
						$agstufe = $agstufe +1;
					if( $agstufe > 5)
						$agstufe = 5;
						
					$data = $this->GetArchivData($ID, $data_rangefrom, $data_rangeto, $agstufe,$typ);
                
					$count = count($data);
					$this->SendDebug(__FUNCTION__, "2. Versuch Data Count:".$count, 0);

					}

                if ($count > 0) {
                    $string = $this->CreateReturnString($data, $target, $typ, $agstufe);
                    $this->SendDebug(__FUNCTION__, "Data String:".$string, 0);

                    $stringall = $stringall . "" .$string ;
                };
            }
            
            $string = $this->CreateHeaderReturnString($stringall);

            $this->SendDebug(__FUNCTION__, "Data String ALL :".$string, 0);
		
			/* 
			if ( $string == "[]" )	// Keine Daten, dann auch nicht senden
				{
				$string = "[{}]";	
				$this->SendDebug(__FUNCTION__, "Data String ALL leer:", 0);
                // return;
                }	
			*/ 
			
			echo $string;
			
            // $this->sendtest();
			return;

			}
		

		if ($data_app != "dashboard") 	
			$this->SendDebug(__FUNCTION__,"Unbekanntes Telegramm empfangen bzw Testtelegramm Raw:".$data,0);
		

		}

	//******************************************************************************
	//	Teststring erstellen und senden
	//******************************************************************************
	protected function sendtest()
		{

		$t1 = ( time() -3600)*1000;		
		$t2 = ( time() -600)*1000;		

		$s = '[{"target":"pps in","datapoints":[[122,'.$t1.'],[565,'.$t2.']]}]';

		
		$this->SendDebug(__FUNCTION__,$s,0);

		echo $s;
		}
	
		
	//******************************************************************************
	// 	Aggregationsstufe fuer Zeitraeume festlegen
	//	Stufe 0		Stuendliche Aggregation
	// 	Stufe 1		Taegliche Aggregation
	// 	Stufe 2		Woechentliche Aggregation
	// 	Stufe 3		Monatliche Aggregation
	//  Stufe 4		Jaehrliche Aggregation
	//  Stufe 5		5-Minuetige Aggregation
	//  Stufe 6		1-Minuetige Aggregation
	// 	Stufe 99	keine Aggregation 
	//******************************************************************************	
	protected function CheckZeitraumForAggregatedValues($from,$to,$varID)
		{
		$archiv = $this->GetArchivID();
		$aggType = AC_GetAggregationType($archiv,$varID);

		$stufe = 99;
		
		$days = ($to-$from)/(3600*24);	
		$hours = ($to-$from)/(3600);

		if ($aggType == 0) 
			{
			if ($days > 7) 
				{
                $stufe = 0;
            	}
			if ($days > 100) 
				{
                $stufe = 1;
            	}
			}

		
		if ($aggType == 1) 
			{
			$stufe = 0;	
			if ( $hours < 2 )
				$stufe = 5;

			if( $days > 2 )
				$stufe = 1;	
			if( $days > 30 )
				$stufe = 2;		
			
			}
				
		$s = "Anzahl Tage:".$days . " Aggreagationsstufe:".$stufe ." Aggregationstype:".$aggType;

		$this->SendDebug(__FUNCTION__,$s,0);

		return $stufe;

		}	

	//******************************************************************************
	//	alle geloggten Variablen an Grafana senden ( Request Metrics )
	//******************************************************************************
	protected function ReturnMetrics()
		{
		$archiv = $this->GetArchivID();
		$varList = IPS_GetVariableList ();

		$string = '[';

		foreach ($varList as $var )
			{
				$status = AC_GetLoggingStatus($archiv,$var);
				if ( $status == true )
					{
					$name = IPS_GetName($var);
					$parent = IPS_GetParent($var);
					$parent = IPS_GetName($parent);	
					$metrics = $var.",".$name."[".$parent."]";

					$string = $string .'"'.$metrics.'",';	

					}

			}
		
		$string = substr($string, 0, -1);
		$string = $string .']';
		
		return $string;	

		}		

	//******************************************************************************
	//	Rueckgabewerte fuer eine Variable erstellen
	//******************************************************************************
	protected function CreateReturnString($data,$target,$typ,$agstufe)
		{
		
		$string = '{"target":"'.$target.'","datapoints":[';
			
		foreach($data as $value)	
			{
			
			
			if ( $agstufe == 99 )	
				$v = str_replace(",",".",$value['Value']);
			else
				{
				$v = str_replace(",",".",$value['Avg']);		
				}	

			if ( $typ == 0 )	// Boolean	
				{
				if ( $v == true )
					$v = 1;
				else
					$v = 0;	

				}

			$t = $this->TimestampToGrafanaTime($value['TimeStamp']);	
			$string = $string ."[" .$v.",".$t."],";		

			}
		$string = substr($string, 0, -1);
		$string = $string . "]},";

		return $string;

		}
		

	//******************************************************************************
	//	endgueltigen String erstellen
	//******************************************************************************
	protected function CreateHeaderReturnString($string)
		{

		$string = substr($string, 0, -1);	

		$string = "[".$string."]";

		return $string;

		}	

	//******************************************************************************
	//	Werte einer Variablen aus dem Archiv holen
	//******************************************************************************
	protected function GetArchivData($id,$from,$to,$agstufe,$typ)
		{

		$werte = array();

		$archiv = $this->GetArchivID();
		$aggType = AC_GetAggregationType($archiv,$id);


		if ( $agstufe == 99)
			$werte = AC_GetLoggedValues($archiv, $id, $from, $to, 0); 
		else
			{
			$werte = AC_GetAggregatedValues ($archiv,$id,$agstufe,$from,$to,0);	
			}	

			/* 
			foreach($werte as $wert )
			{
				IPS_Logmessage(__CLASS__,$wert['Avg']);


			}
			*/ 

		$reversed = array_reverse($werte);
		
		$count = count($werte);
		

		
		
		if ( $aggType ==1 )
		{
		 // Neuesten Wert loeschen.Wegen Anzeige.Werte sind noch nicht komplett
		 // array_pop($reversed);
		}	

		$erster_Wert = 0;
		$letzter_Wert = 0;

		if ($aggType == 0) 
			{
			$letzter_Wert = @AC_GetLoggedValues($archiv, $id, 0, 0, 1)[0]['Value'];
			
			$array = AC_GetLoggedValues($archiv, $id, 0, 0, 1);
			//if ( empty($array)) 
			//	return false;
			//$letzter_Wert = $array[0]['Value'];
            $erster_Wert  = @AC_GetLoggedValues($archiv, $id, 0, $from-1, 1)[0]['Value'];	// erster Wert vorhanden ?
			}
		
			

		/*
		if ( $typ == 0 )
			{
			if ( $erster_Wert == true)
				$erster_Wert = 1;
			else
				$erster_Wert = 0;	
		
			}
		*/	
		if ( $letzter_Wert == false ) // noch keine Daten geloggt/aktuellen Wert nehmen
			{
			$letzter_Wert = GetValue($id);
			$s = "Noch keine Daten geloggt aktueller Wert :".$letzter_Wert;
			$this->SendDebug(__FUNCTION__,$s,0);
				

			}	
		$s = "Erster Wert:".$erster_Wert." - Letzter Wert:".$letzter_Wert;
		$this->SendDebug(__FUNCTION__,$s,0);


		if ($aggType == 0)	// Bei Zaehler keine erster/letzter Wert wegen komischer Anzeige (kleine Balken)
			{
            // Damit Graph bis zum Ende geht
            if ($agstufe == 99) {
                array_push($reversed, array("TimeStamp"=>$to,"Value"=>$letzter_Wert));
            } else {
                array_push($reversed, array("TimeStamp"=>$to,"Avg"=>$letzter_Wert));
            }
        
            // Damit Graph bis zum Anfang geht
            if ($erster_Wert != false) {
                if ($agstufe == 99) {
                    array_unshift($reversed, array("TimeStamp"=>$from,"Value"=>$erster_Wert));
                } else {
                    array_unshift($reversed, array("TimeStamp"=>$from,"Avg"=>$erster_Wert));
                }
            }
        	}		

		return $reversed;
		}

	//******************************************************************************
	// 	wandelt einen Timestamp in GrafanaTime ( Millisekunden )	
	//******************************************************************************
	protected function TimestampToGrafanaTime($time)
		{
		return $time * 1000;
		}

	//******************************************************************************
	//	wandelt Timestamp in Datum/Uhrzeit
	//******************************************************************************
	protected function TimestampToDate($time)
		{
		return date('d.m.Y H:i:s',$time);			
		}

	//******************************************************************************
	//	Ermittelt die Archiv ID
	//******************************************************************************
	protected function GetArchivID()
		{

		$guid = "{43192F0B-135B-4CE7-A0A7-1475603F3060}";

		$array = IPS_GetInstanceListByModuleID($guid);

		$archive_id =  @$array[0];

		if ( !isset($archive_id) )
			{
			$this->Logmessage("Archive Control nicht gefunden!",KL_WARNING);
			return false;
			}
		
		return $archive_id;

		}


	//******************************************************************************
	//	Variable ueberpruefen (existiert/geloggt)
	//******************************************************************************
	protected function CheckVariable($var)
		{
		$archiv = $this->GetArchivID();
		
		if ( is_numeric($var) == false )
			{
			$this->SendDebug(__FUNCTION__,"Variable ist keine Zahl : ". $var, 0);
			$this->Logmessage("Grafana Variable ID ".$var." Fehler !",KL_WARNING);
			return false;	
			}

		$status = IPS_VariableExists($var);
		
		if ( $status == true )
			$status = AC_GetLoggingStatus($archiv,$var);
		
		if ( $status == false )
			$this->Logmessage("Grafana Variable ID ".$var." Fehler ! Wird nicht geloggt",KL_WARNING);
			
		return $status;
		}
	

	//******************************************************************************
	//	Erstelle Hook
	//******************************************************************************
	protected function SubscribeHook($hook)
		{
		$WebHook = "/hook/Grafana".$hook;

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
	protected function UnregisterHook($hook)
		{
		$WebHook = "/hook/Grafana".$hook;

		$ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
		if (count($ids) > 0)
			{
			$hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
			$found = false;
			foreach ($hooks as $index => $hook)
				{
				if ($hook['Hook'] == $WebHook)
					{
					$found = $index;
					break;
					}
				}
	
			if ($found !== false)
				{
				array_splice($hooks, $index, 1);
				IPS_SetProperty($ids[0], 'Hooks', json_encode($hooks));
				IPS_ApplyChanges($ids[0]);
				}
			}
		}


	//**************************************************************************
	// 	Module loeschen
	//**************************************************************************
	public function Destroy()
		{
		
		if (!IPS_InstanceExists($this->InstanceID)) // Instanz wurde eben gelöscht und existiert nicht mehr
			{
            $this->UnregisterHook("");
            $this->UnregisterHook("/query");
            $this->UnregisterHook("/search");
			}
			

		//Never delete this line!
		parent::Destroy();
		}

	}