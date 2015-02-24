Aya
===
A simple php framework to help you build small application.

Features
--------
* Use simple folder structure to layout your app.
* A routing service.
* A session service.
* A database service.
* A template service.

Quick start
-----------
You need 2 folders to work with Aya. Firstly you need an app folder, which should contains all code of your application. Then you need a routing base folder, which should contain all static resources.

Your app folder should look like:

	app/
		cls/
		template/
		config.php
		const.php
		helper.php
		start.php

* Put class definition into `cls` folder. Aya auto loads classes from it.
* Put templates into `template` folder. You can use `Template::get('file_name.php')` to get them.
* `config.php` should contains all config definition including Aya's settings and your's. Use `Config::get('key')` to get them.
* Put constant definition into `const.php`.
* Put helper functions into `helper.php`.
* `start.php` should contains all routing definitions. Aya will include `start.php` when you call `Aya::start()`.


Your base folder should contains 2 files:

index.php

	include('path/to/aya/aya.php');

	Aya::start('path/to/your/app/folder');

Routing service will do routing stuff relative with this file.

.htaccess

	AddDefaultCharset UTF-8
	Options +FollowSymLinks
	RewriteEngine On

	RewriteCond %{REQUEST_FILENAME} !-d
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteRule ^ index.php [L]

To use .htaccess rewrite rule you need Apache server.


Now we can add some code. Open `start.php`, add:

	<?php

	Route::add('/', function(){
		return 'Hello world!';
	});

Then open your browser, nagitive to `http://localhost/path/to/your/base/folder/`, and checkout how Aya works.
