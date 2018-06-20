<?php

namespace TwitterApi;

/**
 * Trait StringValidationTrait
 * @package TwitterApi
 */
trait StringValidationTrait
{
    /**
     * @param $string
     * @throws InvalidArgumentException
     * @return true or throw exception
     */
    public static function isHashtag ($string)
    {
        if (preg_match('/^#[A-Za-z0-9_]*$/', $string)) {
            return true;
        } else {
            throw new \InvalidArgumentException('Invalid Hashtag');
        }
    }
}