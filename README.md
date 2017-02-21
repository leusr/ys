# ys

This is my dead simple system for tests and everyday work. The name *ys* is abbreviation of *yocto system*, where *yocto* is the smallest named unit prefix in science.

- controller - view structure
- HTML compressor
- file-based config/setting system
- on-the-fly image resizer
- router

With some facades, that's all. 


## Install

1. Upload codes to an Apache webserver, where `public` directory must be your document root.



## Functions

These functions are globals, you can use them in views as well in controllers.


### `system.php`

####config(string: $key)

Get config data.

The config data located at `SYSPATH/config` in __php__ files and in associative arrays. 
`key` must be dotted format, e.g.: `basic.group.someconf`. This will search in *basic.php* 
file, and returns with the *someconf* value of *group* array.

```php
config('general.siteinfo.name');  // Get value from general.php
config('general.charset');
```

####setting(string: $key, $data = null)

Get/set setting data.

Settings are read/write values, living at `SYSPATH/storage/settings` directory 
in __json__ files. At saveing data it is allowed to have only a dotless single key 
(as filename), and passing an array (or object) to save.

```php
setting('comments.global.enabled');  // Get value from comments.json
setting('comments.global.enabled', true);  // Set value
setting('comments', ['global' => ['enabled' => true]]);  // Same as above with another syntax. 
                                                         // Make sense whit more values.
```

####getcache(string: $name, int: $expire = 0)

Get cache contents from `SYSPATH/storage/cache`.

####putcache(string: $name, string: $content)

Put cache contents to `SYSPATH/storage/cache`.

####route(string: $route, string: $controller)

Adds a new route.

There's two default routes in the system: `home: DefaultPageController` and `404: ErrorPageController@notFound`. 
The first refers to `index()` method, since this is the default when method not specified, located at 
`ys\controller\DefaultPageController` controller. The second looking for `notFound()` in 
`ys\controller\ErrorPageController`. These defaults can override, see examples in the code block below.

All routes can contains the following wildcards:

- __:word__       - A-Za-z, hypen (-) and underscore (_)
- __:num__        - 0-9
- __()__          - Optional segment. e.g.: `pages(/:num)`

If a route with wildcards match, the values will be available in the controller as method parameters.

```php
route('home', 'Index@main');   // Override home route.
route('404', 'Notfound');   // Override 404 route.
route('category/:word(/page-:num)', 'Category@list');  // function list($category_slug, $pagenum = 1) {}
route('user/:num(/edit)', 'User');
```


####baseurl()

Get baseurl

####subdir()

Get subdirectory relative to `$_SERVER['DOCUMENT_ROOT']`.

####segment(int: $index)

Get an url segment by its index (starting with zero).

####view(string: $view, $data = null)

Create new view instance with given arguments.

- `$view`: The view's path relative to `VIEWSPATH`.
- `$data`: array or string, variables to the view to display them.

####incview()

Include view in another view.



### `template.php`

####e($value)

Escape HTML special chars.

####ent($value)

Escape all weird characters to HTML entities.

####img(string: $path)

Resize image and/or get resized image url.

__$path__ - (string) The path of the original image.
          Relative from base path defined in Image class,
          or may set by constructor or setBasePath setter.

__Additional params__

1. (string|int|null) A predefined named size, or the (max) width value.
2. (int|null) The (max) height value.
3. (bool) The crop flag.

__Some examples__

```php
img('some.jpg', 'thumb');  // Resize to predefined 'thumb' size.
img('some.jpg', 400, 300);  // Original ratio within max-width=400 and max-height=300.
img('some.jpg', 400, 300, true);  // Exact 400x300.
img('some.jpg', null, 400);  // Orginal ratio, width no matter, height=400.
```

__return__ (string) The url of resized or original image.

####show(string: $path)

The same as above, but instead of saving image send it to http output.

####svg(string: $name)

Include an svg icon from configured path or fallback default to `ROOTPATH/assets/icons`.
         
####svguse(string: $id, string: $url = "")

Insert svg use xlink:href tag.

####blank()

1x1 blank.gif base64.



### `helpers.php`

- `isDev()`

- `isProd()`

- `isExternal(string: $url)`

- `isAbsolute(string: $url)`

- `redirect(string: $location, $status = 302)`

- `slug(string: $str)`

- `formatBytes(int: $size)`

- `formatSeconds(float: $sec)`

- `shash(string: $data, string: $encdata = null)` - Secure hash.



### `debug.php`

- `pr($var, $exit = false)`

- `vd($var, $exit = false)`

- `prc($var, $exit = false)`

- `vdc($var, $exit = false)` 
