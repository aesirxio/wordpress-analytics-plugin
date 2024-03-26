<?php

namespace AesirxAnalytics\Track;

abstract class AbstractMessage {
    /**
     * @return false|string
     */
    public function __serialize() {
        return json_encode($this);
    }

    public function toString(): string {
        return $this->__serialize();
    }

    public function __toString(): string {
        return $this->toString();
    }

    abstract public function asCliCommand(): array;
}