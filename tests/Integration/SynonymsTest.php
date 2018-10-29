<?php

namespace Algolia\AlgoliaSearch\Tests\Integration;

class SynonymsTest extends AlgoliaIntegrationTestCase
{
    private $caliSyn = array(
        'objectID' => 'cali',
        'type' => 'synonym',
        'synonyms' => array('Los Angeles', 'LA', 'Venice'),
    );

    private $pekingSyn = array(
        'objectID' => 'china',
        'type' => 'synonym',
        'synonyms' => array('Beijing', 'Peking'),
    );

    private $anotherSyn = array(
        'objectID' => 'city',
        'type' => 'synonym',
        'synonyms' => array('city', 'town', 'village'),
    );

    protected function setUp()
    {
        parent::setUp();

        if (!isset(static::$indexes['main'])) {
            static::$indexes['main'] = $this->safeName('synomyms-mgmt');
        }
    }

    public function testSynonymsCanBeSavedAndRetrieved()
    {
        $index = static::getClient()->initIndex(static::$indexes['main']);

        $index->saveObjects($this->airports);

        $index->saveSynonym($this->pekingSyn);

        $index->saveSynonyms(array($this->caliSyn));

        $this->assertArraySubset($this->pekingSyn, $index->getSynonym('china'));
        $this->assertArraySubset($this->caliSyn, $index->getSynonym('cali'));

        $index->deleteSynonym('china');

        $res = $index->searchSynonyms('');
        $this->assertArraySubset(array('nbHits' => 1), $res);

        $index->replaceAllSynonyms(array($this->anotherSyn));
        $res = $index->searchSynonyms('');
        $this->assertArraySubset(array('nbHits' => 1), $res);
        $this->assertArraySubset($this->anotherSyn, $res['hits'][0]);

        $index->clearSynonyms();
        $res = $index->searchSynonyms('');
        $this->assertArraySubset(array('nbHits' => 0), $res);
    }

    public function testBrowseSynonyms()
    {
        $index = static::getClient()->initIndex(static::$indexes['main']);

        $index->saveObject($this->airports[0]);

        $index->replaceAllSynonyms(array($this->caliSyn, $this->pekingSyn, $this->anotherSyn));

        $previousObjectID = '';
        $iterator = $index->browseSynonyms(array('hitsPerPage' => 1));
        foreach ($iterator as $synonym) {
            $this->assertArraySubset(array('type' => 'synonym'), $synonym);
            $this->assertFalse($synonym['objectID'] == $previousObjectID);
            $previousObjectID = $synonym['objectID'];
        }
    }
}
