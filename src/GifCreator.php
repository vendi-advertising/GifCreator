<?php

namespace GifCreator;

use Exception;
use GifCreator\Exceptions\GeneralGifCreatorException;
use GifCreator\Exceptions\FrameIsAlreadyAnimatedException;
use GifCreator\Exceptions\InvalidFrameTypeException;
use GifCreator\Exceptions\MoreThanOneFrameRequiredException;
use GifCreator\Exceptions\SourceIsNotAGifImageException;

/**
 * Create an animated GIF from multiple images
 *
 * @version 1.0
 * @link https://github.com/Sybio/GifCreator
 * @author Sybio (Clément Guillemain  / @Sybio01)
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @copyright Clément Guillemain
 */
class GifCreator
{
    private const VERSION = 'GifCreator: Under development';

    /**
     * The gif string source
     */
    private string $gif;

    /**
     * Check the image is build or not
     */
    private bool $imgBuilt;

    /**
     * Frames string sources
     */
    private array $frameSources;

    /**
     * Gif loop
     */
    private int $loop;

    /**
     * Gif dis
     */
    private int $dis;

    /**
     * Gif color
     */
    private int $colour;

    public function __construct()
    {
        $this->reset();
    }

    /**
     * Reset and clean the current object
     */
    public function reset(): void
    {
        $this->frameSources = [];
        $this->gif = 'GIF89a'; // the GIF header
        $this->imgBuilt = false;
        $this->loop = 0;
        $this->dis = 2;
        $this->colour = -1;
    }

    /**
     * Create the GIF string (old: GIFEncoder)
     *
     * @param array $frames An array of frame: can be file paths, resource image variables, binary sources or image URLs
     * @param array $durations An array containing the duration of each frame
     * @param integer $loop Number of GIF loops before stopping animation (Set 0 to get an infinite loop)
     *
     * @return string The GIF string source
     * @throws Exception
     */
    public function create(array $frames = [], array $durations = [], int $loop = 0): string
    {
        if (!is_array($frames) && !is_array($durations)) {
            throw new MoreThanOneFrameRequiredException($this);
        }

        $this->loop = ($loop > -1) ? $loop : 0;
        $this->dis = 2;

        for ($i = 0, $iMax = count($frames); $i < $iMax; $i++) {

            $thisFrame = $frames[$i];

            if (is_resource($thisFrame) || $thisFrame instanceof \GdImage) { // Resource var

                $resourceImg = $thisFrame;

                ob_start();
                imagegif($thisFrame);
                $this->frameSources[] = ob_get_clean();

            } elseif (is_string($thisFrame)) { // File path or URL or Binary source code

                if (file_exists($thisFrame) || filter_var($thisFrame, FILTER_VALIDATE_URL)) { // File path

                    $thisFrame = file_get_contents($thisFrame);
                }

                $resourceImg = imagecreatefromstring($thisFrame);

                ob_start();
                imagegif($resourceImg);
                $this->frameSources[] = ob_get_clean();

            } else { // Fail
                throw new InvalidFrameTypeException($this);
            }

            if (0 === $i) {
                $colour = imagecolortransparent($resourceImg);
            }

            if (!str_starts_with($this->frameSources[$i], 'GIF87a') && !str_starts_with($this->frameSources[$i], 'GIF89a')) {
                throw new SourceIsNotAGifImageException($i, $this);
            }

            for ($j = (13 + 3 * (2 << (ord($this->frameSources[$i] [10]) & 0x07))), $k = true; $k; $j++) {

                switch ($this->frameSources[$i] [$j]) {

                    case '!':

                        if ((substr($this->frameSources[$i], ($j + 3), 8)) === 'NETSCAPE') {
                            throw new FrameIsAlreadyAnimatedException($this, $i + 1);
                        }

                        break;

                    case ';':

                        $k = false;
                        break;
                }
            }

            unset($resourceImg);
        }

        if (isset($colour)) {

            $this->colour = $colour;

        } else {

            $red = $green = $blue = 0;
            $this->colour = ($red > -1 && $green > -1 && $blue > -1) ? ($red | ($green << 8) | ($blue << 16)) : -1;
        }

        $this->gifAddHeader();

        for ($i = 0, $iMax = count($this->frameSources); $i < $iMax; $i++) {
            $this->addGifFrames($i, $durations[$i]);
        }

        $this->gifAddFooter();

        return $this->gif;
    }

    /**
     * Add the header gif string in its source (old: GIFAddHeader)
     */
    private function gifAddHeader(): void
    {
        if (ord($this->frameSources[0] [10]) & 0x80) {

            $cmap = 3 * (2 << (ord($this->frameSources[0] [10]) & 0x07));

            $this->gif .= substr($this->frameSources[0], 6, 7);
            $this->gif .= substr($this->frameSources[0], 13, $cmap);
            $this->gif .= "!\377\13NETSCAPE2.0\3\1".$this->encodeAsciiToChar($this->loop)."\0";
        }
    }

    /**
     * Add the frame sources to the GIF string (old: GIFAddFrames)
     *
     * @param integer $i
     * @param integer $d
     * @throws Exception
     */
    private function addGifFrames(int $i, int $d): void
    {
        $Locals_str = 13 + 3 * (2 << (ord($this->frameSources[$i] [10]) & 0x07));

        $Locals_end = strlen($this->frameSources[$i]) - $Locals_str - 1;
        $Locals_tmp = substr($this->frameSources[$i], $Locals_str, $Locals_end);

        $Global_len = 2 << (ord($this->frameSources[0] [10]) & 0x07);
        $Locals_len = 2 << (ord($this->frameSources[$i] [10]) & 0x07);

        $Global_rgb = substr($this->frameSources[0], 13, 3 * (2 << (ord($this->frameSources[0] [10]) & 0x07)));
        $Locals_rgb = substr($this->frameSources[$i], 13, 3 * (2 << (ord($this->frameSources[$i] [10]) & 0x07)));

        $Locals_ext = "!\xF9\x04".chr(($this->dis << 2) + 0).chr(($d >> 0) & 0xFF).chr(($d >> 8) & 0xFF)."\x0\x0";

        if ($this->colour > -1 && ord($this->frameSources[$i] [10]) & 0x80) {

            for ($j = 0; $j < (2 << (ord($this->frameSources[$i] [10]) & 0x07)); $j++) {

                if (ord($Locals_rgb [3 * $j + 0]) === (($this->colour >> 16) & 0xFF) &&
                    ord($Locals_rgb [3 * $j + 1]) === (($this->colour >> 8) & 0xFF) &&
                    ord($Locals_rgb [3 * $j + 2]) === (($this->colour >> 0) & 0xFF)
                ) {
                    $Locals_ext = "!\xF9\x04".chr(($this->dis << 2) + 1).chr(($d >> 0) & 0xFF).chr(($d >> 8) & 0xFF).chr($j)."\x0";
                    break;
                }
            }
        }

        switch ($Locals_tmp [0]) {

            case '!':

                $Locals_img = substr($Locals_tmp, 8, 10);
                $Locals_tmp = substr($Locals_tmp, 18, strlen($Locals_tmp) - 18);

                break;

            case ',':

                $Locals_img = substr($Locals_tmp, 0, 10);
                $Locals_tmp = substr($Locals_tmp, 10, strlen($Locals_tmp) - 10);

                break;
        }

        if (!isset($Locals_img)) {
            throw new GeneralGifCreatorException($this, 'Locals_img is not set');
        }

        if (ord($this->frameSources[$i] [10]) & 0x80 && $this->imgBuilt) {

            if ($Global_len === $Locals_len) {

                if ($this->gifBlockCompare($Global_rgb, $Locals_rgb, $Global_len)) {

                    $this->gif .= $Locals_ext.$Locals_img.$Locals_tmp;

                } else {

                    $byte = ord($Locals_img [9]);
                    $byte |= 0x80;
                    $byte &= 0xF8;
                    $byte |= (ord($this->frameSources[0] [10]) & 0x07);
                    $Locals_img [9] = chr($byte);
                    $this->gif .= $Locals_ext.$Locals_img.$Locals_rgb.$Locals_tmp;
                }

            } else {

                $byte = ord($Locals_img [9]);
                $byte |= 0x80;
                $byte &= 0xF8;
                $byte |= (ord($this->frameSources[$i] [10]) & 0x07);
                $Locals_img [9] = chr($byte);
                $this->gif .= $Locals_ext.$Locals_img.$Locals_rgb.$Locals_tmp;
            }

        } else {

            $this->gif .= $Locals_ext.$Locals_img.$Locals_tmp;
        }

        $this->imgBuilt = true;
    }

    /**
     * Add the gif string footer char (old: GIFAddFooter)
     */
    private function gifAddFooter(): void
    {
        $this->gif .= ';';
    }

    /**
     * Compare two blocks and return the version (old: GIFBlockCompare)
     *
     * @param string $globalBlock
     * @param string $localBlock
     * @param integer $length
     *
     * @return integer
     */
    private function gifBlockCompare(string $globalBlock, string $localBlock, int $length): int
    {
        for ($i = 0; $i < $length; $i++) {

            if ($globalBlock [3 * $i + 0] !== $localBlock [3 * $i + 0] ||
                $globalBlock [3 * $i + 1] !== $localBlock [3 * $i + 1] ||
                $globalBlock [3 * $i + 2] !== $localBlock [3 * $i + 2]) {

                return 0;
            }
        }

        return 1;
    }

    /**
     * Encode an ASCII char into a string char (old: GIFWord)
     *
     * @param integer $char ASCII char
     *
     * @return string
     */
    private function encodeAsciiToChar(int $char): string
    {
        return (chr($char & 0xFF).chr(($char >> 8) & 0xFF));
    }

    /**
     * Get the final GIF image string (old: GetAnimation)
     */
    public function getGif(): string
    {
        return $this->gif;
    }

    public function getVersion(): string
    {
        return self::VERSION;
    }
}