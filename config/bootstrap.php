<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
if (is_file(dirname(__DIR__).'/.env')) { (new Symfony\Component\Dotenv\Dotenv())->bootEnv(dirname(__DIR__).'/.env'); }
