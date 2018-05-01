# FlexPhpRouter

Simple and flexible router library for web and command line applications.

Packagist page: https://packagist.org/packages/tbolner/flex-php-router

## Installation

Include the library in the *composer.json* file of your project:

    {
        "require": {
            "tbolner/FlexPhpRouter": "dev-master"
        }
    }

Then execute:

    composer update

This library requires PHP 7.1 or newer.

## Usage example for web applications

Create a folder in your project, to contain the controllers.
For example:

    project1/src/Controller/

Then create the controllers. The file name has to match the first part
of the URI:

|Example URL|Controller path|
|---|---|
|GET http://host/items/12| project1/src/Controller/items.php|
|POST http://host/items/12/list| project1/src/Controller/items.php|
|PUT https://host/items/12/detailsasd| project1/src/Controller/items.php|
|DELETE http://host/test%25Ctr4/as%25d/66| project1/src/Controller/testCtr4.php|
|GET http://host/test+_Ctr%255/qwerty/66| project1/src/Controller/test_Ctr5.php|
|GET http://host/test-Ctr6/qwerty/66| project1/src/Controller/test-Ctr6.php|

As you see all special characters are removed from the first part of the
URI when looking up the corresponding PHP file. (For security
considerations.) To be precise: all characters are removed from the first
part, which are not: a-z, A-Z, 0-9, - (dash), _ (underline). (Other parts
of the URL are unaffected by this mechanism.)

The controller will match for patters to decide what to execute.
These matching parts are called "actions".

- project1/src/Controller/items.php

        <?php declare(strict_types=1);

        namespace MyProject\Controller;

        use FlexPhpRouter\Router;

        Router::get(
            "items/{id:int}",
            function (int $id) {
                /* ... your code ... */
                /* you can also throw exceptions to
                    catch them at your router start-up
                    code */
            }
        );

        Router::post(
            "items/{id:int}/list",
            function (int $id) {
                /* ... your code ... */
            }
        );

        Router::put(
            "items/{id:int}/details{other:string}",
            function (int $id, string $other) {
                /* ... your code ... */
            }
        );

Notes:
- In parameters in the path patterns can have 4 types:
    - **string**, **int**, **float**, **bool**
    - To have additional types, just use "string" and do the type
        conversion in the action.
- The slashes (/) at the endings are ignored. The patterns will match
    regardless of the ending slashes.
- To match for the site root, just match for "/" in the default
    controller. (See next point on how to specify the default controller.)
- Notice that in the 3rd action, the parameter follows the "/details"
    text without a slash separating them, which is an accepted solution.
- The supported methods are:
    - get, post, put, delete, any

Finally add the router start-up code to your application:

- project1/web/index.php

        <?php declare(strict_types=1);

        namespace Web;

        use FlexPhpRouter\Router;
        use MyProject\Exceptions\MySpecialException;

        require_once (dirname(__FILE__)."/../vendor/autoload.php");

        try {
            Router::route(dirname(__FILE__)."/../src/Controller", 'default');
        } catch (MySpecialException $ex) {
            /* ... */
        } catch (\Exception $ex) {
            /* ... */
        }

Notes:
- The first parameter of Router::route() defines the path to the
    controller directory. Use a way similar this the above to define
    the path in a relative way.
- The second parameter of Router::route() command expects the
    name of the default controller, which is called when no controller
    was found. You can handle there the site root for example. The
    name "default" will lead to the controller file "default.php".
- Handle all exceptions which you would throw in the controllers or
    in code invoked from the controllers.

## Usage example for CLI applications

Create a folder in your project, to contain the CLI controllers.
For example:

- project1/src/CLI

Then create the controllers. The file name has to match the first part
of the path passed as first parameter:

|Example console command|Controller path|
|---|---|
|console.php test/cleanup|project1/src/CLI/test.php|
|console.php test/run param1=55 param2="asd"|project1/src/CLI/test.php|
|console.php other/do/something|project1/src/CLI/other.php|

Example controller:

- project1/src/CLI/test.php:

        <?php declare(strict_types=1);

        namespace MyProject/CLI;

        use FlexPhpRouter\Router;
        use FlexPhpRouter\CliParameter;

        Router::cli("test/cleanup")
            ->matchRoute(function () {
                /* ... your code ... */
                /* you can also throw exceptions to
                    catch them at your router start-up
                    code */
            });

        Router::cli("test/run")
            ->addParameter("param1", CliParameter::TYPE_INT, true, "Description of first parameter.")
            ->addParameter("param2", CliParameter::TYPE_STRING, false, "Description of second parameter.")
            ->matchRoute(function (int $param1, string $param2 = null) {
                /* ... your code ... */
            });

In this case the router start-up code has to go into a not web related
PHP file (so it should be outside of the web folder). For example:

- project1/console.php:

        #!/usr/bin/php
        <?php declare(strict_types=1);

        namespace CLI;

        use FlexPhpRouter\Router;
        use MyProject\Exceptions\MySpecialException;

        require_once (dirname(__FILE__)."/vendor/autoload.php");

        try {
            Router::route(dirname(__FILE__)."/src/CLI", 'default');
        } catch (MySpecialException $ex) {
            /* ... */
        } catch (\Exception $ex) {
            /* ... */
        }

Notes:

- You don't need to tell the *Router::route()* method that it has to
    expect console parameters, because it will automatically detect
    that the script was run in console mode.

## Design considerations

- Usage is similar to the Laravel router.
- No library dependencies.
- No parsing and pre-built cache. See the next point which explains
    how high performance is achieved without using code pre-generation.
- Code locations for actions are not fully custom. Their controller is
    decided by the first part of the URL. This forces the developer to
    keep the code for actions tidy, and also increases the performance of
    the application, since the PHP file for a controller can be found
    in an explicit way. (See "Usage example for web applications" for
    more information.)
- Type safity. All parameters are casted to the specified types.
- It doesn't handle output. In most router libraries the action uses the
    **return** command to pass back data to be outputted (For example
    an array as a JSON). I found that this causes more problems on
    the long run than it solves. Better put the I/O functionality
    into a separate library, and call its methods from your controllers.
- It only supports an "any" method specifier, but doesn't support multiple
    (specific) methods (like POST+GET) per action,
    because those are so rarely required, that it doesn't worth messing
    up the API with an extra parameter for each action (Like in Laravel).
    When it's required, then put your action codes into static methods,
    and call them from the separate POST, GET, etc. actions.
