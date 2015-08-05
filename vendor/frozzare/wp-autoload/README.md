# WordPress Autoload

[![Build Status](https://travis-ci.org/frozzare/wp-autoload.svg?branch=master)](https://travis-ci.org/frozzare/wp-autoload)

Simple autoloader that will autoload classes or traits with namespace prefix

# Install

```
$ composer require frozzare/wp-autoload
```

## Example

Example of your main php file.

```php
require 'vendor/autoload.php';
register_wp_autoload('Digster\\', __DIR__ . '/src');
```

Example of `src/class-plugin-loader.php`

```php
namespace Digster;
class Plugin_Loader {}
```

Example of `src/trait-crawler.php`

```php
namespace Digster;
trait Crawler {}
```

## License

MIT © [Fredrik Forsmo](https://github.com/frozzare)
