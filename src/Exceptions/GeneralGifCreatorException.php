<?php

namespace GifCreator\Exceptions;

use GifCreator\GifCreator;
use Throwable;

class GeneralGifCreatorException extends \Exception
{
    public function __construct(
        public readonly GifCreator $instance,
        string $message,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            $message,
            $code,
            $previous
        );
    }
}