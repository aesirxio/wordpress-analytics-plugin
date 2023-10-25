<?php

namespace AesirxAnalytics\Track;

use GuzzleHttp\Client;

class ApiTracker extends AbstractTracker {

    private Client $client;

    public function __construct(string $url)
    {
        $this->client = new Client([
            'base_uri' => $url,
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ]);
    }

    protected function doTrack(): void
    {
        foreach ($this->messages as $message)
        {
            $this->client->patch('/conversion/v1/replace', [
                'body' => $message->toString(),
            ]);
        }
    }
}