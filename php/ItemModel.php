<?php
namespace d3;
/**
* Get the users item from Battle.Net and present it to the user; store it locally in a database
* behind the scenes. The item will only be updated after a few ours of retrieving it.
*
*/

/**
* var $p_itemHash string User BattleNet ID.
* var $p_dqi object Data Query Interface.
* var $p_sql object SQL.
* var $p_userIp string User IP address.
*/
class ItemModel extends BattleNetModel 
{
	/**
	* Constructor
	*/
	public function __construct( $p_json )
	{
		parent::__construct( $p_json );
		// Top-level properties required of the JSON returned from battle.net.
		$this->attributeMap = [
			"armor" => "array",
			"attributes" => "array",
			"attributesRaw" => "array",
			"bonusAffixes" => "int",
			"displayColor" => "string",
			"flavorText" => "string",
			"dps" => "array",
			"gems" => "array",
			"icon" => "string",
			"id" => "string",
			"itemLevel" => "int",
			"name" => "string",
			"requiredLevel" => "int",
			"salvage" => "array",
			"set" => "array",
			"socketEffects" => "array",
			"tooltipParams" => "string",
			"type" => "array",
			"typeName" => "string"
		];
		$this->__init();
	}
	
	/**
	* Destructor
	*/
	public function __destruct()
	{
		parent::__destruct();
	}
	
	/**
	* Get property
	*/
	public function __get( $p_name )
	{
		if ( isset($this->$p_name) )
		{
			return $this->$p_name;
		}
		else if ( array_key_exists($p_name, $this->_array) )
		{
			return $this->$p_name = $this->_array[ $p_name ];
		}
		
		$trace = debug_backtrace();
		trigger_error(
			'Undefined property: ' . $p_name .
			' in ' . $trace[0]['file'] .
			' on line ' . $trace[0]['line'],
			E_USER_NOTICE
		);
		
		return NULL;
	}
	
	/**
	* Convert this object to a string.
	* @return string
	*/
	public function __toString()
	{
		return json_encode( $this, JSON_PRETTY_PRINT );
	}
	
	/**
	* Determine if a variable is set.
	* @return bool
	*/
	public function __isset( $p_property )
	{
		return isset( $this->$p_property );
	}
	
	/**
	* @param $p_effect Name of an effect (a.k.a attribute).
	* @return string
	*/
	public function getEffect( $p_effect, $p_min, $p_max )
	{
		$returnValue = '';
		if ( array_key_exists($p_effect, $this->effects) )
		{
			$mapValue = $this->effects[ $p_effect ];
			// Convert some decimals to percents.
			if ( $p_min < 1 ) {
				$p_min = $p_min * 100;
				$p_max = $p_max * 100;
			}
			$max = ( $p_min === $p_max ) ? '' : '-' . $p_max;
			$returnValue = sprintf( $mapValue, $p_min, $max );
		}
		return $returnValue;
	}
	
	/**
	* Specify how this object is to be used with json_encode.
	* @return array
	*/
	public function jsonSerialize()
	{
		$returnValue = [];
		foreach ( $this as $property => $value )
		{
			if ( array_key_exists($property, $this->attributeMap) )
			{
				$returnValue[ $property ] = $value;
			}
		}
		return $returnValue;
	}
}
?>