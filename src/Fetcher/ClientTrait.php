<?php


namespace App\Fetcher;


use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
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
        $jar = new CookieJar();
        $jar->setCookie(
            new SetCookie(
                [
                    'Name'   => 'lastvisited',
                    'Value'  => 'vaihingen_germany_2817929; locale=en_GB',
                    'Domain' => null,
                    'Path'   => '/',
                ]
            )
        );
        return new Client(
            [
                'base_uri'                      => $uri,
                RequestOptions::ALLOW_REDIRECTS => [
                    'max'             => 2,
                    'strict'          => false,
                    'referer'         => false,
                    'protocols'       => ['https'],
                    'track_redirects' => false,
                ],
                RequestOptions::HTTP_ERRORS     => false,
                RequestOptions::CONNECT_TIMEOUT => 20,
                RequestOptions::TIMEOUT         => 40,
                RequestOptions::HEADERS         => [
                    'Accept'          => '*/*',
                    'Accept-Language' => 'en_GB, de;q=0.7',
                    'User-Agent'      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0.3 Safari/605.1.15,',
                ],
                RequestOptions::COOKIES         => $jar,
            ]
        );
    }

}

