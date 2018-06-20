<?php

namespace TwitterApi;

/**
 * Class Bearer
 * @package TwitterApi
 */
trait BearerTrait
{
    /**
     * request bearer token from twitter API
     * @param $consumer_key consumer key of Twitter application
     * @param $consumer_secret consumer secret of  Twitter application
     * @return mixed
     * @throws \Exception
     */
    public static function get ($consumer_key, $consumer_secret)
    {
        $url = 'https://api.twitter.com/oauth2/token';
        $header = 'Authorization: Basic '. base64_encode($consumer_key.':'.$consumer_secret);

        $param['grant_type'] = 'client_credentials';


        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => ["grant_type" => "client_credentials"],
            CURLOPT_HTTPHEADER => [$header],
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            throw new \Exception('cURL Error #:' . $err);
        } else {
            $response = json_decode($response, true);

            if(!isset($response['access_token']))
                throw new \Exception('getHashtagStatistics bearer token failed: incorrect consumer_key OR consumer_secret');


            return $response['access_token'];
        }
    }
}