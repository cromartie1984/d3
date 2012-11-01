<?php
/**
* Get the users item from Battle.Net and present it to the user; store it locally in a database behind the scenes.
* The item will only be updated after a few ours of retrieving it.
*
*/
namespace d3cb\Api;

use \d3cb\Tool;

/**
* var $p_itemHash string User BattleNet ID.
* var $p_dqi object Data Query Interface.
* var $p_sql object SQL.
* var $p_userIp string User IP address.
*/
class Item
{
	protected 
		$dqi,
		$info,
		$item,
		$itemHash,
		$json,
		$loadedFromBattleNet,
		$sql,
		$userIp;
	
	
	/**
	* Constructor
	*/
	public function __construct( $p_itemHash, \d3cb\BattleNetDqi $p_dqi, \d3cb\Sql $p_sql, $p_userIp )
	{
		$this->itemHash = $p_itemHash;
		$this->dqi = $p_dqi;
		$this->sql = $p_sql;
		$this->userIp = $p_userIp;
		$this->item = NULL;
		$this->info = NULL;
		$this->json = NULL;
		$this->loadedFromBattleNet = FALSE;
		$this->load();
	}
	
	/**
	* Destructor
	*/
	public function __destruct()
	{
		unset(
			$this->dqi,
			$this->info,
			$this->item,
			$this->itemHash,
			$this->json,
			$this->loadedFromBattleNet,
			$this->sql,
			$this->userIp
		);
	}
	
	/**
	* Get the item, first check the local DB, otherwise pull from Battle.net.
	*
	* @return string JSON item data.
	*/
	protected function getJson()
	{
		// Get the item from local database.
		$this->info = $this->sql->getItem( $this->itemHash );
		if ( Tool::isArray($this->info) )
		{
			$this->json = $this->info['item_json'];
		}
		// If that fails, then try to get it from Battle.net.
		if ( !Tool::isString($this->json) )
		{
			// Request the item from BattleNet.
			$json = $this->dqi->getItem( $this->itemHash );
			$responseCode = $this->dqi->responseCode();
			$url = $this->dqi->getUrl();
			// Log the request.
			$this->sql->addRequest( $this->dqi->getBattleNetId(), $url, $this->userIp );
			if ( $responseCode == 200 )
			{
				$this->json = $json;
				$this->loadedFromBattleNet = TRUE;
			}
		}
		
		return $this->json;
	}
	
	/**
	* Get raw JSON data returned from Battle.net.
	*/
	public function getRawData()
	{
		if ( $this->json !== NULL )
		{
			return $this->json;
		}
		return NULL;
	}
	
	/**
	* Load the users item into this class
	*/
	protected function load()
	{
		// Get the item.
		$this->getJson();
		// Convert the JSON to an associative array.
		if ( Tool::isString($this->json) )
		{
			$item = Tool::parseJson( $this->json );
			if ( Tool::isArray($item) )
			{
				$this->item = $item;
				if ($this->loadedFromBattleNet)
				{
					$this->save();
				}
			}
		}
		
		return $this->item;
	}
	
	/**
	* Save the users item locally, in this case a database
	*/
	protected function save()
	{
		return $this->sql->saveItem( $this->itemHash, $this->item, $this->json, $this->userIp );
	}
}
?>