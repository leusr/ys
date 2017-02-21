<?php namespace ys\controller;

class DefaultPageController {
    protected $data = [];

    public function index() {
        $this->header('Kezdőlap', 'home');
        view('page.home')->render();
        $this->footer();
    }

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

    protected function header($pagetitle = "", $body_class = 'default') {
        // $document_title
        if (empty($pagetitle)) {
            $tagline = config('general.tagline');
            $title = empty($tagline) ? config('general.sitename')
                    : $tagline . config('general.titlesep') . config('general.sitename');
        } else {
            $title = $pagetitle . config('general.titlesep') . config('general.sitename');
        }
        $this->data['document_title'] = $title;

        // $body_class
        $this->data['body_class'] = $body_class;

        view('page.header', $this->data)->render();
    }

    protected function footer() {
        view('page.footer', $this->data)->render();
    }
}
