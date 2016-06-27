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