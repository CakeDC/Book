CakeDC/Book plugin for CakePHP
==============================

cakephp-book allows you to search in the official CakePHP documentation directly from the console.

Requirements
------------

- CakePHP 3.5+
- PHP 7.2+

Installation
------------

You can install this plugin into your CakePHP application using [composer](https://getcomposer.org).

The recommended way to install composer packages is:

```
composer require cakedc/cakephp-book
```

Configuration
-------------

You can load the plugin using the shell command:

```bash
bin/cake plugin load CakeDC/Book
```

Or you can manually add the loading statement in the `src/Application.php` file of your application:

```php
public function bootstrap() {
    parent::bootstrap();
    $this->addPlugin('CakeDC/Book');
}
```

Basic Usage
-----------

```bash
bin/cake book <parameter>
```

parameter = parameter to look for in the documentation

Examples:
```
bin/cake book Text
bin/cake book "Virtual Fields"
```

Support
-------

For bugs and feature requests, please use the [issues](https://github.com/CakeDC/Book/issues) section of this repository.

Commercial support is also available, [contact us](https://www.cakedc.com/contact) for more information.

Contributing
------------

This repository follows the [CakeDC Plugin Standard](https://www.cakedc.com/plugin-standard). 
If you'd like to contribute new features, enhancements or bug fixes to the plugin, please read our [Contribution Guidelines](https://www.cakedc.com/contribution-guidelines) for detailed instructions.

License
-------

Copyright 2020 - 2022 Cake Development Corporation (CakeDC). All rights reserved.

Licensed under the [MIT](http://www.opensource.org/licenses/mit-license.php) License. Redistributions of the source code included in this repository must retain the copyright notice found in each file.
