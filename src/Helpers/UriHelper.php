<?php

namespace DaydreamLab\JJAJ\Helpers;

use DaydreamLab\Dsth\Notifications\DeveloperNotification;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Notification;

class UriHelper
{
    public static function getShortCode($fullUrl)
    {
        $client = new Client();

        $domain = config('app.env') == 'production'
            ? config('app.dingsomthing.dsth.url', 'https://dsth.me/')
            : 'https://demo.dsth.me/';

        $shortCodeUri = $domain . (config('app.env') == 'production' ? 'shorten' : 'shortcode.php');
        $shortenUrl = $domain . (config('app.env') == 'production' ? 'shorten' : 'shorten.php');

        $response = $client->request('POST', $shortCodeUri, [
            'form_params' => [
                'url' => $fullUrl
            ],
        ]);

        $r = [];
        if ($response->getStatusCode() == 200) {
            $data = json_decode($response->getBody()->getContents());
            $r['shortCode'] =  $data->code;
            $r['data'] = $data;
        } elseif ($response->getStatusCode() == 303) {
            $response = $client->request('POST', $shortenUrl, [
                'form_params' => [
                    'url' => $fullUrl
                ]
            ]);

            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody()->getContents());
                if (!$data || !property_exists($data, 'code')) {
                    $response = $client->request('POST', $shortenUrl, [
                        'form_params' => [
                            'url' => $fullUrl
                        ]
                    ]);
                    $data = json_decode($response->getBody()->getContents());
                }
                $r['shortCode'] =  $data->code;
                $r['data'] = $data;
            } else {
                $r['shortCode'] = 'error!!';
                $r['data'] = json_decode($response->getBody()->getContents());
            }
        } else {
            $r['shortCode'] = 'error!!';
            $r['data'] = json_decode($response->getBody()->getContents());
        }

        return $r;
    }
}
