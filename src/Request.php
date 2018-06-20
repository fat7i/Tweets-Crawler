<?php

namespace TwitterApi;

/**
 * Class Request
 * handle the API Requests and Responses
 * @package TwitterApi
 */
class Request
{


    /** @var  Config details about API configuration*/
    private $config;
    /** @var string Application bearer token */
    private $bearer;
    /** @var String of request */
    private $header;
    /** @var String of url */
    private $url;
    /** @var String one week ago */
    private $oneWeekAgo;


    use BearerTrait;

    /**
     * Request constructor.
     * @param $config
     */
    public function __construct(Config $config)
    {
        $this->setConfig($config);
    }

    /**
     * @return String
     */
    public function getUrl(): String
    {
        return $this->url;
    }

    /**
     * @param String $url
     */
    public function setUrl(String $url)
    {
        $this->url = $url;
    }

    /**
     * @return String
     */
    public function getOneWeekAgo(): String
    {
        return $this->oneWeekAgo;
    }

    /**
     * @param String $oneWeekAgo
     */
    public function setOneWeekAgo($oneWeekAgo)
    {
        $this->oneWeekAgo = $oneWeekAgo;
    }

    /**
     * @param Config $config
     */
    private function setConfig (Config $config)
    {
        $this->config = $config;
    }

    /**
     * @return Config details
     */
    private function getConfig (): Config
    {
        return $this->config;
    }

    /**
     * set the http head of request
     */
    public function setHeader ()
    {
        $this->bearer = (isset($this->bearer))? $this->bearer : $this->getBearer();
        $this->header = 'Authorization: Bearer '. $this->bearer;
    }

    /**
     * @return header
     */
    private function getHeader (): String
    {
        if (!isset($this->header))
            $this->setHeader();
        return $this->header;
    }

    /**
     * get bearer token if is set, and get it from twitter API if not
     * @return string
     * @throws \Exception
     */
    public function getBearer ()
    {
        if (isset($this->bearer))
            return $this->bearer;
        else
           return BearerTrait::get($this->getConfig ()->consumer_key, $this->getConfig ()->consumer_secret);
    }

    /**
     * do the Api request and receive and handle the response
     * @param $queryArray array of GET params
     * @return string as json array
     * @throws \Exception
     */
    public function getResponse (array $queryArray)
    {
        //get head of the request
        $this->header = $this->getHeader();

        //do the twitter API request
        $fullResponse = $this->curl($queryArray);

        // json decode response body
        $responseArray = json_decode($fullResponse['body'], true);

        //throw exception if twitter api return error
        if (isset($responseArray['error']))
            throw new \RuntimeException('Error response from Twitter: ' . $responseArray['error']['message']);


        if (isset($responseArray['statuses']))
        {
            $validTweets = array_filter($responseArray['statuses'],
                function ($tweet)  {
                    return strtotime($tweet['created_at']) >= $this->oneWeekAgo; }
            );

            Sqlite::insertTweets($queryArray['q'], $validTweets);

            $lastTweet = end($validTweets);

            if (count($validTweets) == count($responseArray['statuses']) && $lastTweet['id'] != $queryArray['max_id'])
            {
                $queryArray['max_id'] = $lastTweet['id'];
                return $queryArray;
            }

        }


        //get hashtag statistics from the database
        $result = Sqlite::getResult($queryArray['q'], $this->oneWeekAgo);

        return json_encode(['status'=>'ok', 'hashtag' => $queryArray['q'], 'result'=>$result]);
    }

    /**
     * curl twitter API and receive the response
     * @param $getParams array
     * @return bool|mixed
     * @throws \Exception
     */
    private function curl (array $getParams)
    {
        $options = [
            CURLOPT_URL => $this->getUrl() .'?' .http_build_query($getParams),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [$this->getHeader()],
            CURLOPT_HEADER => true
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);

        if (!$response)
        {
            sleep(1);
            $this->curl($getParams);
        }

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $output['header'] = self::parseHeaders(substr($response, 0, $headerSize));
        $output['body'] = substr($response, $headerSize);


        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        //http code reset header and bearer token
        if ($httpCode != 200 && $httpCode == 429)
        {
            // renew the header and bearer
            $this->header = null;
            $this->bearer = null;

            $sec = self::calcSleepSec($output['header']['x_rate_limit_reset']);
            sleep($sec);
            $this->getResponse($getParams);
        }

        if (($error = curl_error($ch)) !== '') {
            curl_close($ch);
            throw new \Exception($error);
        }
        curl_close($ch);

        return $output;
    }

    /**
     * parse a response header and return it as a PHP array
     * @param $header
     * @return array [x_rate_limit_limit , x_rate_limit_remaining, x_rate_limit_reset]
     */
    private static function parseHeaders(String $header)
    {
        $headers = [];
        foreach (explode("\r\n", $header) as $line) {
            if (strpos($line, ':') !== false && substr($line, 0, 6) === 'x-rate') {
                list ($key, $value) = explode(': ', $line);
                $key = str_replace('-', '_', strtolower($key));
                $headers[$key] = trim($value);
            }
        }
        return $headers;
    }

    /**
     * calculate how much time i should wait until the twitter reset my application time
     * @param int $toTime
     * @return mixed
     */
    public static function calcSleepSec (int $toTime)
    {
        $now = time();
        $diff = $toTime - $now;
        return max(0, $diff);
    }



}