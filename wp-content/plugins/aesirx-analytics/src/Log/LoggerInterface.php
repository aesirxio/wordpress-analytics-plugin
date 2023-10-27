<?php

namespace AesirxAnalytics\Log;

interface LoggerInterface {
    public function log(string $message): void;

    public function error(string $message): void;
}