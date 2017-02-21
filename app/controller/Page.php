<?php namespace app\controller;


class Page extends \ys\controller\DefaultPageController {

    public function readme() {
        $this->header('Read me', 'readme');

        if (false === $docpath = realpath(SYSDIR . '/../README.md')) {
            error404();
        }

        require_once SYSDIR . '/vendor/Parsedown.php';
        $Parsedown = new \Parsedown();
        $content = $Parsedown->text(file_get_contents($docpath));
        echo $content;

        $this->footer();
    }

}
