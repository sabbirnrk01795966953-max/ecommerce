<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('about-shop', function () {
    $this->info('Trendy Deal BD ecommerce project is ready.');
})->purpose('Show shop project status');
