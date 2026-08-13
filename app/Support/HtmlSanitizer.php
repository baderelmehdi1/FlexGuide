<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

class HtmlSanitizer
{
    /**
     * Server-side allow-list pass over step body HTML. The client already
     * sends normalized semantic markup (see resources/js/lib/quillSerializer.js),
     * but client HTML is never trusted -- this is the actual security boundary.
     */
    public static function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,strong,em,u,s,ul,ol,li,blockquote,code,h2,h3,a[href]');
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('Cache.DefinitionImpl', null);

        $clean = (new HTMLPurifier($config))->purify($html);

        return trim($clean) === '' ? null : $clean;
    }
}
