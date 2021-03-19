<?php


namespace App\Fetcher;


use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

trait ClientTrait
{
    
    /**
     * Configures guzzle client
     *
     * @param string $uri
     * @return Client
     */
    protected function client(string $uri): Client
    {
        return new Client(
            [
                'base_uri'                      => $uri,
                RequestOptions::ALLOW_REDIRECTS => [
                    'max'             => 2,
                    'strict'          => false,
                    'referer'         => false,
                    'protocols'       => ['https'],
                    'track_redirects' => false
                ],
                RequestOptions::CONNECT_TIMEOUT => 20,
                RequestOptions::TIMEOUT         => 40,
                RequestOptions::HEADERS         => [
                    'Accept-Language' => 'de_DE, de;q=0.7'
                ]
            ]
        );
    }
    
}

