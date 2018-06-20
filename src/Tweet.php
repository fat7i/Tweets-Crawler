<?php

namespace TwitterApi;

date_default_timezone_set('UTC');
ini_set('max_execution_time', 0);


/**
 * Class Tweet
 * @package TwitterApi
 */
class Tweet
{
    /** api version */
    const API_VERSION = '1.1';
    /** api host url */
    const API_HOST = 'https://api.twitter.com';
    /** @var Config */
    private $config;
    /** @var String one week ago */
    private $oneWeekAgo;
    /** @var Request */
    private $request;


    use StringValidationTrait;


    /**
     * Tweet constructor.
     * @param array $settings configuration array of Twitter application
     */
    public function __construct(array $settings)
    {
        $this->setConfig(new Config($settings['consumer_key'], $settings['consumer_secret']));

        // the default is one day before, of course, you can change it as you want, but be noted that the Twitter API only provide tweets from one week ago
        $this->setOneWeekAgo(strtotime("-1 day"));
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @param Config $config
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @return String
     */
    public function getOneWeekAgo(): String
    {
        return $this->oneWeekAgo;
    }

    /**
     * @param String  $oneWeekAgo
     */
    public function setOneWeekAgo($oneWeekAgo)
    {
        $this->oneWeekAgo = $oneWeekAgo;
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
        $this->request->setUrl($this->getUrl());
        $this->request->setOneWeekAgo($this->oneWeekAgo);
    }

    /**
     * validate the hashtag, then get statistics
     * @param String $hashtag
     * @throws InvalidArgumentException if hashtag not vaild
     * @return string as a json string
     */
    public function getHashtagStatistics(String $hashtag)
    {

        /**
         * This line for check whether if $hastag is a hashtag or not
         * you can comment it in case you gathering  tweets for a word
         */
        StringValidationTrait::isHashtag($hashtag);

        $this->setRequest(new Request($this->config));

        return $this->getStatistics($hashtag);
    }

    /**
     * get hashtag statistics by requesting the API recursively
     * @param String $hashtag
     * @throws
     * @return string hashtag statistics as a json array
     */
    public function getStatistics (String $hashtag)
    {

        $queryArray = $this->getQuery($hashtag);

        if (ob_get_level() == 0) ob_start();

        $response = $this->request->getResponse($queryArray);
        while ( is_array($response) ){

            sleep(1);
            $response = $this->request->getResponse($response);
            echo str_pad("",4096);

            ob_flush();
            flush();
        }
        ob_end_flush();

        return $response;
    }

    /**
     * build a api url
     * @return string
     */
    private function getUrl ()
    {
        return sprintf('%s/%s/%s.json', self::API_HOST, self::API_VERSION, 'search/tweets');
    }

    /**
     * build a query array for the request
     * @param $hashtag String
     * @return array
     */
    private function getQuery (String $hashtag)
    {
        return ['q'=> $hashtag, 'count' => 10, 'include_entities' => 1, 'max_id' => ''];
    }

}