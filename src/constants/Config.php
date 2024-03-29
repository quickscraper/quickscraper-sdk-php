<?php

namespace QuickScraper\Constants;

class Config
{
    protected $BASE_URL;

    public function __construct()
    {
        $this->BASE_URL = 'https://api.quickscraper.co/';
    }
  
    public function getBaseUrl()
    {
        $baseUrl = getenv('QS_BASE_URL');
        if ($baseUrl && $baseUrl !== '') {
            return $baseUrl;
        }
        return $this->BASE_URL;
    }
    public static function getAccessToken()
    {
        return getenv('QS_ACCESS_TOKEN');
    }
}
