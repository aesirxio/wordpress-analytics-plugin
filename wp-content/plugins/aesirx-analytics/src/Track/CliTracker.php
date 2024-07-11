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
     * Processes analytics tracking using CLI commands.
     *
     * This method iterates over a collection of messages and processes each one
     * by converting it to a CLI command and passing it to the CLI processor.
     * 
     * @throws ExceptionWithErrorType if an error occurs during the processing of any CLI command.
     */
    protected function doTrack(): void {
        foreach ($this->messages as $message)
        {
            try {
                // Convert the message to a CLI command and process it using the CLI handler
                $this->cli->processAnalytics($message->asCliCommand());
            } catch (ExceptionWithErrorType $e) {
                // Handle specific exceptions that might occur during CLI command processing
                throw $e;
            } catch (Exception $e) {
                // Handle any other general exceptions that might occur
                throw new ExceptionWithErrorType('Error processing CLI command', 0, $e);
            }
        }
    }
}