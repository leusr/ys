<?php

/*
|--------------------------------------------------------------------------
| Routes
|--------------------------------------------------------------------------
|
| The routes are an associated array with an url pattern as key, and an action
| as value. The action can or can not contain a @ character. Without it, the action
| is only the controller (class) name, and the targeted method will be index().
|
| Whit @ part it's possible define the method name.
|
| Native parameter passing support is not impemented, but there are two
| handy function to this: getSegment($i) and getSegments(), with these
| it is possible to access url parts in the controllers.
|
| Wildcars:
|     :word       - A-Za-z, hypen (-) and underscore (_)
|     :num        - 0-9 and hypen (to support negative numbers and dashed date format)
|     ()          - Optional segment. e.g.: 'pages(/:num)'
|
*/

return [
        // 'home'                          => 'DefaultPageController',
        // '404'                           => 'ErrorPageController@notFound',
        'readme'                        => 'Page@readme',
        'user(/:num(/page-:word))'      => 'UserTest',
        'config/mode=:num(,type=:word)' => 'UserTest',
];