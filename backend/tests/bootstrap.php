<?php

// Composerが提供するテストライブラリを読み込みます。
require dirname(__DIR__).'/fuel/vendor/autoload.php';
// FuelPHPに依存しないServiceと契約だけを単体テストへ読み込みます。
require dirname(__DIR__).'/fuel/app/classes/repository/healthstatusrepositoryinterface.php';
require dirname(__DIR__).'/fuel/app/classes/service/healthservice.php';

