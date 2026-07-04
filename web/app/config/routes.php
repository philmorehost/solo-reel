<?php

$router->get('/', 'HomeController@index');
$router->get('/movie/{slug}', 'SeriesController@detail');
$router->get('/episodes/{...slug}', 'EpisodeController@player');
$router->get('/shelf/{slug}', 'ShelfController@index');
$router->get('/search', 'SearchController@index');
$router->get('/blog', 'BlogController@index');
$router->get('/blog/{slug}', 'BlogController@detail');

$router->get('/login', 'AuthController@showLogin');
$router->get('/auth/google', 'GoogleAuthController@redirect');
$router->get('/auth/google/callback', 'GoogleAuthController@callback');
$router->post('/login', 'AuthController@login');
$router->get('/register', 'AuthController@showRegister');
$router->post('/register', 'AuthController@register');
$router->get('/verify-otp', 'AuthController@showVerifyOtp');
$router->post('/verify-otp', 'AuthController@verifyOtp');
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

// Admin Routes
$router->get('/admin', 'DashboardController@index');
$router->get('/admin/series', 'SeriesController@index');
$router->get('/admin/series/create', 'SeriesController@create');
$router->post('/admin/series/create', 'SeriesController@create');
$router->get('/admin/series/edit/{id}', 'SeriesController@edit');
$router->post('/admin/series/edit/{id}', 'SeriesController@edit');
$router->get('/admin/series/delete/{id}', 'SeriesController@delete');

$router->get('/admin/episodes', 'EpisodeController@index');
$router->get('/admin/episodes/create', 'EpisodeController@create');
$router->post('/admin/episodes/create', 'EpisodeController@create');
$router->get('/admin/episodes/edit/{id}', 'EpisodeController@edit');
$router->post('/admin/episodes/edit/{id}', 'EpisodeController@edit');
$router->get('/admin/episodes/delete/{id}', 'EpisodeController@delete');

$router->get('/admin/users', 'UserController@index');
$router->get('/admin/users/create', 'UserController@create');
$router->post('/admin/users/create', 'UserController@create');
$router->get('/admin/users/edit/{id}', 'UserController@edit');
$router->post('/admin/users/edit/{id}', 'UserController@edit');
$router->get('/admin/users/delete/{id}', 'UserController@delete');
$router->get('/admin/users/login-as/{id}', 'UserController@loginAs');
$router->get('/admin/users/suspend/{id}', 'UserController@suspend');
$router->get('/admin/security', 'SecurityController@index');
$router->post('/admin/security/add-ip', 'SecurityController@addIp');
$router->post('/admin/security/remove-ip', 'SecurityController@removeIp');
$router->get('/admin/coins', 'CoinController@index');
$router->get('/admin/coins/create', 'CoinController@create');
$router->post('/admin/coins/create', 'CoinController@create');
$router->get('/admin/coins/edit/{id}', 'CoinController@edit');
$router->post('/admin/coins/edit/{id}', 'CoinController@edit');
$router->get('/admin/coins/delete/{id}', 'CoinController@delete');
$router->get('/admin/banners', 'BannerController@index');
$router->get('/admin/banners/create', 'BannerController@create');
$router->post('/admin/banners/create', 'BannerController@create');
$router->get('/admin/banners/delete/{id}', 'BannerController@delete');
$router->get('/admin/shelves', 'ShelfController@index');
$router->get('/admin/genres', 'GenreController@index');
$router->get('/admin/blog', 'BlogController@index');
$router->get('/admin/blog/create', 'BlogController@create');
$router->post('/admin/blog/create', 'BlogController@create');
$router->get('/admin/blog/delete/{id}', 'BlogController@delete');
$router->get('/admin/blog-categories', 'BlogCategoryController@index');
$router->post('/admin/blog-categories/create', 'BlogCategoryController@create');
$router->post('/admin/genres/create', 'GenreController@create');
$router->get('/admin/genres/delete/{id}', 'GenreController@delete');
$router->get('/admin/shelves/create', 'ShelfController@create');
$router->post('/admin/shelves/create', 'ShelfController@create');
$router->get('/admin/shelves/edit/{id}', 'ShelfController@edit');
$router->post('/admin/shelves/edit/{id}', 'ShelfController@edit');
$router->get('/admin/shelves/delete/{id}', 'ShelfController@delete');
$router->get('/admin/reports', 'ReportController@index');
$router->get('/admin/settings', 'SettingsController@index');
$router->post('/admin/settings', 'SettingsController@index');
$router->get('/admin/settings/payments', 'PaymentSettingsController@index');
$router->post('/admin/settings/payments', 'PaymentSettingsController@index');

// API Routes
$router->post('/api/v1/auth/login', 'Api\AuthController@login');
$router->post('/api/v1/auth/register', 'Api\AuthController@register');
$router->get('/api/v1/series', 'Api\SeriesController@index');
$router->get('/api/v1/banners', 'Api\BannerController@index');

$router->get('/favicon.ico', 'FaviconController@index');
$router->get('/sitemap.xml', 'SeoController@sitemap');
$router->get('/llms.txt', 'SeoController@llms');
