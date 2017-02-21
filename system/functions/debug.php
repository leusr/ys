<?php

if ( ! function_exists('pr')) {
    /**
     * Formatted print_r()
     * @param mixed $var  The variable which we are interested.
     * @param mixed $exit (optional) Exit or not, with exitcode, error message or nothing.
     */
    function pr($var, $exit = false) {
        ob_start();
        print_r($var);
        debugWrapPre(ob_get_clean(), '#22d');
        debugTerminateScript($exit);
    }
}

if ( ! function_exists('vd')) {
    /**
     * Formatted var_dump()
     * @param mixed $var
     * @param mixed $exit
     */
    function vd($var, $exit = false) {
        ob_start();
        var_dump($var);
        debugWrapPre(ob_get_clean(), '#d22');
        debugTerminateScript($exit);
    }
}

if ( ! function_exists('prc')) {
    /**
     * Comment print_r()
     * @param mixed $var
     * @param mixed $exit
     */
    function prc($var, $exit = false) {
        ob_start();
        print_r($var);
        debugWrapComment(ob_get_clean());
        debugTerminateScript($exit);
    }
}

if ( ! function_exists('vdc')) {
    /**
     * Comment var_dump()
     * @param mixed $var
     * @param mixed $exit
     */
    function vdc($var, $exit = false) {
        ob_start();
        var_dump($var);
        debugWrapComment(ob_get_clean());
        debugTerminateScript($exit);
    }
}

/**
 * Wrap output to stylish <pre> tag.
 * @param string $output The output what we are interested.
 * @param string $color  (optional) The css color property.
 */
function debugWrapPre($output, $color = '#22d') {
    echo <<<HERE

<pre style="position: relative;
			z-index: 9000;
			background-color: rgba(255,255,255,.9);
			border: 1px solid rgba(0,0,0,.1);
            box-shadow: 0 3px 0 rgba(0,0,0,.1);
            -webkit-border-radius: 5px;
                    border-radius: 5px;
            color: {$color};
            font: 13px/1.4 Consolas, monospace;
            margin: 20px;
            padding: 10px;
            white-space: pre-wrap;">{$output}</pre>

HERE;
}

/**
 * Wrap output to html comment.
 * @param string $output
 */
function debugWrapComment($output) {
    echo "\n\n<!--\n{$output}\n-->\n\n";
}

/**
 * Das Terminator function.
 * @param mixed $exitcode
 */
function debugTerminateScript($exitcode) {
    if (false === $exitcode) {
        return;
    }
    true === $exitcode && exit;
    exit($exitcode);
}
