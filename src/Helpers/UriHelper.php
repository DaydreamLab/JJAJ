<?php

namespace DaydreamLab\JJAJ\Helpers;

use GuzzleHttp\Client;

class UriHelper {

    public static function getShortCode($fullUrl)
    {
        $client = new Client();

        $domain = config('app.env') == 'production'
            ? config('app.dingsomthing.dsth.url', 'https://dsth.me/')
            : config('app.dingsomthing.dsth.url', 'https://demo.dsth.me/');

        $shortCodeUri = $domain . 'shortcode.php';
        $shortenUrl = $domain . 'shorten.php';
        $response = $client->request('POST', $shortCodeUri, [
            'form_params' => [
                'url' => $fullUrl
            ]
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
