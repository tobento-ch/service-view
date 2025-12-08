<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\Service\View;

use Tobento\Service\Support\Htmlable;

class Str
{    
    /**
     * Escapes string with htmlspecialchars.
     * 
     * @param mixed $string
     * @param int $flags
     * @param string $encoding
     * @param bool $double_encode
     * @return string
     */
    public static function esc(
        mixed $string,
        int $flags = ENT_QUOTES,
        string $encoding = 'UTF-8',
        bool $double_encode = true
    ): string {
        if ($string instanceof Htmlable) {
            return $string->toHtml();
        }
        
        return htmlspecialchars((string)$string, $flags, $encoding, $double_encode);
    }
}