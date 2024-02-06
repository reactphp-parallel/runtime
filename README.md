# ReactPHP Runtime wrapper for ext-parallel

![Continuous Integration](https://github.com/Reactphp-parallel/runtime/workflows/Continuous%20Integration/badge.svg)
[![Latest Stable Version](https://poser.pugx.org/React-parallel/runtime/v/stable.png)](https://packagist.org/packages/React-parallel/runtime)
[![Total Downloads](https://poser.pugx.org/React-parallel/runtime/downloads.png)](https://packagist.org/packages/React-parallel/runtime)
[![Code Coverage](https://scrutinizer-ci.com/g/Reactphp-parallel/runtime/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Reactphp-parallel/runtime/?branch=master)
[![Type Coverage](https://shepherd.dev/github/Reactphp-parallel/runtime/coverage.svg)](https://shepherd.dev/github/Reactphp-parallel/runtime)
[![License](https://poser.pugx.org/React-parallel/runtime/license.png)](https://packagist.org/packages/React-parallel/runtime)

### Installation ###

To install via [Composer](http://getcomposer.org/), use the command below, it will automatically detect the latest version and bind it with `~`.

```
composer require react-parallel/runtime 
```

# Usage

```php
use ReactParallel\Runtime\Runtime;
use ReactParallel\EventLoop\EventLoopBridge;

$runtime = Runtime::create(new EventLoopBridge());

echo $runtime->run(function (): int {
    sleep(3);

    return 3;
}), PHP_EOL;
```

## Contributing ##

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License ##

Copyright 2019 [Cees-Jan Kiewiet](http://wyrihaximus.net/)

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.
