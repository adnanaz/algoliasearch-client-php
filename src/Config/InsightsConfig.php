<?php

namespace Algolia\AlgoliaSearch\Config;

final class InsightsConfig extends AbstractConfig
{
    public static function create($appId = null, $apiKey = null, $region = null)
    {
        $config = array(
            'appId' => null !== $appId ? $appId : getenv('ALGOLIA_APP_ID'),
            'apiKey' => null !== $apiKey ? $apiKey : getenv('ALGOLIA_API_KEY'),
            'region' => null !== $region ? $region : 'us',
        );

        return new static($config);
    }

    public function setRegion($region)
    {
        $this->config['region'] = $region;

        return $this;
    }

    public function getRegion()
    {
        return $this->config['region'];
    }

    public function setUserToken($userToken)
    {
        $this->config['userToken'] = $userToken;

        return $this;
    }

    public function getUserToken()
    {
        return $this->config['userToken'];
    }
}