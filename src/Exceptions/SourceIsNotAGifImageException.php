<?php

namespace GifCreator\Exceptions;

use GifCreator\GifCreator;

final class SourceIsNotAGifImageException extends GeneralGifCreatorException
{
    public function __construct(
        public readonly int $frameSourceIndex,
        GifCreator $instance
    ) {
        parent::__construct(
            $instance,
            sprintf(
                '%1$s:%2$d %3$s',
                $instance->getVersion(),
                $frameSourceIndex,
                'Source is not a GIF image.',
            )
        );
    }
}