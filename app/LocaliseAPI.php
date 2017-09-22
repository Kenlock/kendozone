<?php

namespace App;

use GuzzleHttp\Client;

class LocaliseAPI
{
    protected $apiKey;
    protected $projectId;
    protected $urlBase = "https://api.lokalise.co/api";

    /**
     * LocaliseAPI constructor.
     * @param string $apiKey
     * @param string $projectId
     */
    public function __construct($apiKey, $projectId)
    {
        $this->apiKey = $apiKey;
        $this->projectId = $projectId;
    }


    /**
     * Return all Langs in Localise
     * @return array
     */
    public function listLangsInProject()
    {
        try {
            $body = (new Client())
                ->request('GET', $this->urlBase . '/language/list?api_token=' . $this->apiKey . '&id=' . $this->projectId)
                ->getBody();
            return json_decode($body)->languages;
        } catch (\Exception $e) {
            return [];
        }

    }
}