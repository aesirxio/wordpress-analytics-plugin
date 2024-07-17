<?php

namespace AesirxAnalytics\Track;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

class ApiTracker extends AbstractTracker {

    private Client $client;
    private LoggerInterface $logger;

    public function __construct(string $url, LoggerInterface $logger)
    {
        $this->client = new Client([
            'base_uri' => $url,
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ]);

        $this->logger = $logger;
    }

    protected function doTrack(): void
    {
        foreach ($this->messages as $message)
        {
            try {
                $response = $this->client->patch('/conversion/v1/replace', [
                    'body' => $message->toString(),
                ]);
                
                // Optionally, log the response or handle it as needed
                $this->logger->info('Request successful', [
                    'status_code' => $response->getStatusCode(),
                    'body' => $response->getBody()->getContents()
                ]);
            } catch (RequestException $e) {
                // Log the error or handle it as needed
                $this->logger->error('Request failed', [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'body' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null
                ]);
            }
        }
    }
}