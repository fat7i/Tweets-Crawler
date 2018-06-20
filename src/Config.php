<?php

namespace TwitterApi;


/**
 * Class Config
 * @package TwitterApi
 */
class Config
{
    /** @var string consumer key of  Twitter application  */
    public $consumer_key;
    /** @var string consumer secret of  Twitter application  */
    public $consumer_secret;

    /**
     * @param string $consumer_key
     * @param string $consumer_secret
     */
    public function __construct($consumer_key, $consumer_secret)
    {
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;
    }

}
