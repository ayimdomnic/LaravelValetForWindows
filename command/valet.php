#!/usr/bin/env php 

<?php

/**
 * load correct autoloader depending on install
 */

if (file_exists(__DIR__.'/../vendor/autoload.php')) {
	# code...
	require __DIR__.'/../../../autoload.php';
}else {
	require __DIR__.'/../../../autoload.php';
}

use Silly\Application;
use Illuminate\Container\Container;

// a new application

Container::setInstance(new Container);

$version = '0.8.0';

$app = new Application ('valet for WindowsOs', $version);
// The code is similar to the original valet app so enjoy
$app->command('install', function(){

	// its strictly caddy and php so much understandable
	Configuration::install();
	Caddy::install();

	output(PHP_EOL.'<info>You sir, has installed Vallet Sucessfully</info>');
})->descriptions('Now install all the valet services!');

// allow valet root acess (Allowing Node proxy to run password-less sudo)

$app->command('share', function(){
	$cmd = 'start cmd.exe @cmd /k "'.VALET_BIN_PATH.'ngrok http -host-header=rewrite blog.dev:80"';
	pclose(popen($cmd, "r"));
	$sharedUrl = Ngrok::currentTunnelUrl();
	exec ('echo'.$sharedUrl. '| clip');
	output(PHP_EOL.'<info> The Shared Url: '.$sharedUrl.'is in your clipboard!</info>')
})->descriptions('share!!!!!!');

// more awesome code
/**
 * Rescan Valet parked folders and update the hosts file (c:\Windows\System32\drivers\etc\hosts)
 */
$app->command('scan', function () {
    Host::scan();
    output(PHP_EOL.'<info>Valet domains updated!</info>');
})->descriptions('Rescan Valet parked folders');


/**
 * Get or set the domain currently being used by Valet.
 */
$app->command('domain [domain]', function ($domain = null) {
    if ($domain === null) {
        return info(Configuration::read()['domain']);
    }

//    DnsMasq::updateDomain(
//        $oldDomain = Configuration::read()['domain'], $domain = trim($domain, '.')
//    );
    $oldDomain = Configuration::read()['domain'];
    Configuration::updateKey('domain', $domain);
    Host::scan();

    Site::resecureForNewDomain($oldDomain, $domain);
//    PhpFpm::restart();
//    Caddy::restart();

    info('Your Valet domain has been updated to ['.$domain.'].');
})->descriptions('Get or set the domain used for Valet sites');

/**
 * Add the current working directory to the paths configuration.
 */
$app->command('park', function () {
    Configuration::addPath(getcwd());

    info("This directory has been added to Valet's paths.");
})->descriptions('Register the current working directory with Valet');

/**
 * Remove the current working directory to the paths configuration.
 */
$app->command('forget', function () {
    Configuration::removePath(getcwd());
    Host::scan();

    info("This directory has been removed from Valet's paths.");
})->descriptions('Remove the current working directory from Valet\'s list of paths');

/**
 * Register a symbolic link with Valet.
 */
$app->command('link [name]', function ($name) {
    $linkPath = Site::link(realpath(getcwd()), $name = $name ?: basename(getcwd()));

    info('A ['.$name.'] symbolic link has been created in ['.$linkPath.'].');
})->descriptions('Link the current working directory to Valet');

/**
 * Display all of the registered symbolic links.
 */
$app->command('links', function () {
    passthru('ls -la '.VALET_HOME_PATH.'/Sites');
})->descriptions('Display all of the registered Valet links');

/**
 * Unlink a link from the Valet links directory.
 */
$app->command('unlink [name]', function ($name) {
    Site::unlink($name = $name ?: basename(getcwd()));

    info('The ['.$name.'] symbolic link has been removed.');
})->descriptions('Remove the specified Valet link');

/**
 * Secure the given domain with a trusted TLS certificate.
 */
$app->command('secure [domain]', function ($domain = null) {

    $url = ($domain ?: Site::host(getcwd())).'.'.Configuration::read()['domain'];
    Site::secure($url);

    //PhpFpm::restart();

    //Caddy::restart();

    info('The ['.$url.'] site has been secured with a fresh TLS certificate.');
});

$app->command('unsecure [domain]', function ($domain = null) {
    $url = ($domain ?: Site::host(getcwd())).'.'.Configuration::read()['domain'];

    Site::unsecure($url);

//    PhpFpm::restart();

//    Caddy::restart();

    info('The ['.$url.'] site will now serve traffic over HTTP. Please, restart Caddy server.');
});

/**
 * Determine which Valet driver the current directory is using.
 */
$app->command('which', function () {
    require __DIR__.'/drivers/require.php';

    $driver = ValetDriver::assign(getcwd(), basename(getcwd()), '/');

    if ($driver) {
        info('This site is served by ['.get_class($driver).'].');
    } else {
        warning('Valet could not determine which driver to use for this site.');
    }
})->descriptions('Determine which Valet driver serves the current working directory');

/**
 * Stream all of the logs for all sites.
 */
$app->command('logs', function () {
    $files = Site::logs(Configuration::read()['paths']);

    $files = collect($files)->transform(function ($file) {
        return escapeshellarg($file);
    })->all();

    if (count($files) > 0) {
        passthru('tail -f '.implode(' ', $files));
    } else {
        warning('No log files were found.');
    }
})->descriptions('Stream all of the logs for all Laravel sites registered with Valet');

/**
 * Display all of the registered paths.
 */
$app->command('paths', function () {
    $paths = Configuration::read()['paths'];

    if (count($paths) > 0) {
        output(json_encode($paths, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    } else {
        info('No paths have been registered.');
    }
})->descriptions('Get all of the paths registered with Valet');

/**
 * Open the current directory in the browser.
 */
 $app->command('open', function () {
     $url = "http://".Site::host(getcwd()).'.'.Configuration::read()['domain'].'/';

     passthru("\"C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe\" ".escapeshellarg($url));
 })->descriptions('Open the site for the current directory in your browser');

/**
 * Echo the currently tunneled URL.
 */
$app->command('fetch-share-url', function () {
    output(Ngrok::currentTunnelUrl());
})->descriptions('Get the URL to the current Ngrok tunnel');

/**
 * Start the daemon services.
 */
$app->command('start', function () {

    $cmd = 'start "Valet" cmd.exe @cmd /k "cd '.VALET_BIN_PATH.'/../ && start.bat"';
    pclose(popen($cmd, "r"));
    //PhpFpm::restart();

    //Caddy::restart();

    info('Valet services have been started.');
})->descriptions('Start the Valet services');

/**
 * Restart the daemon services.
 */
$app->command('restart', function () {
    PhpFpm::restart();

    Caddy::restart();

    info('Valet services have been restarted.');
})->descriptions('Restart the Valet services');

/**
 * Stop the daemon services.
 */
$app->command('stop', function () {
    exec('taskkill /IM cmd.exe /FI "WINDOWTITLE eq Valet*"');
    exec('taskkill /IM cmd.exe /FI "WINDOWTITLE eq Administrator: Valet*"');
//    PhpFpm::stop();

//    Caddy::stop();

    info('Valet services have been stopped.');
})->descriptions('Stop the Valet services');

/**
 * Uninstall Valet entirely.
 */
$app->command('uninstall', function () {
    Caddy::uninstall();

    info('Valet has been uninstalled.');
})->descriptions('Uninstall the Valet services');

/**
 * Determine if this is the latest release of Valet.
 */
$app->command('on-latest-version', function () use ($version) {
    if (Valet::onLatestVersion($version)) {
        output('YES');
    } else {
        output('NO');
    }
})->descriptions('Determine if this is the latest version of Valet');

/**
 * Load all of the Valet extensions.
 */
foreach (Valet::extensions() as $extension) {
    include $extension;
}

/**
 * Run the application.
 */
$app->run();