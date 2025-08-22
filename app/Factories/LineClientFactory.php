<?php

namespace App\Factories;

use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Clients\MessagingApi\Configuration;

class LineClientFactory
{
    public static function create(): MessagingApiApi
    {
        $channelToken = config('line.channel_access_token');
        
        if (empty($channelToken)) {
            throw new \RuntimeException('LINE channel access token not configured');
        }
        
        $config = new Configuration();
        $config->setAccessToken($channelToken);
        
        return new MessagingApiApi(new \GuzzleHttp\Client(), $config);
    }
}