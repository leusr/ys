# ys

This is my dead simple system for tests and everyday work. The name *ys* is abbreviation of *yocto system*, where *yocto* is the smallest named unit prefix in science.

- controller - view structure
- HTML compressor
- file-based config/setting system
- on-the-fly image resizer
- router

With some facades, that's all. 


## Install

1. Copy `ys` directory (or just it's contents) to the server. Php must have write permissions at least on `storege` dir.
 
2. Copy `index.php` and `.htaccess` to a public directory (domain root or subdirectory).

3. (subdirectory only) The `.htaccess` prepared for domain root install, so in subdirectory adjust RewriteBase param and 
   the two RewriteRule.
   
4. Edit `index.php`, set up directories, some environament constants and open the address in a browser. You'll have to see the home page via `SYSPATH/controller/Page.php` file's `home()` method.



## Functions

These functions are globals, you can use them in views as well in controllers.


### `system.php`

- `config(string: $key)` - Get config data.

    The config data located at `SYSPATH/config` in __php__ files and in associative arrays. 
    `key` must be dotted format, e.g.: `basic.group.someconf`. This will search in *basic.php* 
    file, and returns with the *someconf* value of *group* array.
    
    ```php
    config('general.siteinfo.name');  // Get value from general.php
    config('general.charset');
    ```

- `setting(string: $key, $data = null)` - Get/set setting data.

    Settings are read/write values, living at `SYSPATH/storage/settings` directory 
    in __json__ files. At saveing data it is allowed to have only a dotless single key 
    (as filename), and passing an array (or object) to save.

    ```php
    setting('comments.global.enabled');  // Get value from comments.json
    setting('comments.global.enabled', true);  // Set value
    setting('comments', ['global' => ['enabled' => true]]);  // Same as above with another syntax. 
                                                             // Make sense whit more values.
    ```

- `getcache(string: $name, int: $expire = 0)` - Get cache contents from `SYSPATH/storage/cache`.
- `putcache(string: $name, string: $content)` - Put cache contents to `SYSPATH/storage/cache`.

    
        
- `route(string: $route, string: $controller)` - Adds a new route.

    There's two default routes in the system: `home: Page` and `404: Error@notFound`. These refer to `index()` method (since this is the default when method not specified) in `ys\controller\Page` controller, and `notFound()` method in `ys\controller\Error`. These defaults can override, see examples in the code block below.
    
    All routes can contains the following wildcards:
    - __:word__       - A-Za-z, hypen (-) and underscore (_)
    - __:num__        - 0-9
    - __()__          - Optional segment. e.g.: `pages(/:num)`
    - __:segments__   - All :word and :num characters, plus slash (/). In outher words it 
                        takes it all. Must be the last route or the only route. 
        
    ```php
    route('home', 'Index@main');   // Override home route.
    route('404', 'Notfound');   // Override 404 route.
    route('category/:word', 'Category@list');
    route('user/:num(/edit)', 'User');
    route(':segments', 'BigBossController');
    ```
    
    
- `baseurl()` - Get baseurl
- `subdir()` - Get subdirectory relative to `$_SERVER['DOCUMENT_ROOT']`.
- `segment(int: $index)` - Get an url segment by its index (starting with zero).

    In yocto System automatic passing the parameters to controllers isn't implemented, 
    but with this function it's easy to access all segments of the requested url.

- `view()` - Create new view instance __with given arguments__.
 
    - __1st param__ - The view's path relative to `VIEWSPATH`. `VIEWSPATH` can be set 
    manually to anything, if it not set, yocto will search for  
    `SYSPATH/templates` or `SYSPATH/views` (in this exact order), if not found `SYSPATH`
    is the fallback default.
    - (...)
    
- `incview()`
    
    Include view in another view.
    


### `template.php`

- `e($value)` - Escape HTML special chars.

- `ent($value)` - Escape all weird characters to HTML entities.

- `img(string: $path)` - Resize image and/or get resized image url.

    - __$path__ - (string) The path of the original image.
                  Relative from base path defined in Image class,
                  or may set by constructor or setBasePath setter.

    - __Additional params__
    
        - 1: (string|int|null) A predefined named size, or the (max) width value.
        - 2: (int|null) The (max) height value.
        - 3: (bool) The crop flag.

    - __Some examples__
    
        - `img('some.jpg', 'thumb');` - Resize to predefined `thumb` size.
        - `img('some.jpg', 400, 300);` - Original ratio within max-width=400 and max-height=300.
        - `img('some.jpg', 400, 300, true);` - Exact 400x300.
        - `img('some.jpg', null, 400);` - Orginal ratio, width no matter, height=400.

    - __return__ (string) The url of resized or original image

- `show(string: $path)` - The same as above, but instead of saving image send it to http output.

- `svg($name)` - Include an svg icon from configured path
                 or fallback default to `ROOTPATH/assets/icons`
                 
- `svguse(string: $id, string: $url = "")` - Insert svg use xlink:href tag

- `blank()` - 1x1 blank.gif base64
  


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
