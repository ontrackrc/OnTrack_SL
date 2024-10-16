<?php
global $debug;
global $ch;
global $test;
global $sl_user;

//Syteline config info
define('SL_PROTOCOL','HTTP'); #HTTPS for ssl
define('SL_SERVER', '<hostname>'); #hostname for idorequestservice
define('SL_USER', '<username>'); #SL username
define('SL_PWD', '<Password>'); #SL password

$debug = false; //set to true to enable debuging prints

if ($debug){
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
}


#Main functions
#region#########################################################################

//main run function
function run($req = null){
	$break = false; //used to flag when fatal errors have occured

	//the return array $x is used for all messaging back to the caller
	$x = array();
	$data = array();

	//check for data being passed
	if($req == null){
		array_push($x,array('Class' => 'Fatal Error','Message' =>'No request was passed'));
		$break = true;
	}
	
	//Try to set all the properties
	$type = $req['type'];
	$db = $req['db'];
	$ido = $req['ido'];
	$fields = $req['fields'];
	$values = $req['values'];
	$filter = $req['filter_str'];
	$order = $req['order_str'];
	$rcap = $req['record_cap'];
	$retcolhdr = $req['ret_col_hdr_first_row'];
	$method = $req['method'];
	$pmethod = $req['method_params'];
	$rowid = $req['row_id'];
	$distinct = $req['distinct'];
	
	//Warn about unset properties
	if(!(isset($typ))){array_push($x,array('Class' => 'Warning','Message' =>'Type property not set'));}
	if(!(isset($db))){array_push($x,array('Class' => 'Warning','Message' =>'DB property not set'));}
	if(!(isset($ido))){array_push($x,array('Class' => 'Warning','Message' =>'IDO property not set'));}
	if(!(isset($fields))){array_push($x,array('Class' => 'Warning','Message' =>'Fields property not set'));}
	if(!(isset($values))){array_push($x,array('Class' => 'Warning','Message' =>'Values property not set'));}
	if(!(isset($filter))){array_push($x,array('Class' => 'Warning','Message' =>'Filter String property not set'));}
	if(!(isset($order))){array_push($x,array('Class' => 'Warning','Message' =>'Order By property not set'));}	
	if(!(isset($rcap))){array_push($x,array('Class' => 'Warning','Message' =>'Record Cap property not set'));}
	if(!(isset($retcolhdr))){array_push($x,array('Class' => 'Warning','Message' =>'Return Column Headers As First Row property not set'));}
	if(!(isset($method))){array_push($x,array('Class' => 'Warning','Message' =>'Method property not set'));}
	if(!(isset($pmethod))){array_push($x,array('Class' => 'Warning','Message' =>'Method Parameters property not set'));}
	if(!(isset($rowid))){array_push($x,array('Class' => 'Warning','Message' =>'RowID property not set'));}
	if(!(isset($distinct))){array_push($x,array('Class' => 'Warning','Message' =>'Distinct property not set'));}

	//fatal error interlocks
	if((($type == 'insert')||($type == 'update'))&&(count($fields) != count($values))){
		array_push($x,array('Class' => 'Fatal Error', 'Message' => 'Number of values must match number of fields on an update or insert command'));
		$break = true;
	}

	if((($type == 'update')||($type == 'delete'))&&(($filter == null)||($filter == ""))){
		if(!(isset($rowid))){
			array_push($x,array('Class' => 'Fatal Error','Message' => 'Filter string is null, '.$type.' may not be ran as it would '.$type.' everything'));
			$break = true;
		}
	}
	
	if(($type == 'invoke')&&(!(isset($method)))){
		array_push($x,array('Class' => 'Fatal Error', 'Message' => 'Method must be passed for invoke'));
		$break = true;
	}
	if(($type == 'invoke')&&(count($pmethod) <= 0)){
		array_push($x,array('Class' => 'Fatal Error', 'Message' => 'Method parameters must be passed for invoke'));
		$break = true;
	}
	
	//Warning error interlocks 
	if(!(isset($rcap))){
		$rcap = 1000;
		array_push($x,array('Class' => 'Message','Message' =>'Record Cap default of 1000 used'));
	}
	
	if(!(isset($order))){
		$order ='';
		array_push($x,array('Class' => 'Message','Message' =>'Order string default of empty used'));
	}
	
	if(!(isset($filter))){
		$filter ='';
		array_push($x,array('Class' => 'Message','Message' =>'Filter string default of empty used'));
	}


	//check for fatal errors
	if($break == false){
        //request type switch
        switch ($type){
            case 'insert':
                $ret = Insert($db,$ido,$fields,$values,$filter,$rowid);
                $data = array('data' => $ret['data']);
                if(isset($ret['error'])){
                    array_push($x, array('Class' => 'SL Error','Message' => $ret['error']));
                }
                break;
            case 'update':
                $ret = Update($db,$ido,$fields,$values,$filter,$rowid);
                $data = array('data' => $ret['data']);
                if(isset($ret['error'])){
                    array_push($x, array('Class' => 'SL Error','Message' => $ret['error']));
                }
                break;
            case 'invoke':
                $ret = Invoke($db,$ido,$method,$pmethod);
                if(isset($ret['error'])){
                    array_push($x, array('Class' => 'SL Error','Message' => $ret['error']));
                }
                break;
            case 'delete':
                $ret = Del($db,$ido,$fields,$values,$filter,$rowid);
                $data = array('data' => $ret['data']);
                if(isset($ret['error'])){
                    array_push($x, array('Class' => 'SL Error','Message' => $ret['error']));
                }
                break;
            case 'info':
                $ret = Info($db,$ido);
                $data = array('data' => $ret['data']);
                if(isset($ret['error'])){
                    array_push($x, array('Class' => 'SL Error','Message' => $ret['error']));
                }
                break;
            case 'get':                       
                $ret = Select($db,$ido,$fields,$filter,$order,$rcap,$distinct,$method,$pmethod,$retcolhdr);
                $data = array('data' => $ret['data']);
                if(isset($ret['error'])){
                    array_push($x, array('Class' => 'SL Error','Message' => $ret['error']));
                }
                break;
            default:
                array_push($x,array('Class' => 'Fatal Error','Message' => 'Operation type not valid (insert, update, invoke, get, delete)'));
        }
	}

    
	if (empty($x[0])){
		array_push($x,array('Class' => 'Message','Message' => 'Return Successful'));
	}

	$x = array_filter($x);

	//compile the final array
	$final = $data + array('errors_and_messages' => $x);
	#$enc = json_encode($final);
	return $final;
    
}

#endregion

#Functions to call the IDO request Service
#region#########################################################################

//Send the IDO request xml and get response
function idorequest($xml){
		global $debug;
        global $ch;
		global $test;
		
		$urls = array(SL_PROTOCOL.'://'.SL_SERVER.'/IDORequestService/RequestService.aspx');
		shuffle($urls);		
		$slurl = $urls[0];
		
		if($debug){
				echo print_r($xml,true);
		}
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookieFileName');
        curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookieFileName');
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml',));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
        curl_setopt($ch, CURLOPT_URL, $slurl);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $response = curl_exec($ch);
      	if($debug){
			echo print_r($response,true);
		}
        $array_response = json_decode(json_encode(simplexml_load_string($response)) , true);
		return $array_response;
    }

//Build the xml requst in the correct format
function buildXML ($site, $bodyxml){	
		
	$header = '<RequestHeader Type="OpenSession">
				<RequestData>
					<UserID>'.SL_USER.'</UserID>
					<LanguageID>en-US</LanguageID>
					<ConfigName>'.$site.'</ConfigName>
					<AllowCloseExistingSessions>true</AllowCloseExistingSessions>
					<Password Encrypted="N">'.SL_PWD.'</Password>
				</RequestData>
			</RequestHeader>';	
			
				
	$xml = '<IDORequest>' . $header . $bodyxml . '</IDORequest>';
	return $xml;
}


#endregion

#Functions to create Invoke type operations
#region#########################################################################

//Info entry and return marshaling point
function Invoke($site, $ido, $method, $properties){
	$slxml = buildXML($site, InvokeXML($ido,$method,$properties));
	//echo print_r($slxml,true);
    $slret = idorequest($slxml);
    $err        = $slret['ResponseHeader'][1]['ErrorMessage'];
    $data   = processInvoke($slret['ResponseHeader'][1]['ResponseData']['ReturnValue']);
    $finalarray = array(
        'data' => $data,
        'error' => $err
    );
    return $finalarray;
}

//make IDO info cleaner
function processInvoke($arr){
	$arr = json_encode($arr);
	$arr = str_replace("@attributes","attributes",$arr);
	$arr = json_decode($arr);
	return $arr;
}

//Get IDO information and properties
function InvokeXML ($idoname, $method, $parms){
	
	$propstr = '';
    //create a string from an array where each element becomes an XML element
	foreach ($parms as $x) {
        $propstr .=  '<Parameter ByRef="Y">'.$x.'</Parameter>';
    }
	
	$bodyxml ='
		<RequestHeader Type="Invoke">
		<RequestData>
		<Name>'.$idoname.'</Name>
		<Method>'.$method.'</Method>
		<Parameters>
		'.$propstr.'
		</Parameters>
		</RequestData>
	</RequestHeader>
	';
	return $bodyxml;
}

#endregion

#Functions to create Write type operations
#region#########################################################################

//Generate Update and Delete operations XML
function UpdateAndDeleteXML($db,$idoName,$type,$fields,$values,$filter = '',$id =null){
	
	//echo print_r($fields,true);
	
	
	$properties = array();
	
	foreach($fields as $key=>$x){
		array_push($properties,array('value'=>$values[$key],'mod'=>'Y','name'=>$x));
	}
	
	//echo print_r($properties,true);
	
	
	
	//echo print_r($sel,true);
	
	$ids = array();
	
	if(isset($id)){
		array_push($ids,$id);
	}else{
		$sel = Select($db,$idoName,$fields,$filter,null,10,false);
		$sel = $sel['data'];
		foreach($sel as $x){
			array_push($ids,$x['ID']);
		}
	}
	//echo print_r($ids,true);
	
	$itemstr = "";
	
	foreach($ids as $index=>$x){
		$items = ItemXML($properties,$type,$x,$index);
		$itemstr = $itemstr . $items;
	}
	
	$xml = WriteXMLbyID($idoName, $itemstr);
	return $xml;
}

//Generate Insert operation XML
function InsertXML($idoName,$fields,$values){
	
	$properties = array();
	
	foreach($fields as $key=>$x){
		array_push($properties,array('value'=>$values[$key],'mod'=>'Y','name'=>$x));
	}
	$itemstr = ItemXML($properties,'insert',$index);
	
	$xml = WriteXMLbyKey($idoName, $itemstr);
	return $xml;
}

//Use the RowID to generate the write type XML
function WriteXMLbyID($ido,$items){
	
	$bodyxml = 
	'<RequestHeader Type="UpdateCollection">
	 <RequestData>
	 <UpdateCollection Name="'.$ido.'" RefreshAfterUpdate="Y">
	'.$items.'
	 </UpdateCollection>
	 </RequestData>
	 </RequestHeader>';
	 return $bodyxml;
}

//Use the Fields to generate the write type XML
function WriteXMLbyKey($ido,$items){
	
	
	$bodyxml = 
	'<RequestHeader Type="UpdateCollection">
	 <RequestData>
	 <UpdateCollection Name="'.$ido.'" RefreshAfterUpdate="Y" UseKeys="Y">
	'.$items.'
	 </UpdateCollection>
	 </RequestData>
	 </RequestHeader>';
	 return $bodyxml;
}

//Create Item XML for each item
function ItemXML ($properties, $type, $rowid = null , $itemnum = 0){
	
	$propstr = '';
    //create a string from an array where each element becomes an XML element
	
	foreach($properties as $x){
    $mod = '<Property Name="' . $x['name'] . '" Modified="'.$x['mod'].'">'.$x['value'].'</Property>';
	$propstr = $propstr . $mod;
	}
	
	if(isset($rowid)){
	
	 $items = '<Items>
		<Item Action="'.$type.'" ItemNo="'.$itemnum.'" ID="'.$rowid.'">
		'.$propstr.'
		</Item></Items>';
	}else{
		 $items = '<Items>
		<Item Action="'.$type.'" ItemNo="'.$itemnum.'">
		'.$propstr.'
		</Item></Items>';
	}
	
	return $items;
}

//Delete entry and return marshaling point
function Del($db,$idoName,$fields,$values,$filter = '',$rowid = null){
	$slxml = buildXML($db, UpdateAndDeleteXML($db,$idoName,'delete',$fields,$values,$filter,$rowid));
	
	//echo '<pre>'.$slxml.'</pre>';
    
	$slret = idorequest($slxml);
    $err        = $slret['ResponseHeader'][1]['ErrorMessage'];
    $data   = processInfo($slret['ResponseHeader'][1]['ResponseData']['GetPropertyInfo']['IDOProperties']);
    $finalarray = array(
        'data' => $data,
        'error' => $err
    );
    return $finalarray;
	
	
	
}

//Insert entry and return marshaling point
function Insert($db,$idoName,$fields,$values){
		$slxml = buildXML($db, InsertXML($idoName,$fields,$values));	
		$slret = idorequest($slxml);
		$err        = $slret['ResponseHeader'][1]['ErrorMessage'];
		$data   = processInfo($slret['ResponseHeader'][1]['ResponseData']['Items']);
		$finalarray = array(
        'data' => $data,
        'error' => $err
    );
    return $finalarray;
}

//Update entry and return marshaling point
function Update($db,$idoName,$fields,$values,$filter = '',$rowid = null){
	
	$slxml = buildXML($db, UpdateAndDeleteXML($db,$idoName,'update',$fields,$values,$filter,$rowid));
	$slret = idorequest($slxml);
    $err  = $slret['ResponseHeader'][1]['ErrorMessage'];
    $data = processInfo($slret['ResponseHeader'][1]['ResponseData']['GetPropertyInfo']['IDOProperties']);
    $finalarray = array(
        'data' => $data,
        'error' => $err
    );
    return $finalarray;
	
}


#endregion

#Functions to create Read type operations
#region#########################################################################

//Get IDO Data
function LoadCollection($idoName, $properties = array(), $filter = '', $order = '', $recordCap = 1000,$distinct = true,$method=false, $method_parms=array()){
	$propstr = '';
    //create a string from an array where each element becomes an XML element
	foreach ($properties as $x) {
        $propstr .=  '<'.$x.'></'.$x.'>';
    }
	
	//concat the strings together
    $bodyxml = '<RequestHeader Type="LoadCollection">
                        <RequestData>
                            <LoadCollection Name="' . $idoName . '">';
							
							if($method){
								  $parmstr = '';
								foreach ($method_parms as $p) {
									$parmstr .=  '<Parameter>'.$p.'</Parameter>';
								}
							
							$bodyxml .= '<CustomLoadMethod Name="'.$method.'">
										 <Parameters>'.$parmstr.'
										 </Parameters>
										 </CustomLoadMethod>';
							
							}
							
                                $bodyxml .= '<LoadType>FIRST</LoadType>
                                <PropertyList>
                                ' . $propstr . '
                                </PropertyList>';
								if($distinct){
									$bodyxml .= '<Distinct></Distinct>';
                                }
								$bodyxml .= '<RecordCap>' . $recordCap . '</RecordCap>
                                <Filter>' . $filter . '</Filter>
                                <OrderBy>' . $order . '</OrderBy>
                            </LoadCollection>
                        </RequestData>
                    </RequestHeader>';
    return $bodyxml;	
}

//Get just the field data into an array
function processLoadCollection($inrec, $properties, $includeHeaders = false){
	global $debug;
	if($debug){
		echo '====In records====';
		echo print_r($inrec,true);
		echo PHP_EOL;
		}
   //add the ID key for latter use
	array_push($properties,'ID');
    
	$records = array();
	
	//this includes headers as the first element
    if ($includeHeaders == true) {
        array_push($records, array_combine($properties, $properties));
    }
	
	if(isset($inrec['P'])){
		$inrec = array($inrec);
	}
	
	//format the array in a logical manner and add the keys correctly
    foreach ($inrec as $x) {
		
		if($debug){
			echo '====Current Row====';
			echo print_r($x,true);
			echo PHP_EOL;
		}
		
		$row = $x['P'];
		$rowid = $x['@attributes']['ID'];
		
		//this loop cleans up the empties so they play nice with json_encode
        foreach ($row as &$f) {
            if (isset($f)) {
                $f = $f;
            } else {
                $f = null;
            }
            if (empty($f)) {
                $f = null;
            } else {
                $f = $f;
            }
        }
        array_push($row,$rowid);
        array_push($records, array_combine($properties, $row));
    }
	
	if($debug){
			echo '====Final Array Row====';
			echo print_r($records,true);
			echo PHP_EOL;
		}
    return $records;
}


//Select entry and return marshaling point
function Select($site, $ido, $properties = array(), $filter = '', $order = '', $cap, $distinct,$method = '', $method_parms = array(),$includeHeaders = false){	
    $slxml = buildXML($site, LoadCollection($ido, $properties, $filter, $order, $cap,$distinct,$method,$method_parms));    
    $slret = idorequest($slxml);
    $err        = $slret['ResponseHeader'][1]['ErrorMessage'];
    $itemdata   = $slret['ResponseHeader'][1]['ResponseData']['LoadCollection']['Items']['Item'];
    $procarray  = processLoadCollection($itemdata, $properties, $includeHeaders);
    $finalarray = array(
        'data' => $procarray,
        'error' => $err
    );
    return $finalarray;
}

//Get IDO information and properties
function GetPropertyInfo ($idoName, $classNotes = 'N'){
	
	$bodyxml ='<RequestHeader Type="GetPropertyInfo"><RequestData>
	 <GetPropertyInfo Name="'.$idoName.'" IncludeClassNotesFlag="'.$classNotes.'" />
	</RequestData></RequestHeader>';
	return $bodyxml;
}

//make IDO info cleaner
function processInfo($arr){
	global $debug;
	
	if($debug){
		echo print_r($arr,true);
	}
	$arr = json_encode($arr);
	$arr = str_replace("@attributes","attributes",$arr);
	$arr = json_decode($arr);
	return $arr;
}

//Info entry and return marshaling point
function Info($site, $ido, $classNotes = 'N'){
	$slxml = buildXML($site, GetPropertyInfo($ido,$classNotes));
    $slret = idorequest($slxml);
    $err        = $slret['ResponseHeader'][1]['ErrorMessage'];
    $data   = processInfo($slret['ResponseHeader'][1]['ResponseData']['GetPropertyInfo']['IDOProperties']);
    $finalarray = array(
        'data' => $data,
        'error' => $err
    );
    return $finalarray;
}
?>