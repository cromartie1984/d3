<?php namespace Kshabazz\BattleNet\D3\Connections;
/**
* Perform request to BattleNet
*/
use function \Kshabazz\Slib\isString,
			 \Kshabazz\Slib\isArray;
/**
 * Class Http
 *
 * @package Kshabazz\BattleNet
 */
class Http extends \Kshabazz\Slib\HttpRequester implements Connection
{
	const
		D3_API_PROFILE_URL = 'http://us.battle.net/api/d3/profile',
		D3_API_HERO_URL = 'http://us.battle.net/api/d3/profile/%s/hero/%d',
		D3_API_ITEM_URL = 'http://us.battle.net/api/d3/data/%s';

	private
		$battleNetId,
		$battleNetUrlSafeId;

	/**
	 * Constructor
	 *
	 * @param string $pBattleNetId
	 */
	public function __construct( $pBattleNetId )
	{
		parent::__construct( NULL );
		$this->battleNetId = $pBattleNetId;
		$this->battleNetUrlSafeId = str_replace( '#', '-', $this->battleNetId );
	}

	/**
	 * Destructor
	 */
	public function __destruct()
	{
		unset(
			$this->battleNetId,
			$this->battleNetUrlSafeId,
			$this->url
		);
	}

	/**
	 * Get BattleNet ID
	 *
	 * @return string BattleNet ID
	 */
	public function battleNetUrlSafeId()
	{
		return $this->battleNetUrlSafeId;
	}

	/**
	 * Get BattleNet ID
	 *
	 * @return string BattleNet ID
	 */
	public function battleNetId()
	{
		return $this->battleNetId;
	}

	/**
     * Request Hero JSON from Battle.Net.
	 * ex: http://us.battle.net/api/d3/profile/<battleNetIdName>-<battleNetIdNumber>/hero/<hero-id>
     * Note: Leave off the trailing '/' when setting
	 *
	 * @param $pHeroId
	 * @return null|string
	 * @throws \InvalidArgumentException
	 */
	public function getHero( $pHeroId )
	{
		if ( !is_int($pHeroId) )
		{
			throw new \InvalidArgumentException( 'Expected an integer, got a '. gettype($pHeroId) );
		}
		// Construct the Battle.net URL.
		$this->url = sprintf( self::D3_API_HERO_URL, $this->battleNetUrlSafeId, $pHeroId );
		// Request the hero JSON from BattleNet.
		return $this->makeRequest();
	}

	/**
	 * Get item JSON from Battle.Net D3 API.
	 * ex: http://us.battle.net/api/d3/data/item/COGHsoAIEgcIBBXIGEoRHYQRdRUdnWyzFB2qXu51MA04kwNAAFAKYJMD
	 *
	 * @param $pItemId
	 * @return mixed|null
	 * @throws \InvalidArgumentException
	 * @throws \Exception
	 */
	public function getItem( $pItemId )
	{
		if ( !isString($pItemId) )
		{
			throw new \InvalidArgumentException(
				"Expects a valid item id, but was given: '{$pItemId}'."
			);
		}
		// Construct the Battle.net URL.
		$this->url = sprintf( self::D3_API_ITEM_URL, $pItemId );
		return $this->makeRequest();
	}

	/**
	 * ex: http://us.battle.net/api/d3/profile/<battleNetIdName>-<battleNetIdNumber>/
	 *
	 * @return null|string
	 * @throws \Exception
	 */
	public function getProfile()
	{
		// Construct the Battle.net URL.
		$this->url = self::D3_API_PROFILE_URL . '/' . $this->battleNetUrlSafeId . '/';
		// Return the response text.
		return $this->makeRequest();
	}

	/**
	 * Make a request to the currently set {@see $this->url}.
	 * @return string|null
	 * @throws \Exception
	 */
	private function makeRequest()
	{
		// Request the item from BattleNet.
		$responseText = $this->send();
		// When the response is good, return the response text.
		$requestSuccessful = ($this->responseCode() === 200);
		if ($requestSuccessful)
		{
			return $responseText;
		}
		return NULL;
	}
}
?>