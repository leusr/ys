<?php namespace app\controller;

class UserTest extends \ys\controller\DefaultPageController {
    public function index($id = null) {
        $this->header('Parameter passing test');
        if (isset($id)) {
            echo "<p>Az id a következő: <b>$id</b></p>";
        } else {
            echo "<p>Nincs id.</p>";
        }
        $this->footer();
    }
}