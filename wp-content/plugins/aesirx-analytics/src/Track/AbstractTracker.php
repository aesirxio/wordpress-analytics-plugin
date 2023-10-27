<?php

namespace AesirxAnalytics\Track;

abstract class AbstractTracker
{
    /**
     * @var AbstractMessage[]
     */
    protected array $messages = [];

    public function push(AbstractMessage $message): self
    {
        $this->messages[] = $message;

        return $this;
    }
    public function track(): void
    {
        $this->doTrack();
        $this->messages = [];
    }

    abstract protected function doTrack(): void;
}