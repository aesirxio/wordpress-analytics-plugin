<?php

namespace AesirxAnalytics\Track;

abstract class AbstractMessage {
    /**
     * Serializes the object to a JSON string.
     *
     * This method converts the object into a JSON-encoded string. It is used
     * to serialize the object's properties into a format that can be easily stored
     * or transmitted.
     *
     * @return false|string The JSON-encoded string representing the object, or false on failure.
     */
    public function __serialize() {
        return wp_json_encode($this);
    }

    /**
     * Converts the object to a string representation.
     *
     * This method provides a string representation of the object by calling the
     * __serialize method. It is useful for debugging and logging purposes.
     *
     * @return string The JSON-encoded string representation of the object.
     */
    public function toString(): string {
        return $this->__serialize();
    }

    /**
     * Magic method to convert the object to a string.
     *
     * This method is automatically called when the object is treated as a string.
     * It calls the toString method to provide a string representation of the object.
     *
     * @return string The JSON-encoded string representation of the object.
     */
    public function __toString(): string {
        return $this->toString();
    }

    /**
     * Generates a CLI command array from the object.
     *
     * This abstract method must be implemented by subclasses to provide a specific
     * CLI command representation of the object. The CLI command is returned as an
     * array, where each element represents a part of the command.
     *
     * @return array The CLI command array representing the object.
     */
    abstract public function asCliCommand(): array;
}