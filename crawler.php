<?php
use classes\Crawler;


const ROOT = __DIR__;
include __DIR__ . "/classes/Autoload.php";

Crawler::addAlertCase('/error:/im');                              //Error in page
Crawler::addAlertCase('/^([4-9]\d{2,})$/im', Crawler::STATUS);    //status above 400

// Start the crawl at a given URL
Crawler::addUrl($argv[1] ?? null);
Crawler::start();