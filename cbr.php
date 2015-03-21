<?php
  /**
   * Cbr
   *
   * API connector to SOAP service of The Central Bank of the Russian Federation 
   * 
   * @package    Cbr
   * @author     Sergey Koshkarev <i@sergeykoshkarev.com>
   * @version    1.0
   */
class Cbr {
	private $base_url = 'http://www.cbr.ru/DailyInfoWebServ/DailyInfo.asmx?WSDL';
	private $soap_client = null;
	private $soap_args = array();
	private $output = null;

	public function __construct()
	{
		$this -> setup_client();
	}

	public function __call($name = '', Array $arguments)
    {
    	if (method_exists($this, $name))
    	{
    		// Set method arguments
    		if (is_array($arguments))
			{
				if (count($arguments) > 0)
				{
					foreach ($arguments as $arg_name => $arg_value)
					{
						$this -> soap_args[] = new SoapParam($arg_value, $arg_name);
					}
				}
			}

			return $this -> {$name}();
    	}
    }

	private function setup_client()
	{
		try { 
			$this -> soap_client = @new SoapClient($this -> base_url);
		} catch (Exception $e) {  
	    	error_log(__LINE__ . ' : ' . $e -> getMessage());
	    	return false; 
		} 
	} 

	/**
	* Справочник по кодам валют, содержит полный перечень валют котируемых Банком России
	* 
	* @access    private
	* @param     boolean $Seld (False - перечень ежедневных валют, True - перечень ежемесячных валют)
	* @return    null|array
	*/
	private function currencies()
	{
		try {
			$result = $this -> soap_client -> __call("EnumValutesXML",  $this -> soap_args); 
			if (isset($result -> EnumValutesXMLResult, $result -> EnumValutesXMLResult -> any))
			{
				$xml = (array)simplexml_load_string($result -> EnumValutesXMLResult -> any);
				if (is_array($xml['EnumValutes']))
				{
					$xml['EnumValutes'] = json_decode(json_encode($xml['EnumValutes'], true));
					foreach ($xml['EnumValutes'] as $element)
					{
						$this -> output[] = array(
							'vcode'			=>	$element -> Vcode,
							'vname'			=>	$element -> Vname,
							'vengname'		=>	$element -> VEngname,
							'vnom'			=>	$element -> Vnom,
							'vcommoncode'	=>	$element -> VcommonCode,
							'vnumcode'		=>	$element -> VnumCode,
							'vcharcode'		=>	$element -> VcharCode,
						);	
					}
					
				}
			}
			return $this -> output;
		} catch(Exception $e) {
			error_log(__LINE__ . ' : ' . $e -> getMessage());
	    	return false; 
      	}
	} 

	/**
	* Получение курсов валют на определенную дату (ежедневные курсы валют)
	* 
	* @access   private
	* @return   array
	*/
	private function rate()
	{
		try {
			$result = $this -> soap_client -> __call("GetCursOnDate",  $this -> soap_args); 
			if (isset($result -> GetCursOnDateResult, $result -> GetCursOnDateResult -> any, $result -> GetCursOnDateResult -> any))
			{
				$xml = json_decode(json_encode(simplexml_load_string($result -> GetCursOnDateResult -> any)), true);
				if (is_array($xml['ValuteData']['ValuteCursOnDate']))
				{
					$xml['ValuteData']['ValuteCursOnDate'] = json_decode(json_encode($xml['ValuteData']['ValuteCursOnDate'], true));
					foreach ($xml['ValuteData']['ValuteCursOnDate'] as $element)
					{
						$this -> output[] = array(
							'vcode'			=>	$element -> Vcode,
							'vname'			=>	$element -> Vname,
							'vchcde'		=>	$element -> VchCode,
							'vnom'			=>	$element -> Vnom,
							'vcurs'			=>	$element -> Vcurs,
						);	
					}
					
				}
			}
			return $this -> output;
		} catch(Exception $e) {
			error_log(__LINE__ . ' : ' . $e -> getMessage());
	    	return false; 
      	}
	}

	/**
	* Получение последней даты публикации курсов валют
	* 
	* @access   private
	* @return   string|null
	*/
	private function lastest_update()
	{
		try {
			$result = $this -> soap_client -> __call("GetLatestDateTime",  $this -> soap_args); 
			if (isset($result -> GetLatestDateTimeResult))
			{
				$this -> output = $result -> GetLatestDateTimeResult;
			}
			return $this -> output;
		} catch(Exception $e) {
			error_log(__LINE__ . ' : ' . $e -> getMessage());
	    	return false; 
      	}
	}

	/**
	* Получение динамики ежедневных курсов валюты
	* 
	* @access   private
	* @return   array|null
	*/
	private function dynamic_rate()
	{
		try {
			$result = $this -> soap_client -> __call("GetCursDynamic",  $this -> soap_args); 
			if (isset($result -> GetCursDynamicResult, $result -> GetCursDynamicResult -> any))
			{
				$xml = json_decode(json_encode(simplexml_load_string($result -> GetCursDynamicResult -> any)), true);
				if (is_array($xml['ValuteData']['ValuteCursDynamic']))
				{
					$this -> output = $xml['ValuteData']['ValuteCursDynamic'];
				}
				return $this -> output;
			}
			return $this -> output;
		} catch(Exception $e) {
			error_log(__LINE__ . ' : ' . $e -> getMessage());
	    	return false; 
      	}
	}

	/**
	* Структура бивалютной корзины
	*
	* @access   private
	* @return   array|null
	*/
	private function bicurrency_basket()
	{
		try {
			$result = $this -> soap_client -> __call("BiCurBase",  $this -> soap_args); 

			if (isset($result -> BiCurBaseResult, $result -> BiCurBaseResult -> any))
			{
				$xml = json_decode(json_encode(simplexml_load_string($result -> BiCurBaseResult -> any)), true);
				if (is_array($xml['BiCurBase']['BCB']))
				{
					$this -> output = $xml['BiCurBase']['BCB'];
				}
				return $this -> output;
			}
			return $this -> output;
		} catch(Exception $e) {
			error_log(__LINE__ . ' : ' . $e -> getMessage());
	    	return false; 
      	}
	}
}