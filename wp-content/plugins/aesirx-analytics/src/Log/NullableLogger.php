<?php

namespace AesirxAnalytics\Log;

class NullableLogger implements LoggerInterface {
    public function log(string $message): void
    {
    }

    public function error(string $message): void
    {
    }
}