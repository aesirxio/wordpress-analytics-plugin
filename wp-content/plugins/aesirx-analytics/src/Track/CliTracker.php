<?php

namespace AesirxAnalytics\Track;

use AesirxAnalyticsLib\Cli\AesirxAnalyticsCli;
use AesirxAnalyticsLib\Exception\ExceptionWithErrorType;

class CliTracker extends AbstractTracker {
    private AesirxAnalyticsCli $cli;

    public function __construct(AesirxAnalyticsCli $cli)
    {
        $this->cli = $cli;
    }

    /**
     * @throws ExceptionWithErrorType
     */
    protected function doTrack(): void {
        foreach ($this->messages as $message)
        {
            $this->cli->processAnalytics($message->asCliCommand());
        }
    }
}