<?php namespace ys\main;

/*
|--------------------------------------------------------------------------
| CompressHTML
|--------------------------------------------------------------------------
|
| This is a heavy regex-based removal of whitespace, unnecessary comments
| and tokens. IE conditional comments are preserved.
|
| Based on Minify project's Minify_HTML class.
|
| Modifications:
|
|  - option to disable new lines after first attribute
|  - preserve whitespace around textarea
|  - do not keep whitespace around script tags
|
|
| @author Stephen Clay <steve@mrclay.org>
|
*/

class CompressHTML {

    protected $useNewLines = true;     // Use newlines before 1st attribute in open tags (to limit line lengths)?
    protected $isXhtml = false;        // Should content be treated as XHTML1.0?
    protected $jsCleanComments = true; // Remove HTML comments beginning and end of SCRIPT block?
    protected $jsMinifier = null;      // Callback function to process content of SCRIPT elements.
    protected $cssMinifier = null;     // Callback function to process content of STLYE elements.

    // Inner working variables
    protected $replacementHash = null;
    protected $placeholders = [];
    private $html;

    /**
     * Minify HTML
     *
     * @param string $html
     * @param array  $options
     *
     * @return string
     */
    public static function minify($html, $options = []) {
        $min = new self($html, $options);

        return $min->process();
    }

    /**
     * Create a minifier object
     *
     * @param string $html
     * @param array  $options
     */
    public function __construct($html, $options = []) {
        $this->html = str_replace("\r\n", "\n", trim($html));

        if (isset($options['useNewLines'])) {
            $this->useNewLines = (bool)$options['useNewLines'];
        }
        if (isset($options['isXhtml'])) {
            $this->isXhtml = (bool)$options['isXhtml'];
        }
        if (isset($options['jsCleanComments'])) {
            $this->jsCleanComments = (bool)$options['jsCleanComments'];
        }
        if (isset($options['jsMinifier'])) {
            $this->jsMinifier = $options['jsMinifier'];
        }
        if (isset($options['cssMinifier'])) {
            $this->cssMinifier = $options['cssMinifier'];
        }
    }

    /**
     * Minify the markeup given in the constructor
     *
     * @return string
     */
    public function process() {
        $this->replacementHash = 'MINIFYHTML' . md5($_SERVER['REQUEST_TIME']);
        $this->placeholders = [];

        // replace SCRIPTs (and minify) with placeholders
        $this->html = preg_replace_callback('/\\s*<script(\\b[^>]*?>)([\\s\\S]*?)<\\/script>\\s*/i',
                                            [$this, 'removeScriptCB'], $this->html);

        // replace STYLEs (and minify) with placeholders
        $this->html = preg_replace_callback('/\\s*<style(\\b[^>]*>)([\\s\\S]*?)<\\/style>\\s*/i',
                                            [$this, 'removeStyleCB'], $this->html);

        // remove HTML comments (not containing IE conditional comments).
        $this->html = preg_replace_callback('/<!--([\\s\\S]*?)-->/', [$this, 'commentCB'], $this->html);

        // replace PREs with placeholders
        $this->html = preg_replace_callback('/\\s*<pre(\\b[^>]*?>[\\s\\S]*?<\\/pre>)\\s*/i', [$this, 'removePreCB'],
                                            $this->html);

        // replace TEXTAREAs with placeholders
        $this->html = preg_replace_callback('/(\\s*)<textarea(\\b[^>]*?>[\\s\\S]*?<\\/textarea>)(\\s*)/i',
                                            [$this, 'removeTextareaCB'], $this->html);

        // trim each line.
        // @TODO take into account attribute values that span multiple lines.
        $this->html = preg_replace('/^\\s+|\\s+$/m', '', $this->html);

        // remove ws around block/undisplayed elements
        $this->html = preg_replace('/\\s+(<\\/?(?:area|article|aside|base(?:font)?|blockquote|body'
                                   . '|canvas|caption|center|col(?:group)?|dd|dir|div|dl|dt|fieldset|figcaption|figure|footer|form'
                                   . '|frame(?:set)?|h[1-6]|head|header|hgroup|hr|html|legend|li|link|main|map|menu|meta|nav'
                                   . '|ol|opt(?:group|ion)|output|p|param|section|t(?:able|body|head|d|h||r|foot|itle)'
                                   . '|ul|video)\\b[^>]*>)/i', '$1', $this->html);

        // remove ws outside of all elements
        $this->html = preg_replace('/>(\\s(?:\\s*))?([^<]+)(\\s(?:\s*))?</', '>$1$2$3<', $this->html);

        // use newlines
        if ($this->useNewLines) {
            $this->html = preg_replace('/(<[a-z\\-]+)\\s+([^>]+>)/i', "$1\n$2", $this->html);
        }

        // fill placeholders
        $this->html = str_replace(array_keys($this->placeholders), array_values($this->placeholders), $this->html);
        // issue 229: multi-pass to catch scripts that didn't get replaced in textareas
        $this->html = str_replace(array_keys($this->placeholders), array_values($this->placeholders), $this->html);

        // One new line to the end is acceptable. (except for html end)
        if (substr($this->html, -7) !== '</html>') {
            $this->html .= "\n";
        }

        return $this->html;
    }

    protected function commentCB($m) {
        return (0 === strpos($m[1], '[') || false !== strpos($m[1], '<![')) ? $m[0] : '';
    }

    protected function reservePlace($content) {
        $placeholder = '%' . $this->replacementHash . count($this->placeholders) . '%';
        $this->placeholders[$placeholder] = $content;

        return $placeholder;
    }

    protected function removePreCB($m) {
        return $this->reservePlace("<pre{$m[1]}");
    }

    protected function removeTextareaCB($m) {
        // whitespace surrounding? preserve at least one space
        $ws1 = ($m[1] === '') ? '' : ' ';
        $ws2 = ($m[3] === '') ? '' : ' ';

        return $this->reservePlace("{$ws1}<textarea{$m[2]}{$ws2}");
    }

    protected function removeStyleCB($m) {
        $openStyle = "<style{$m[1]}";
        $css = $m[2];
        // remove HTML comments
        $css = preg_replace('/(?:^\\s*<!--|-->\\s*$)/', '', $css);

        // remove CDATA section markers
        $css = $this->removeCdata($css);

        // minify
        $minifier = $this->cssMinifier ? $this->cssMinifier : 'trim';
        $css = call_user_func($minifier, $css);

        return $this->reservePlace($this->needsCdata($css) ? "{$openStyle}/*<![CDATA[*/{$css}/*]]>*/</style>"
                                           : "{$openStyle}{$css}</style>");
    }

    protected function removeScriptCB($m) {
        $openScript = "<script{$m[1]}";
        $js = $m[2];

        // remove HTML comments (and ending "//" if present)
        if ($this->jsCleanComments) {
            $js = preg_replace('/(?:^\\s*<!--\\s*|\\s*(?:\\/\\/)?\\s*-->\\s*$)/', '', $js);
        }

        // remove CDATA section markers
        $js = $this->removeCdata($js);

        // minify
        $minifier = $this->jsMinifier ? $this->jsMinifier : 'trim';
        $js = call_user_func($minifier, $js);

        return $this->reservePlace($this->needsCdata($js) ? "{$openScript}/*<![CDATA[*/{$js}/*]]>*/</script>"
                                           : "{$openScript}{$js}</script>");
    }

    protected function removeCdata($str) {
        return (false !== strpos($str, '<![CDATA[')) ? str_replace(['<![CDATA[', ']]>'], '', $str) : $str;
    }

    protected function needsCdata($str) {
        return ($this->isXhtml && preg_match('/(?:[<&]|\\-\\-|\\]\\]>)/', $str));
    }
}
