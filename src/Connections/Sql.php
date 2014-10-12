<?php namespace Kshabazz\BattleNet\D3\Connections;

use function \Kshabazz\Slib\logError, \Kshabazz\Slib\isArray;

class Sql extends \Kshabazz\Slib\Sql implements Connection
{
	const
		SELECT_PROFILE = 'SELECT `battle_net_id`, `json`, `ip_address`, `last_updated`, `date_added` FROM `d3_profiles` WHERE `battle_net_id` = :battleNetId;',
		INSERT_PROFILE = 'INSERT INTO `d3_profiles` (`battle_net_id`, `json`, `ip_address`, `last_updated`, `date_added`) VALUES(:battleNetId, :json, :ipAddress, :lastUpdated, :dateAdded) ON DUPLICATE KEY UPDATE `json` = VALUES(json), `ip_address` = VALUES(ip_address), `last_updated` = VALUES(last_updated);',
		INSERT_REQUEST = 'INSERT INTO `battlenet_api_request` (`battle_net_id`, `ip_address`, `url`, `date_number`, `date_added`) VALUES(:battleNetId, :ipAddress, :url, :dateNumber, :dateAdded);',
		SELECT_REQUEST = 'SELECT `ip_address`, `url`, `date`, `date_added` FROM `battlenet_api_request` WHERE  `date` = :date;',
		SELECT_ITEM = 'SELECT `hash`, `id`, `name`, `item_type`, `json`, `ip_address`, `last_updated`, `date_added` FROM `d3_items` WHERE `%s` = :selectValue;',
		INSERT_ITEM = 'INSERT INTO `d3_items` (`hash`, `id`, `name`, `item_type`, `json`, `ip_address`, `last_updated`, `date_added`) VALUES(:hash, :id, :name, :itemType, :json, :ipAddress, :lastUpdate, :dateAdded);',
		SELECT_HERO = 'SELECT `id`, `battle_net_id`, `json`, `ip_address`, `last_updated`, `date_added` FROM `d3_heroes` WHERE `id` = :id;',
		INSERT_HERO = 'INSERT INTO `d3_heroes` (`id`, `battle_net_id`, `json`, `ip_address`, `last_updated`, `date_added`) VALUES(:heroId, :battleNetId, :json, :ipAddress, :lastUpdated, :dateAdded) ON DUPLICATE KEY UPDATE `json` = VALUES(json), `ip_address` = VALUES(ip_address), `last_updated` = VALUES(last_updated);';

	private
		$battleNetId,
		$battleNetUrlSafeId;

	/**
	 * Constructor
	 *
	 * @param string $pBattleNetId
	 * @param \PDO $pPdo
	 * @param string $pIpAddress
	 */
	public function __construct( $pBattleNetId, \PDO $pPdo, $pIpAddress = NULL )
	{
		parent::__construct( $pPdo, $pIpAddress );
		$this->battleNetId = $pBattleNetId;
		$this->battleNetUrlSafeId = str_replace( '#', '-', $this->battleNetId );
	}

	/**
     * Add a record of the Battle.net Web API request.
     *
	 * @param $pUrl string The Battle.net url web API URL requested.
     * @return bool|mixed
     */
    public function addRequest($pUrl )
	{
		$returnValue = FALSE;
		try
		{
			if ( $this->pdoh !== NULL )
			{
				$today = date( 'Y-m-d' );
				$stmt = $this->pdoh->prepare( self::INSERT_REQUEST );
				$stmt->bindValue( ':battleNetId', $this->battleNetId, \PDO::PARAM_STR );
				$stmt->bindValue( ':ipAddress', $this->ipAddress, \PDO::PARAM_STR );
				$stmt->bindValue( ':url', $pUrl, \PDO::PARAM_STR );
				$stmt->bindValue( ':dateNumber', strtotime($today), \PDO::PARAM_STR );
				$stmt->bindValue( ':dateAdded', date('Y-m-d H:i:s'), \PDO::PARAM_STR );
				$returnValue = $this->pdoQuery( $stmt, FALSE );
			}
		}
		catch ( \Exception $p_error )
		{
			// TODO: Throw an error;
		}
		return $returnValue;
	}

    /**
     * Get hero data from local database.
     *
     * @param int $pHeroId
     * @return string|null
     */
    public function getHero( $pHeroId )
	{
		if ( $pHeroId === NULL )
		{
			throw new \InvalidArgumentException( 'Hero ID should be an integer.' );
		}
		$result = $this->pdoQueryBind( self::SELECT_HERO, [ 'id' => [$pHeroId, \PDO::PARAM_STR] ]);
		if ( isArray($result) )
		{
			return $result[ 0 ][ 'json' ];
		}
		return NULL;
	}

	/**
	 * Get item JSON data from local database.
	 *
	 * @param string $pItemHash
	 * @return string|null
	 */
	public function getItem( $pItemHash )
	{
		$hashValue = str_replace( 'item/', '', $pItemHash );
		$query = sprintf( self::SELECT_ITEM, 'hash' );
		$result = $this->pdoQueryBind( $query, ['selectValue' => [$hashValue, \PDO::PARAM_STR]] );
		if ( isArray($result) )
		{
			return $result[ 0 ][ 'json' ];
		}
		return NULL;
	}

    /**
     * Get the profile from local database.
     *
     * @return string|null
     */
    public function getProfile()
	{
		$returnValue = NULL;
		try
		{
			$result = $this->pdoQueryBind(
				self::SELECT_PROFILE,
				[ ':battleNetId' => [$this->battleNetId, \PDO::PARAM_STR] ]
			);
			if ( isArray($result) )
			{
				$returnValue = $result[ 0 ][ 'json' ];
			}
		}
		catch ( \Exception $p_error )
		{
			// TODO: Map ERROR_NOTICE_1 to message "Unable to retrieve your profile from cache."
			logError( $p_error, $p_error->getMessage() );
		}

		return $returnValue;
	}

	/**
	 * Save the hero in a local database.
	 *
	 * @return bool Indicates success (TRUE) or failure (FALSE).
	 */
	public function saveHero( $pHeroId, $pJson )
	{
		$utcTime = gmdate( 'Y-m-d H:i:s' );
		return $this->sql->pdoQueryBind( self::INSERT_HERO, [
				'battleNetId' => [ $this->battleNetId, \PDO::PARAM_STR ],
				'dateAdded' => [ $utcTime, \PDO::PARAM_STR ],
				'heroId' => [ $pHeroId, \PDO::PARAM_STR ],
				'ipAddress' => [ $this->ipAddress, \PDO::PARAM_STR ],
				'json' => [ $pJson, \PDO::PARAM_STR ],
				'lastUpdated' => [ $utcTime, \PDO::PARAM_STR ]
			]);
	}

	/**
	 * Save the item locally in a database.
	 *
	 * @param \Kshabazz\BattleNet\D3\Models\Item $pItem
	 * @return bool
	 */
	public function saveItem( \Kshabazz\BattleNet\D3\Models\Item $pItem )
	{
		$itemName = $pItem->name();
		$itemType = $pItem->type();
		$id = $pItem->id();
		$tooltipParams = $pItem->tooltipParams();
		$json = $pItem->json();
		$utcTime = gmdate( 'Y-m-d H:i:s' );
		$params = [
			'hash' => [ $tooltipParams, \PDO::PARAM_STR ],
			'id' => [ $id, \PDO::PARAM_STR ],
			'name' => [ $itemName, \PDO::PARAM_STR ],
			'itemType' => [ $itemType['id'], \PDO::PARAM_STR ],
			'json' => [ $json, \PDO::PARAM_STR ],
			'ipAddress' => [ $this->ipAddress, \PDO::PARAM_STR ],
			'lastUpdate' => [ $utcTime, \PDO::PARAM_STR ],
			'dateAdded' => [ $utcTime, \PDO::PARAM_STR ]
		];
		return $this->pdoQueryBind( self::INSERT_ITEM, $params );
	}

	/**
	 * Save the users profile locally to the database.
	 *
	 * @return bool
	 */
	public function saveProfile()
	{
		// save it to the database.
		$utcTime = \gmdate( 'Y-m-d H:i:s' );
		$query = self::INSERT_PROFILE;
		return $this->sql->pdoQueryBind( $query, [
			'battleNetId' => [ $this->key, \PDO::PARAM_STR ],
			'json' => [ $this->json, \PDO::PARAM_STR ],
			'ipAddress' => [ $this->sql->ipAddress(), \PDO::PARAM_STR ],
			'lastUpdated' => [ $utcTime, \PDO::PARAM_STR ],
			'dateAdded' => [ $utcTime, \PDO::PARAM_STR ]
		]);
	}
}
?>