<?php

namespace GifCreator\Exceptions;

use GifCreator\GifCreator;

final class FrameIsAlreadyAnimatedException extends GeneralGifCreatorException
{
    public function __construct(GifCreator $instance, public readonly int $sourceIndex)
    {
        parent::__construct(
            $instance,
            sprintf(
                '%1$s: %2$s (%3$d) source)',
                $instance->getVersion(),
                'Does not make animation from animated GIF source.',
                $sourceIndex,
            )
        );
    }
}