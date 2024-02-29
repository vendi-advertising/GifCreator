<?php

namespace GifCreator\Exceptions;

use GifCreator\GifCreator;

final class MoreThanOneFrameRequiredException extends GeneralGifCreatorException
{
    public function __construct(GifCreator $instance)
    {
        parent::__construct(
            $instance,
            sprintf(
                '%1$s: %2$s',
                $instance->getVersion(),
                'Does not supported function for only one image.',
            )
        );
    }
}