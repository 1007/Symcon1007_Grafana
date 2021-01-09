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
		
		$this->RegisterPropertyString("BasicAuthUser", "");
		$this->RegisterPropertyString("BasicAuthPassword", "");
		
		$runlevel = IPS_GetKernelRunlevel();
		if ( $runlevel == KR_READY )
			{
			$this->CreateHooks();
			}
		else
			{
            $this->RegisterMessage(0, IPS_KERNELMESSAGE);
			}
				
		}

	//**************************************************************************
	// Inspired by module SymconTest/HookServe
	//**************************************************************************    
    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

        if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) 
        	{
        	$this->LogMessage("GRAFANA KR_Ready", KL_MESSAGE);	
            $this->CreateHooks();
			
        	}
	
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
	// Hooks erstellen
	//**************************************************************************
	protected function CreateHooks()
		{

		$this->LogMessage("GRAFANA: Create Hooks", KL_MESSAGE);	
	
		$this->SubscribeHook("");
		$this->SubscribeHook("/query");
		$this->SubscribeHook("/search");

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
		
		$HookStarttime = time();
		$this->SendDebug(__FUNCTION__, "Hook Startime:".$this->TimestampToDate($HookStarttime), 0);	

		$auth = false ;

		if ($_SERVER['PHP_AUTH_USER'] == $AuthUser and $_SERVER['PHP_AUTH_PW'] == $AuthPassword) 
		    {
            $this->SetStatus(102);
            $auth = true;
			}	
		else
			{
			$this->SetStatus(202);
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
		
	
		
		//$string = $this->ReturnMetrics($data_target);	
		//$this->SendDebug(__FUNCTION__."[".__LINE__."]","RequestMetrics:".$string,0);
		// echo $string;	

        
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
            $this->SendDebug(__FUNCTION__, "Target ist gesetzt [".$data_target."]", 0);
            $targetset = true;
        	}	
		else
			{
			$this->SendDebug(__FUNCTION__, "Target ist nicht gesetzt", 0);
			$targetset = false;
			}	

		if ( $data_type == "timeseries" or $targetset == true)		// Request Metrics
			{
			$string = $this->ReturnMetrics($data_target);	
			$this->SendDebug(__FUNCTION__."[".__LINE__."]","RequestMetrics:".$string,0);
			echo $string;	
			
			return ;	
			}

		if ( $data_app == "explore" )		// Explore
			{
			$string = $this->ReturnMetrics($data_target);	
			$this->SendDebug(__FUNCTION__,"Explore:".$string,0);
			// echo $string;	
			// return;	
			}
	
			
		$x = 0; 

		if ($data_app == "dashboard") ; 	// Manchmal fehlt dashboard
			{
			
			// bei Aufruf durch Browser Meldung ausgeben
			if ( isset($d['targets']) == false )
				{
				echo "Aufruf im Browser wird nicht unterstuetzt";
				return false;	
				}

			foreach ($d['targets'] as $target) 
				{
			
				if ( isset ($target['target']) == false )
					{
					$this->SendDebug(__FUNCTION__, "Target is empty! Panel:".$data_panelId." Dashboard:".$data_dashboardId, 0);
                	continue;
					}	

				$data_target[$x] = $target['target'];


				if ( isset($target['hide']) == false )
					{
					$this->SendDebug(__FUNCTION__.'['.__LINE__.']', "Target Hide is empty! ", 0);
					$data_hide[$x] = false ;
					}
				else
					{
					$data_hide[$x] = $target['hide'];
					}
				
				$data_data[$x]	= false;
				$data_data[$x] = @$target['data'];	// Additional Data

				

                $this->SendDebug(__FUNCTION__.'['.__LINE__.']', "Target:".$data_target[$x], 0);
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

            // $this->SendDebug(__FUNCTION__, "From:".$data_rangefrom, 0);
            // $this->SendDebug(__FUNCTION__, "To:".$data_rangeto, 0);
			// $this->SendDebug(__FUNCTION__, "Hide:".$data_hide,0);

            $data_rangefrom = strtotime($d['range']['from']);
            $data_rangeto   = strtotime($d['range']['to']);

			// Endzeit liegt in der Zukunft
			if ( $data_rangeto > time() )
				$data_rangeto = time();
			
            $this->SendDebug(__FUNCTION__, "From:".$this->TimestampToDate($data_rangefrom), 0);
            $this->SendDebug(__FUNCTION__, "To:".$this->TimestampToDate($data_rangeto), 0);

			

            // $agstufe = $this->CheckZeitraumForAggregatedValues($data_rangefrom, $data_rangeto);

            $data_starttime = $d['startTime'];
            $data_starttime = intval($data_starttime/1000);
            $data_starttime = $this->TimestampToDate($data_starttime);


            $this->SendDebug(__FUNCTION__, "Startime:".$data_starttime, 0);

            $stringall = "";
			$loop = 0;

			
			// Beginn neue Version
			// ************** Stopuhr Start
			// microtime nicht auf allen Systemen
			// $microtimestart = microtime(true);

			foreach ($data_target as $key => $dataID) 
				{
                $pieces = explode(",", $dataID);

                $ID = $pieces[0];
                $target = @$pieces[1];

                $this->SendDebug(__FUNCTION__, "Data ID:".$ID, 0);
			
				if ($data_hide[$key] == true) 
					{
                    $this->SendDebug(__FUNCTION__, "Data ID: HIDE ", 0);
					continue; 
					}

				$additional_data = $this->GetAdditionalData($data_data[$key]);

					
				$data_additional = false ;
				
				$add = -1;	// 0 nicht moeglich
				if ($data_data[$key] == true) 
					{
					$data_additional = $data_data[$key];
							
					}

					
				if (isset($ID) == false) 
					{
					continue;
					}
				
				// checken ob exist und geloggt	
				if ($this->CheckVariable($ID) == false) 
					{
                    continue;
                	}
			   
				$array = IPS_GetVariable($ID);
				$typ = $array['VariableType'];

				$RecordLimit = 9999;
				$RecordLimit = IPS_GetOption('ArchiveRecordLimit') - 1 ;

				$AggregationsStufe = $additional_data['Aggregationsstufe'];

				// Besser hier, da fuer jeden Graph eigene Stufe
				$agstufe = $this->CheckZeitraumForAggregatedValues($data_rangefrom, $data_rangeto,$ID,$AggregationsStufe);
				// $agstufe = 99; // Versuch 1

				
				// Archivdaten fuer eine Variable holen
				$data = $this->GetArchivData($ID, $data_rangefrom, $data_rangeto, $agstufe,$typ);       
				
				if($data == FALSE)	// wird nicht geloggt
					continue;
				
                $count = count($data);
                $this->SendDebug(__FUNCTION__, "1. Versuch Data Count:".$count, 0);

				if( $count > $RecordLimit )		// Maximale Anzahl Daten erreicht
					{	
					$agstufe = 6; // 1 minuetig		
					$data = $this->GetArchivData($ID, $data_rangefrom, $data_rangeto, $agstufe,$typ);
					if ( $data == false )
						$counts = "Fehler";
					else
						$count = count($data);
					$this->SendDebug(__FUNCTION__, "2. Versuch Data Count:".$count, 0);
					}

				if( $count > $RecordLimit or $count == false )		// Maximale Anzahl Daten erreicht
					{	
					$agstufe = 5; // 5 minuetig	Problem !!!	
					$data = $this->GetArchivData($ID, $data_rangefrom, $data_rangeto, $agstufe,$typ);
					if ( $data == false )
						$counts = "Fehler";
					else
						$count = count($data);
					$this->SendDebug(__FUNCTION__, "3. Versuch Data Count:".$count, 0);
					}
                 
				if( $count > $RecordLimit or $count == false )		// Maximale Anzahl Daten erreicht
					{	
					$agstufe = 0; // stuendlich		
					$data = $this->GetArchivData($ID, $data_rangefrom, $data_rangeto, $agstufe,$typ);
					$count = count($data);
					$this->SendDebug(__FUNCTION__, "4. Versuch Data Count:".$count, 0);
					}

				if( $count > $RecordLimit )		// Maximale Anzahl Daten erreicht
					{	
					$agstufe = 1; // taeglich		
					$data = $this->GetArchivData($ID, $data_rangefrom, $data_rangeto, $agstufe,$typ);
					$count = count($data);
					$this->SendDebug(__FUNCTION__, "5. Versuch Data Count:".$count, 0);
					}


				$DataOffset = $additional_data['DataOffset'];
				$TimeOffset = $additional_data['TimeOffset'];

				if ($count > 0) 
					{
                    $string = $this->CreateReturnString($data, $target, $typ, $agstufe,$data_additional,$DataOffset,$TimeOffset,$additional_data);
                    $this->SendDebug(__FUNCTION__, "Data String:".$string, 0);

                    $stringall = $stringall . "" .$string ;
					};
					

            }








			// *************** Stoppuhr Ende
			// $microtimesende = microtime(true);
			// $microtime = $microtimesende - $microtimestart;
			// $this->SendDebug(__FUNCTION__, "Microtime :".$microtime, 0);
			// Ende neue Version

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
			
			$HookEndtime = time();
			$HookLaufzeit = $HookEndtime - $HookStarttime; 
			$this->SendDebug(__FUNCTION__, "Hook Endtime:".$this->TimestampToDate($HookEndtime), 0);
			$this->SendDebug(__FUNCTION__, "Hook Laufzeit:".$HookLaufzeit. " Sekunden", 0);

            // $this->sendtest();
			return;

			}
		

		if ($data_app != "dashboard") 	
			$this->SendDebug(__FUNCTION__,"Unbekanntes Telegramm empfangen bzw Testtelegramm Raw:".$data,0);
		

		}

	//******************************************************************************
	//	Additional JSON Data auswerten
	//******************************************************************************
	protected function GetAdditionalData($data)
		{
		$AdditionalData = array();
		
		$this->SendDebug(__FUNCTION__, "" , 0);

		if ( is_array($data ) )
		foreach ($data as $key => $value)
			{
            $this->SendDebug(__FUNCTION__, "Input-".$key ."[" . $value . "]", 0);
            }	
			
		if ( isset($data['Aggregationsstufe']) == false )
            $AdditionalData['Aggregationsstufe'] = -1;
		else	 
			$AdditionalData['Aggregationsstufe'] = 	$data['Aggregationsstufe'];	

		if ( isset($data['AggregationsAvg']) == true )
			$AdditionalData['AggregationsAvg'] = $data['AggregationsAvg'];	
		if ( isset($data['AggregationsMin']) == true )
			$AdditionalData['AggregationsMin'] = $data['AggregationsMin'];	
		if ( isset($data['AggregationsMax']) == true )
			$AdditionalData['AggregationsMax'] = $data['AggregationsMax'];	

		if ( isset($data['Resolution']) == false )
			$AdditionalData['Resolution'] = -1;
		else	 
			$AdditionalData['Resolution'] = $data['Resolution'];	

		if ( isset($data['DataOffset']) == false )
			$AdditionalData['DataOffset'] = 0;
		else	 
			$AdditionalData['DataOffset'] = $data['DataOffset'];	

		if ( isset($data['TimeOffset']) == false )
			$AdditionalData['TimeOffset'] = 0;
		else	 
			$AdditionalData['TimeOffset'] = $data['TimeOffset'];	

		if ( isset($data['DataFilter']) == true )
			$AdditionalData['DataFilter'] = $data['DataFilter'];

			

		foreach ($AdditionalData as $key => $value)
			{
            $this->SendDebug(__FUNCTION__, "Output-".$key ."[" . $value . "]", 0);
            }	
			


		return $AdditionalData;

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

		}
	
		
	//******************************************************************************
	// 	Aggregationsstufe fuer Zeitraeume festlegen
	//  Stufe -1    kein Additional JSON Data uebergeben
	//	Stufe 0		Stuendliche Aggregation
	// 	Stufe 1		Taegliche Aggregation
	// 	Stufe 2		Woechentliche Aggregation
	// 	Stufe 3		Monatliche Aggregation
	//  Stufe 4		Jaehrliche Aggregation
	//  Stufe 5		5-Minuetige Aggregation
	//  Stufe 6		1-Minuetige Aggregation
	// 	Stufe 99	keine Aggregation ( maximale Aufloesung mit mehreren Vesuchen )
	//******************************************************************************	
	protected function CheckZeitraumForAggregatedValues($from,$to,$varID,$AggregationsStufe)
		{
		$archiv = $this->GetArchivID();
		$aggType = AC_GetAggregationType($archiv,$varID);

		$stufe = 99;
		
		$days = ($to-$from)/(3600*24);	
		$hours = ($to-$from)/(3600);

		if ($aggType == 0) 		// Standard bei -1
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

		if ($aggType == 1) 		// Zaehler bei -1
			{
			$stufe = 0;	
			if ( $hours < 2 )
				$stufe = 5;

			if( $days > 2 )
				$stufe = 1;	
			if( $days > 30 )
				$stufe = 2;		
			}
		
		// $AggregationsStufe hat Vorrang vor Standard ( -1 )
		if ($AggregationsStufe >= 0 and $AggregationsStufe <= 6 )
			$stufe = $AggregationsStufe;			
		if ($AggregationsStufe == 99 )
			$stufe = $AggregationsStufe;			
			
		$s = "Anzahl Tage:".$days . " Aggreagationsstufe:".$stufe ." Aggregationstype:".$aggType;

		$this->SendDebug(__FUNCTION__."[".__LINE__."]",$s,0);

		return $stufe;

		}	

	//******************************************************************************
	//	alle geloggten Variablen an Grafana senden ( Request Metrics )
	//******************************************************************************
	protected function ReturnMetrics($data_target)
		{
			

		// $this->SendDebug(__FUNCTION__."[".__LINE__."]",$s,0);

		$archiv = $this->GetArchivID();
		$varList = IPS_GetVariableList ();

		sort($varList);

		$string = '[';

		foreach ($varList as $var )
			{
				$status = AC_GetLoggingStatus($archiv,$var);
				if ( $status == true )
					{
					
					$name = IPS_GetName($var);
					$name = str_replace("'",'"',$name);
					$name = addslashes($name);
					$parent = IPS_GetParent($var);
					$parent = IPS_GetName($parent);
					$parent = str_replace("'",'"',$parent);
					$parent = addslashes($parent);	
					$metrics = $var.",".$name."[".$parent."]";

					// Filterung der Eingabe
					if ( $data_target != "" )	// Kein Filter
						{
						$found = stripos($metrics,$data_target);
						if ( $found === false )
							{
							$s = "false";	
							//if ( $data_target =! false )
							continue;
							// $this->SendDebug(__FUNCTION__."[".__LINE__."]",$s,0);
							}	
						else
							{
							$s = "true";	
							// $this->SendDebug(__FUNCTION__."[".__LINE__."]",$s,0);
							}	
						}


					$string = $string .'"'.$metrics.'",';	

					}

			}
		
		$string = substr($string, 0, -1);
		$string = $string .']';
		
		$this->SendDebug(__FUNCTION__."[".__LINE__."]",$string,0);

		return $string;	

		}		

	//******************************************************************************
	//	Rueckgabewerte fuer eine Variable erstellen
	//******************************************************************************
	protected function CreateReturnString($data,$target,$typ,$agstufe,$data_data,$DataOffset,$TimeOffset,$additional_data)
		{
		
		$offset = 0;
	
		$offset = floatval($DataOffset);
		$s = "Offsetwert neu:".$offset;
		$this->SendDebug(__FUNCTION__,$s,0);
	
		
		if ( isset($data_data['additional']) == true )
			if ( $data_data['additional'] == 'yoffset' )
				if ( isset($data_data['value']) == true )
					{
					$offset = floatval($data_data['value']);
					$s = "Offsetwert alt:".$offset;
					$this->SendDebug(__FUNCTION__,$s,0);

					}	
	

		
		$target = addslashes($target);

		$string = '{"target":"'.$target.'","datapoints":[';
			
		foreach($data as $value)	
			{
			 
							
			// Kein Offset zZ bei nicht Booleans
			// if ($agstufe == 99) 

			if (isset($value['Value'])) 
				{
                if(isset($additional_data['DataFilter']))
                    {
                   $filter = $additional_data['DataFilter'];    
 
                   $v = str_replace(",", ".", $value['Value']);
                   if ( $filter > $v)   // sinvoll ?
                        continue;
                    }
                else 
                    {
                    $v = str_replace(",",".",$value['Value']);
                    }
                }		

           else
				{
				// Aggregation
				// IPS_LogMessage(__FUNCTION__,"");

				$min = false;
				$max = false;
				$avg = false;

				$avg = @$value['Avg'];	
				$min = @$value['Min'];	
				$max = @$value['Max'];	
				$avg = str_replace(",",".",$avg);
				$min = str_replace(",",".",$min);
				$max = str_replace(",",".",$max);
				
				$v = $avg;
				
				
				if ( isset($additional_data['AggregationsMin']) == true )
				    if ( $min != false )
						$v = $min;
				if ( isset($additional_data['AggregationsMax']) == true )
					if ( $max != false )
						$v = $max;
						
				}	

			// Boolean	
			if ( $typ == 0 )		
				{
				if ($v == true) 
					{
					$v = 1;
					$vorher = $v;
					$v = $v + $offset;
					
					$s = "V + True Offset vorher nachher :".$vorher. "-" . $v;
					// $this->SendDebug(__FUNCTION__,$s,0);
					$v = str_replace(",", ".", $v);
					}
				else
					{
					$v = 0;
					$vorher = $v;

					$v = $v + $offset;
					$s = "V + False Offset vorher nachher :".$vorher. "-" . $v;
					// $this->SendDebug(__FUNCTION__,$s,0);
					$v = str_replace(",", ".", $v);
					}	
				}

			
			$Timestamp = $value['TimeStamp'] + intval($TimeOffset);	
			$t = $this->TimestampToGrafanaTime($Timestamp);	
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

		$status = AC_GetLoggingStatus ($archiv, $id);
		if ( $status == FALSE )
			{
			$s = " Variable wird nicht geloggt : ".$id;	
			$this->SendDebug(__FUNCTION__,$s,0);
			return FALSE;

			}	


		// 0 = Standard
		// 1 = Zaehler
		$aggType = AC_GetAggregationType($archiv,$id);


		if ($agstufe == 99) 
			{
			$s = "GetloggedValues".$archiv."-".$id."-".$from."-".$to;	
			$this->SendDebug(__FUNCTION__,$s,0);
			$werte = AC_GetLoggedValues($archiv, $id, $from, $to, 0);
			// print_r($werte);
			}
		else
			{
			$s = "GetAggregatedValues:".$agstufe."-".$archiv."-".$id."-".$from."-".$to;	
			$this->SendDebug(__FUNCTION__,$s,0);
			$werte = @AC_GetAggregatedValues ($archiv,$id,$agstufe,$from,$to,0);	
			}	

		if ( is_array($werte) == false )
			return false;

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
			// Problem da aktueller Wert genommen wird ( Gestern )	
			// $letzter_Wert = @AC_GetLoggedValues($archiv, $id, 0, 0, 1)[0]['Value'];
			// $to Zeit nehmen fuer letzten Wert
			$letzter_Wert = @AC_GetLoggedValues($archiv, $id, 0, $to, 1)[0]['Value'];
			
			
			$array = AC_GetLoggedValues($archiv, $id, 0, 0, 1);
			
            $erster_Wert  = @AC_GetLoggedValues($archiv, $id, 0, $from-1, 1);	// erster Wert vorhanden ?
			
			// Wenn es im Zeitbereich keinen ersten Wert gibt dann auf FALSE
			if ( $erster_Wert != false )
				{
				$erster_Wert = $erster_Wert[0]['Value'];
				$erster_WertOK = true;
				}
			else
				{
				$erster_Wert = false; 
				$erster_WertOK = false;
				}		
				
			}
		
			
		if ( $letzter_Wert == false ) // noch keine Daten geloggt/aktuellen Wert nehmen
			{
			$letzter_Wert = GetValue($id);
			$s = "Noch keine Daten geloggt aktueller Wert :".$letzter_Wert. " ID:".$id;
			$this->SendDebug(__FUNCTION__,$s,0);
			}	

		$s = "Erster Wert:[".$erster_Wert."] - Letzter Wert:[".$letzter_Wert."]";
		$this->SendDebug(__FUNCTION__,$s,0);


		if ($aggType == 0)	// Bei Zaehler keine erster/letzter Wert wegen komischer Anzeige (kleine Balken)
			{
            // Damit Graph bis zum Ende geht
			if ($agstufe == 99)
				{
                array_push($reversed, array("TimeStamp"=>$to,"Value"=>$letzter_Wert));
				} 
			else 
				{
                array_push($reversed, array("TimeStamp"=>$to,"Avg"=>$letzter_Wert));
            	}
        
            // Damit Graph bis zum Anfang geht ( $erster_Wert kann aber false sein !)
			if ($erster_WertOK != false) 
				{
				if ($agstufe == 99) 
					{
                    array_unshift($reversed, array("TimeStamp"=>$from,"Value"=>$erster_Wert));
					} 
				else
					{
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
		
		// rausgenommen,wenn nicht geloggt wird letzter Wert genommen (Gauge)
		/*
		if ( $status == true )
			$status = AC_GetLoggingStatus($archiv,$var);
		
		if ( $status == false )
			$this->Logmessage("Grafana Variable ID ".$var." Fehler ! Wird nicht geloggt",KL_WARNING);
		*/ 
			
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