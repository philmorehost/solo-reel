<?php

$router->get('/', 'HomeController@index');
$router->get('/movie/{slug}', 'SeriesController@detail');
$router->get('/episodes/{...slug}', 'EpisodeController@player');
$router->get('/shelf/{slug}', 'ShelfController@index');
$router->get('/search', 'SearchController@index');

$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/register', 'AuthController@showRegister');
$router->post('/register', 'AuthController@register');
$router->get('/logout', 'AuthController@logout');

$router->get('/profile', 'UserController@profile');
$router->get('/watch-history', 'UserController@watchHistory');
$router->get('/favorites', 'UserController@favorites');
$router->post('/favorites/{seriesId}', 'UserController@addFavorite');
$router->delete('/favorites/{seriesId}', 'UserController@removeFavorite');

$router->get('/coin-shop', 'CoinController@shop');
$router->post('/unlock/{episodeId}', 'CoinController@unlock');
$router->post('/coins/purchase', 'CoinController@purchase');
$router->get('/payment/verify', 'PaymentController@verify');
$router->post('/payment/webhook', 'PaymentController@webhook');

// Admin Routes (Prefix handled logic in controllers or a routing group if implemented)
$router->get('/admin', 'DashboardController@index');
$router->get('/admin/series', 'SeriesController@index');
$router->get('/admin/episodes', 'EpisodeController@index');
$router->get('/admin/users', 'UserController@index');
$router->get('/admin/settings', 'SettingsController@index');

// API Routes
$router->post('/api/v1/auth/login', 'Api\AuthController@login');
$router->post('/api/v1/auth/register', 'Api\AuthController@register');
$router->get('/api/v1/series', 'Api\SeriesController@index');
$router->get('/api/v1/banners', 'Api\BannerController@index');

$router->get('/favicon.ico', 'FaviconController@index');
