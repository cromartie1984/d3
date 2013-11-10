<?php namespace kshabazz\d3a;
/**
* Take inventory of all stats on each item equipped.
*
* @var array $p_items A hash array of items, by which the keys indicate where the items are placed
*	on the hero.
*/
class Model_GetHero
{
	const
		APS_DUAL_WIELD_BONUS = 0.15,
		CRITICAL_HIT_CHANCE_BONUS = 0.08,
		CRITICAL_HIT_DAMAGE_BONUS = 0.05;

	public
		$itemHashes,
		$requestTime;

	protected
		$attributeMap,
		$bnr,
		$bnrHero,
		$itemModels,
		$slotStats,
		$sql,
		$stats;

	/**
	 * Constructor
	 *
	 * @param BattleNet_Hero $bnrHero
	 * @param array $pAttributeMap
	 * @param BattleNet_Requestor $pBnr
	 * @param BattleNet_Sql $pSql
	 */
	public function __construct( BattleNet_Hero $bnrHero, array & $pAttributeMap, BattleNet_Requestor $pBnr, BattleNet_Sql $pSql )
	{
		$this->attributeMap = $pAttributeMap;
		$this->bnr = $pBnr;
		$this->bnrHero = $bnrHero;
		$this->itemModels = NULL;
		$this->items = NULL;
		$this->sql = $pSql;
		$this->stats = [];
		$this->json = $this->bnrHero->json();
		$this->requestTime = $_SERVER[ 'REQUEST_TIME_FLOAT' ];
		$this->init();
		$this->renderSetup();
	}

	/**
	 * Get Hero, used by template engine.
	 *
	 * @return array
	 */
	public function hero()
	{
		return $this->hero;
	}

	/**
	 * Get item hashes by item slot
	 *
	 * @return array
	 */
	public function itemHashes()
	{
		return $this->items;
	}

	/**
	 * @return string JSON from battle.net
	 */
	public function json()
	{
		return $this->json;
	}

	/**
	 * Get the items.
	 *
	 * @return array
	 */
	public function getItemModels()
	{
		if ( !isset($this->itemModels) && $this->bnrHero instanceof BattleNet_Hero )
		{
			$this->itemModels = [];
			$this->itemHashes = [];
			$this->items = $this->bnrHero->items();
			// It is valid that the bnrHero may not have any items equipped.
			if ( isArray($this->items) )
			{
				foreach ( $this->items as $slot => $item )
				{
					$hash = str_replace( "item/", '', $item['tooltipParams'] );
					$bnItem = new BattleNet_Item( $hash, "hash", $this->bnr, $this->sql );
					$this->itemModels[ $slot ] = new Item( $bnItem->json() );
					// for output to JavaScript variable.
					$this->itemHashes[ $slot ] = $hash;
				}
			}
		}

		return $this->itemModels;
	}

	/**
	 * Initialize this object.
	 */
	protected function init()
	{
		$this->getItemModels();
		$this->processRawAttributes();

		if ( isset($this->itemModels['mainHand']) )
		{
			// ??? this just seems all kinds of wrong.
			$this->dualWield = $this->itemModels[ 'mainHand' ]->type[ 'twoHanded' ];
		}
		$this->time = microtime( TRUE ) - $_SERVER[ 'REQUEST_TIME_FLOAT' ];
		$this->hero = new Hero( $this->bnrHero->json() );
		$this->primaryAttribute = $this->hero->primaryAttribute();
		$this->calculator = new Calculator( $this->hero, $this->attributeMap, $this->itemModels );
	}

	/**
	 * Loop through raw attributes for every item.
	 * @return float
	 */
	protected function processRawAttributes()
	{
		if ( isArray($this->itemModels) )
		{
			foreach ( $this->itemModels as $slot => $item )
			{
				// Compute some things.
				$this->tallyAttributes( $item->attributesRaw, $slot );
				// Tally gems.
				$this->tallyGemAttributes( $pRawAttribute, $slot );
			}
		}
	}

	/**
	 * Render setup
	 * @return $this
	 */
	public function renderSetup()
	{
		$this->name = $this->hero->name;
		$this->hardcore = ( $this->hero->hardcore ) ? 'Hardcore ' : '';
		$this->deadText = '';
		if ( $hero->dead )
		{
			$this->deadText = "This {$this->hardcore}hero fell on " . date( 'm/d/Y', $this->hero->{'last-updated'} ) . ' :(';
		}
		$this->sessionTimeLeft = displaySessionTimer( $this->sessionCacheInfo['timeLeft'] );
		$this->progress = getProgress( $this->hero->progress );
		$this->battleNetId = '';
		$this->battleNetUrlSafeId = '';
		$this->heroItemHashes = json_encode( $this->itemHashes );
		$this->items = $this->itemModels;

		// $this->class = $this->hero->class;
		$this->heroJson = $this->hero->json();
		return $this;
	}

	/**
	 * Set Hero
	 *
	 * @param Hero $pHero
	 * @return $this
	 */
	public function setHero( Hero $pHero )
	{
		$this->hero = $pHero;
		return $this;
	}

	/**
	 * Tally raw attributes.
	 * @param $pRawAttribute
	 * @param $pSlot
	 * @return $this
	 */
	protected function tallyAttributes( $pRawAttribute, $pSlot )
	{
		foreach ( $pRawAttribute as $attribute => $values )
		{
			$value = ( float )$values[ 'min' ];

			// Initialize an attribute in the totals array.
			if ( !array_key_exists($attribute, $this->stats) )
			{
				$this->stats[ $attribute ] = 0.0;
				$this->slotStats[ $attribute ] = [];
			}
			// Sum it up.
			$this->stats[ $attribute ] += $value;
			// A break-down of each attribute totals. An item can have multiple types of the same attribute
			// use a combination of the slot and attribute name to keep them from replacing the previous value.
			$this->slotStats[ $attribute ][ $pSlot . '_' . $attribute ] = $value;

			// Add the attribute to the map collection.
			if ( !array_key_exists($attribute, $this->attributeMap) )
			{
				$this->attributeMap[ $attribute ] = '';
			}
		}
		return $this;
	}

	/**
	* Tally raw gem attributes.
	* @return float
	*/
	protected function tallyGemAttributes( $pGems, $pSlot )
	{
		if ( isArray($pGems) )
		{
			for ( $i = 0; $i < count($pGems); $i++ )
			{
				$gem = $pGems[ $i ];
				if ( isArray($gem) )
				{
					$this->tallyAttributes( $gem['attributesRaw'], "{$pSlot} gem slot {$i}" );
				}
			}
		}
	}
}
?>