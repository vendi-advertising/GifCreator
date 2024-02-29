<?php

namespace GifCreator\Exceptions;

use GifCreator\GifCreator;

final class InvalidFrameTypeException extends GeneralGifCreatorException
{
    public function __construct(GifCreator $instance)
    {
        parent::__construct(
            $instance,
            sprintf(
                '%1$s: %2$s',
                $instance->getVersion(),
                'You have to give resource image variables, image URL or image binary sources in $frames array.'
            )
        );
    }
}