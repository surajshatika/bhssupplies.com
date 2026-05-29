<?php

// The MyFatoorah package registers routes to this App namespace controller
// but keeps the implementation in vendor until it is published. This shim
// makes route:list, route:cache, and package routes resolve cleanly.
require_once dirname(__DIR__, 3) . '/vendor/myfatoorah/laravel-package/src/controllers/MyFatoorahController.php';
