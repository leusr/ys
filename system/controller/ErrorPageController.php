<?php namespace ys\controller;

class ErrorPageController {
    /**
     * 400
     * @param string $message
     */
    public function badRequest($message = "") {
        http_response_code(400);
        $this->header('400 - Bad Request');
        view('error.400', compact('message'))->render();
        $this->footer();
    }

    /**
     * 403
     */
    public function forbidden() {
        http_response_code(403);
        $this->header('403 - Forbidden');
        view('error.403')->render();
        $this->footer();
    }

    /**
     * 404
     */
    public function notFound() {
        http_response_code(404);
        $this->header('404 - Not Found');
        view('error.404')->render();
        $this->footer();
    }

    /**
     * 503
     * @param string $message
     */
    public function serviceUnavailable($message = "") {
        http_response_code(503);
        $this->header('503 - Service Unavailable');
        view('error.503', compact('message'))->render();
        $this->footer();
    }

    /**
     * @param string $title Alternative string to <title> tag
     *                      (default: only the name of the site)
     */
    protected function header($title = "") {
        $title = empty($title) ? config('general.sitename') : $title;
        view('error.header', compact('title'))->render();
    }

    protected function footer() {
        view('error.footer')->render();
        exit;
    }
}
