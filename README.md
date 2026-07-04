# SOLOREEL — Implementation Plan & Architecture

## Project Overview

SOLOREEL is a vertical short-drama streaming platform with a PHP frontend (web), Go/Node.js backend API, Android app, and iOS app. Users browse series, watch episodes, purchase coins, and interact with content. Admins manage content, users, security, and site configuration via a dedicated admin panel.

---

## Table of Contents

1. [Technology Stack](#1-technology-stack)
2. [Directory Structure](#2-directory-structure)
3. [Database Schema](#3-database-schema)
4. [Routing System](#4-routing-system)
5. [Authentication & Authorization](#5-authentication--authorization)
6. [Content Management](#6-content-management)
7. [Payment Integration (Payhub)](#7-payment-integration-payhub)
8. [Coin Economy](#8-coin-economy)
9. [SEO & Performance](#9-seo--performance)
10. [Android App Architecture](#10-android-app-architecture)
11. [iOS App Architecture](#11-ios-app-architecture)
12. [Mobile App Authentication Flow](#12-mobile-app-authentication-flow)
13. [Mobile Video Player](#13-mobile-video-player)
14. [Mobile Payment Flow](#14-mobile-payment-flow)
15. [Mobile Build & Release](#15-mobile-build--release)
16. [Mobile App Icons & Assets](#16-mobile-app-icons--assets)
17. [Site Branding](#17-site-branding)
18. [User Management (Admin)](#18-user-management-admin)
19. [Security](#19-security)
20. [Email System](#20-email-system)
21. [Installation Wizard](#21-installation-wizard)
22. [Responsive Design](#22-responsive-design)
23. [Admin Panel Features Summary](#23-admin-panel-features-summary)
24. [Backend API Specification](#24-backend-api-specification)
25. [Deployment Checklist](#25-deployment-checklist)
26. [File Dependency Map](#26-file-dependency-map)
27. [Known Limitations & Future Roadmap](#27-known-limitations--future-roadmap)

---

## 1. Technology Stack

| Layer | Technology |
|-------|-----------|
| **Web Frontend** | PHP 8.x (no framework), Tailwind CSS (CDN), Alpine.js, HTMX |
| **Backend API** | Go / Node.js microservice (REST JSON API) |
| **Android App** | Kotlin, Jetpack Compose, Kotlin Coroutines, Retrofit, ExoPlayer |
| **iOS App** | Swift, SwiftUI, Combine, Alamofire, AVPlayer |
| **Database** | MySQL / MariaDB (PDO) |
| **Caching** | File-based + optional Redis |
| **Payment Gateway** | Payhub (Nigerian payment processor — virtual accounts, checkout) |
| **Email** | PHPMailer (SMTP) |
| **Authentication** | Session-based (web) + JWT Bearer Token (mobile) with Argon2ID password hashing |
| **Video Streaming** | HLS (.m3u8) via CDN, ExoPlayer (Android) / AVPlayer (iOS) |
| **SEO** | JSON-LD Schema, sitemap.xml, robots.txt, llms.txt |
| **Hosting** | cPanel shared hosting (Apache, PHP-FPM) for web; API on VPS/cloud

---

## 2. Directory Structure

```
SOLOREEL/
├── web/                          # PHP Frontend (public_html)
│   ├── index.php                 # Entry point, bootstrapping, routing
│   ├── .htaccess                 # URL rewriting, security rules
│   ├── .env                      # Environment configuration
│   ├── app/
│   │   ├── config/
│   │   │   └── routes.php        # All route definitions
│   │   ├── controllers/
│   │   │   ├── Controllers.php   # Frontend controllers (Home, Series, Episode, Auth, etc.)
│   │   │   ├── UserCoinController.php  # Profile, favorites, coins, payment
│   │   │   └── PaymentController.php   # Payment verify + webhook
│   │   ├── core/
│   │   │   ├── Router.php        # Request routing + dispatch
│   │   │   ├── Database.php      # PDO wrapper (MySQL)
│   │   │   ├── Auth.php          # Login, register, logout, requireLogin
│   │   │   ├── Session.php       # Session management (file/Redis)
│   │   │   ├── Cache.php         # File/Redis caching
│   │   │   ├── ApiClient.php     # HTTP client to backend API (in Validator.php)
│   │   │   ├── Validator.php     # Input validation + ApiClient
│   │   │   ├── Canonical.php     # URL canonicalizer + SchemaBuilder + SitemapGenerator + AIVisibility
│   │   │   ├── PayhubGateway.php # Payhub payment integration
│   │   │   ├── Security.php      # CSRF, XSS protection
│   │   │   ├── Bruteforce.php    # Brute force protection
│   │   │   └── Mailer.php        # PHPMailer wrapper
│   │   ├── helpers/
│   │   │   ├── sanitize.php      # h(), hAttr() HTML escaping
│   │   │   ├── url.php           # asset(), route(), redirect(), getSiteLogo(), getSiteConfig()
│   │   │   ├── format.php        # formatCount(), date/time formatters
│   │   │   └── seo.php           # seoMeta(), jsonld(), breadcrumbs()
│   │   ├── middleware/
│   │   │   └── AdminAuthMiddleware.php  # Admin authentication guard
│   │   └── models/               # (reserved for future DB models)
│   ├── admin/
│   │   ├── controllers/
│   │   │   └── AdminControllers.php  # All admin controllers (Dashboard, Series, Episodes, etc.)
│   │   ├── middleware/           # (reserved)
│   │   └── templates/
│   │       ├── layout.php        # Admin layout (sidebar + header + content)
│   │       ├── dashboard.php     # Dashboard with stats
│   │       ├── settings.php      # Site settings (cards: General, Branding, SEO, Analytics, Email, Profile, Sitemap)
│   │       ├── payment-settings.php  # Payhub gateway configuration
│   │       ├── series-list.php   # Series management table
│   │       ├── series-form.php   # Add/edit series form
│   │       ├── episodes-list.php # Episodes management table
│   │       ├── episode-form.php  # Add/edit episode form
│   │       ├── shelves-list.php  # Shelves management
│   │       ├── banners-list.php  # Banner management
│   │       ├── blog-list.php     # Blog posts table
│   │       ├── blog-form.php     # Add/edit blog form
│   │       ├── users-list.php    # User management table
│   │       ├── user-edit.php     # Edit user (password, role, coins, block, login-as)
│   │       ├── user-detail.php   # User detail view
│   │       ├── coins-transactions.php  # Coin transaction history
│   │       ├── coins-packages.php      # Coin package management
│   │       ├── sitemap.php       # Sitemap status + regenerate
│   │       ├── security/         # Brute force, login attempts, IP lists, user locks, countries
│   │       └── emails/           # Email templates + queue
│   ├── templates/
│   │   ├── layouts/
│   │   │   └── main.php          # Main site layout (header, footer, preloader, SEO)
│   │   └── pages/
│   │       ├── home.php          # Landing page (banner carousel, shelves)
│   │       ├── series-detail.php # Series detail with episode list
│   │       ├── episode-player.php# Video player
│   │       ├── shelf.php         # Shelf listing
│   │       ├── search.php        # Search results
│   │       ├── blog-listing.php  # Blog index
│   │       ├── blog-detail.php   # Blog post
│   │       ├── login.php         # Sign in form
│   │       ├── register.php      # Registration form
│   │       ├── profile.php       # User profile
│   │       ├── watch-history.php # Watch history
│   │       ├── favorites.php     # Favorites
│   │       ├── coin-shop.php     # Coin shop + virtual bank account
│   │       ├── about.php         # About page
│   │       ├── download.php      # App download page
│   │       ├── 404.php           # 404 Not Found
│   │       ├── 403.php           # 403 Forbidden
│   │       └── maintenance.php   # Maintenance mode
│   ├── assets/
│   │   ├── css/
│   │   │   └── cinematic.css     # Custom CSS (forms, cards, preloader, responsive)
│   │   ├── js/
│   │   │   └── app.js            # Scroll behavior, console branding
│   │   ├── fonts/                # Font files
│   │   ├── img/                  # Default images
│   │   └── uploads/              # Uploaded logos, favicons, OG images
│   ├── install/
│   │   └── index.php             # 4-stage installation wizard
│   └── storage/
│       ├── cache/                # File cache
│       ├── sessions/             # File-based sessions
│       ├── logs/                 # Log files
│       └── install.lock          # Installation complete flag
├── schema/
│   ├── 001_initial_schema_mysql.sql   # Core tables (site_config, series, episodes, users, etc.)
│   ├── 002_payment_gateway.sql        # Payment tables (settings, virtual accounts, transactions)
│   └── 003_add_seo_columns.sql        # Migration: SEO/branding columns for site_config
├── backend/
│   └── api-server/               # Go/Node.js backend API
├── android/                      # Android app (Kotlin)
│   └── app/
│       ├── build.gradle.kts      # Build config
│       ├── src/
│       │   └── main/
│       │       ├── java/com/SOLOREEL/app/
│       │       │   ├── MainActivity.kt
│       │       │   ├── SOLOREELApp.kt
│       │       │   ├── data/
│       │       │   │   ├── api/           # Retrofit API service
│       │       │   │   ├── model/         # Data classes
│       │       │   │   ├── repository/    # Data repositories
│       │       │   │   └── local/         # Room DB, DataStore
│       │       │   ├── ui/
│       │       │   │   ├── theme/         # Material theme
│       │       │   │   ├── navigation/    # NavHost
│       │       │   │   ├── home/          # Home screen
│       │       │   │   ├── detail/        # Series/episode detail
│       │       │   │   ├── player/        # Video player
│       │       │   │   ├── search/        # Search screen
│       │       │   │   ├── auth/          # Login/register
│       │       │   │   ├── profile/       # User profile
│       │       │   │   ├── coins/         # Coin shop
│       │       │   │   ├── favorites/     # Favorites
│       │       │   │   └── components/    # Reusable composables
│       │       │   ├── di/               # Hilt dependency injection
│       │       │   └── util/             # Extensions, constants
│       │       └── res/                  # Resources
│       └── proguard-rules.pro
├── ios/                          # iOS app (Swift)
│   ├── SOLOREEL.xcodeproj/
│   ├── SOLOREEL/
│   │   ├── SOLOREELApp.swift          # App entry point
│   │   ├── ContentView.swift           # Root view
│   │   ├── AppDelegate.swift           # App delegate
│   │   ├── Info.plist                  # App configuration
│   │   ├── Data/
│   │   │   ├── API/                    # Alamofire API service
│   │   │   ├── Models/                 # Codable models
│   │   │   ├── Repositories/           # Data repositories
│   │   │   └── Local/                  # CoreData, UserDefaults
│   │   ├── UI/
│   │   │   ├── Theme/                  # SwiftUI theme
│   │   │   ├── Navigation/             # TabView + NavigationStack
│   │   │   ├── Home/                   # Home screen
│   │   │   ├── Detail/                 # Series/episode detail
│   │   │   ├── Player/                 # Video player
│   │   │   ├── Search/                 # Search screen
│   │   │   ├── Auth/                   # Login/register
│   │   │   ├── Profile/                # User profile
│   │   │   ├── Coins/                  # Coin shop
│   │   │   ├── Favorites/              # Favorites
│   │   │   ├── History/                # Watch history
│   │   │   └── Components/             # Reusable views
│   │   ├── DI/                         # Dependency injection
│   │   ├── Extensions/                 # Swift extensions
│   │   └── Utilities/                  # Constants, helpers
│   ├── SOLOREELTests/                 # Unit tests
│   └── SOLOREELUITests/               # UI tests
├── docker/                       # Docker compose + nginx/PHP configs
└── infra/                        # Infrastructure configs
```

---

## 3. Database Schema

### 3.1 Core Tables (`001_initial_schema_mysql.sql`)

| Table | Purpose |
|-------|---------|
| `site_config` | Site settings (title, logo, SEO, SMTP, analytics, maintenance mode) |
| `users` | User accounts (email, username, password hash, role, coins, status) |
| `series` | TV series (title, synopsis, genre, status, cover image) |
| `episodes` | Episodes (title, video URL, duration, series FK, is_free) |
| `shelves` | Content shelves (name, slug, emoji, sort order) |
| `banners` | Homepage banners (image, title, subtitle, link) |
| `blog_posts` | Blog/fandom posts (title, body, excerpt, cover image) |
| `blog_categories` | Blog categories |
| `coin_packages` | Coin purchase packages (name, coins, price) |
| `coin_transactions` | Coin transaction log |
| `watch_history` | User watch history |
| `favorites` | User favorites |
| `login_attempts` | Brute force login tracking |
| `ip_whitelist` / `ip_blacklist` | IP access control |
| `country_rules` | Country-based access rules |
| `email_templates` | System email templates |
| `email_queue` | Outgoing email queue |
| `sitemap_log` | Sitemap generation log |
| `license_info` | License key storage |

### 3.2 Payment Tables (`002_payment_gateway.sql`)

| Table | Purpose |
|-------|---------|
| `payment_settings` | Payhub API keys, mode, webhook URL |
| `virtual_bank_accounts` | Per-user virtual bank accounts |
| `payment_transactions` | Payment transaction log (reference, amount, status, coins) |

### 3.3 SEO/Branding Migration (`003_add_seo_columns.sql`)

Adds to `site_config`: `favicon_url`, `og_image_url`, `meta_title`, `meta_description`, `twitter_handle`, `enable_jsonld`, `ga_id`, `gtm_id`, `custom_header_code`, `custom_footer_code`.

---

## 4. Routing System

### 4.1 Router Architecture (`app/core/Router.php`)

- PSR-style pattern matching with named parameters: `{slug}`, `{id}`, `{...slug}` (catch-all)
- HTTP method spoofing: POST requests with `_method=PUT` or `_method=DELETE` are converted
- Admin URL detection: requests starting with `/admin` prefer `App\Admin\Controllers\*` namespace
- Middleware execution: runs before controller, can short-circuit with false return
- 404 handler: JSON for API requests, HTML 404 page for browser requests

### 4.2 Route Definitions (`app/config/routes.php`)

**Public Routes:**

| Method | URI | Controller | Purpose |
|--------|-----|-----------|---------|
| GET | `/` | HomeController@index | Landing page |
| GET | `/movie/{slug}` | SeriesController@detail | Series detail |
| GET | `/episodes/{...slug}` | EpisodeController@player | Video player |
| GET | `/shelf/{slug}` | ShelfController@index | Shelf browse |
| GET | `/search` | SearchController@index | Search |
| GET | `/movie-genres/{genre}` | GenreController@browse | Genre browse |
| GET/POST | `/login` | AuthController | Authentication |
| GET/POST | `/register` | AuthController | Registration |
| GET | `/logout` | AuthController@logout | Logout |
| GET | `/profile` | UserController@profile | User profile |
| GET | `/watch-history` | UserController@watchHistory | Watch history |
| GET | `/favorites` | UserController@favorites | Favorites |
| POST/DELETE | `/favorites/{seriesId}` | UserController | Toggle favorite |
| GET | `/coin-shop` | CoinController@shop | Coin purchase |
| POST | `/unlock/{episodeId}` | CoinController@unlock | Unlock episode |
| POST | `/coins/purchase` | CoinController@purchase | Buy coins |
| GET | `/payment/verify` | PaymentController@verify | Payment callback |
| POST | `/payment/webhook` | PaymentController@webhook | Payhub webhook |
| GET | `/fandom` | BlogController@index | Blog index |
| GET | `/fandom/{slug}` | BlogController@detail | Blog post |
| GET | `/sitemap.xml` | SitemapController@index | XML sitemap |
| GET | `/robots.txt` | RobotsController@index | Robots.txt |
| GET | `/llms.txt` | LlmsController@index | AI visibility |
| GET | `/favicon.ico` | FaviconController@index | Dynamic favicon |

**Admin Routes** (prefix: `/admin`, middleware: `AdminAuthMiddleware`):

| Method | URI | Controller | Purpose |
|--------|-----|-----------|---------|
| GET | `/` | DashboardController@index | Dashboard |
| GET/POST/PUT/DELETE | `/series/*` | SeriesController | Series CRUD |
| GET/POST/PUT | `/episodes/*` | EpisodeController | Episodes CRUD |
| GET/POST/PUT | `/shelves/*` | ShelfController | Shelves CRUD |
| GET/POST | `/banners/*` | BannerController | Banners CRUD |
| GET/POST/PUT | `/blog/*` | BlogController | Blog CRUD |
| GET/PUT/DELETE | `/users/*` | UserController | User management (edit, delete, block, login-as) |
| GET/POST | `/coins/*` | CoinController | Coin management |
| GET/PUT/POST/DELETE | `/security/*` | SecurityController + CountryController | Security config |
| GET/PUT | `/emails/*` | EmailController | Email templates + queue |
| GET/PUT/POST | `/settings/*` | SettingsController | Site settings + profile |
| GET/PUT | `/settings/payments` | PaymentSettingsController | Payhub config |

---

## 5. Authentication & Authorization

### 5.1 User Authentication

- Password hashing: Argon2ID via `password_hash()`
- Login: validates username/email + password, checks brute force attempts, sets session
- Registration: validates email uniqueness, creates user with initial coins
- Logout: destroys session

### 5.2 Session Management (`app/core/Session.php`)

- Storage: file-based (default) or Redis
- Flash messages: self-clearing after one read
- Session keys: `user_id`, `user_name`, `user_email`, `user_role`, `user_coin_balance`
- Impersonation: `_admin_impersonating` flag for admin login-as-user

### 5.3 Admin Authentication (`app/middleware/AdminAuthMiddleware.php`)

- Checks `Session::isLoggedIn()` — redirects to `/login` if not authenticated
- Checks `user_role` is `admin` or `super_admin` — shows 403 if not
- Stores redirect URL for post-login return

### 5.4 Brute Force Protection (`app/core/Bruteforce.php`)

- Tracks failed login attempts by IP and username
- Configurable: max attempts, lockout duration, reset window
- Auto-blocks IPs after threshold
- Admin notifications on security events

---

## 6. Content Management

### 6.1 Series

- CRUD via admin panel → backend API
- Cover image upload (PNG/JPG/WEBP, max 2MB) → stored in `assets/uploads/`
- Status: ongoing / completed
- Linked to shelves for organization
- Canonical URL + SEO meta per series

### 6.2 Episodes

- CRUD via admin panel → backend API
- Video file upload + thumbnail
- Episode numbering and duration tracking
- Free / coin-locked toggle
- Video player page with HLS support

### 6.3 Shelves

- Content organization: "New Release", "TOP", "Hidden Identity", etc.
- Custom emoji, sort order
- Shelf pages show grid of series

### 6.4 Banners

- Homepage carousel with Alpine.js auto-rotation (5s interval)
- Image upload, title, subtitle, link URL
- Crossfade transitions

### 6.5 Blog / Fandom

- Blog posts with categories, rich text body
- Author attribution
- Related posts by category

---

## 7. Payment Integration (Payhub)

### 7.1 Gateway Client (`app/core/PayhubGateway.php`)

- API authentication: Bearer token via `secret_key`
- Configurable base URL (default: `https://api.payhub.com.ng`)
- Key methods:
  - `createVirtualAccount()` — auto-generates per-user bank account
  - `initializePayment()` — starts checkout flow (amount, email, reference, callback)
  - `verifyTransaction()` — confirms payment status
  - `verifyWebhookSignature()` — validates HMAC-SHA512 webhook

### 7.2 Virtual Bank Accounts

- Created automatically when user visits `/coin-shop` if not already assigned
- Stored in `virtual_bank_accounts` table (account number, bank name, reference)
- Displayed on coin shop page with copy-friendly format
- Funds credited automatically via webhook or payment verify callback

### 7.3 Payment Flow

1. User selects coin package → `POST /coins/purchase`
2. Controller initializes Payhub transaction with reference
3. Transaction logged in `payment_transactions` (status: `pending`)
4. User redirected to Payhub checkout page (popup or redirect)
5. After payment: redirected to `/payment/verify?reference=XXX`
6. Controller verifies transaction → awards coins → updates `coin_balance`
7. Webhook fallback: `POST /payment/webhook` handles real-time notifications

### 7.4 Admin Payment Settings (`/admin/settings/payments`)

- Payhub public key, secret key, API base URL
- Mode: sandbox / live
- Webhook URL (auto-generated from `BASE_URL`)
- Enable/disable toggle

---

## 8. Coin Economy

### 8.1 Coin Packages

- Admin-defined packages (name, coins, price in NGN)
- Displayed as grid cards on coin shop page
- Each user has a `coin_balance` column

### 8.2 Coin Transactions

- Logged for every: purchase, episode unlock, admin adjustment
- Types: `purchase`, `unlock`, `bonus`, `refund`, `admin`
- Admin view: full transaction history with filtering

### 8.3 Episode Unlocking

- Non-free episodes require coin payment
- `POST /unlock/{episodeId}` deducts coins → grants access
- Unlocked status stored for the user-episode pair

---

## 9. SEO & Performance

### 9.1 On-Page SEO

- **Meta Tags**: configurable defaults per site + per-page overrides
  - Title (50-60 chars recommended)
  - Description (150-160 chars recommended)
  - Keywords
- **Open Graph**: og:title, og:description, og:image, og:type, og:url, og:site_name
- **Twitter Card**: summary_large_image with twitter:site handle
- **Canonical URLs**: automatically generated per page
- **Robots**: index/follow with max-image-preview:large

### 9.2 Structured Data (JSON-LD)

Implemented schemas with `@id` references for proper linking:

| Schema | Pages | Properties |
|--------|-------|-----------|
| `Organization` | All pages | name, url, logo (ImageObject), description, sameAs |
| `WebSite` | All pages | name, url, SearchAction, publisher @id |
| `TVSeries` | Series detail | name, description, image, genre, publisher |
| `VideoObject` | Episode player | name, thumbnailUrl, uploadDate, duration, contentUrl |
| `Article` | Blog posts | headline, description, datePublished, author (Person) |
| `BreadcrumbList` | Detail pages | Position-indexed list items |
| `Person` | Author profiles | name, image, jobTitle, sameAs |

### 9.3 XML Sitemap (`/sitemap.xml`)

- Auto-generated on settings save or manual regenerate
- Includes: homepage, series, episodes, blog posts, shelves, static pages
- Image tags for series cover images
- Priority: 1.0 (home), 0.9 (series), 0.7 (blog/shelves)
- Changefreq: daily (home/blog), weekly (series)
- Namespaces: standard + image + video

### 9.4 Robots.txt (`/robots.txt`)

- Allows all major AI crawlers (GPTBot, ChatGPT-User, ClaudeBot, PerplexityBot, etc.)
- Disallows `/admin/`, `/install/`, `/profile`, `/login`, `/register`
- Sitemap URL reference

### 9.5 AI Visibility (`/llms.txt`)

- Markdown-formatted site overview for LLM consumption
- Lists: site description, categories, primary pages
- Auto-regenerated on settings save
- HTML comment signals injected in page source

### 9.6 Google Analytics / Tag Manager

- GA4 Measurement ID (`G-XXXXXXXXXX`)
- GTM Container ID (`GTM-XXXXXXX`)
- Injected in `<head>` and `<body>` respectively
- Custom header/footer code fields for additional integration

### 9.7 Performance Optimizations

- CDN assets (Tailwind, Alpine.js, HTMX) via jsDelivr
- `preconnect` and `dns-prefetch` for CDN domains
- `defer` attribute on JavaScript
- CSS `preload` for critical styles
- Lazy loading (`loading="lazy"`) on images
- File-based caching with configurable TTL
- Optional Redis for high-traffic caching

---

## 10. Android App Architecture

### 10.1 Technology Stack

| Component | Technology |
|-----------|-----------|
| **Language** | Kotlin |
| **UI Framework** | Jetpack Compose (Material 3) |
| **Navigation** | Compose Navigation (NavHost) |
| **Networking** | Retrofit 2 + OkHttp + Kotlin Serialization |
| **Image Loading** | Coil |
| **Video Player** | ExoPlayer (Media3) with HLS support |
| **DI** | Hilt (Dagger) |
| **Local Storage** | Room Database, DataStore Preferences |
| **Auth Token** | EncryptedSharedPreferences |
| **State Management** | ViewModel + StateFlow |
| **Payments** | Payhub Android SDK or WebView checkout |
| **Firebase** | Cloud Messaging (FCM), Crashlytics, Analytics |
| **Build System** | Gradle (Kotlin DSL) |
| **Min SDK** | 24 (Android 7.0) |
| **Target SDK** | 34 (Android 14) |

### 10.2 Project Structure

```
android/
├── app/
│   ├── build.gradle.kts
│   ├── src/main/
│   │   ├── java/com/SOLOREEL/app/
│   │   │   ├── SOLOREELApp.kt           # Application class (Hilt)
│   │   │   ├── MainActivity.kt           # Single activity
│   │   │   ├── data/
│   │   │   │   ├── api/
│   │   │   │   │   ├── SOLOREELApi.kt        # Retrofit interface
│   │   │   │   │   ├── AuthInterceptor.kt     # JWT token injection
│   │   │   │   │   └── ApiResponse.kt         # Generic wrapper
│   │   │   │   ├── model/
│   │   │   │   │   ├── Series.kt
│   │   │   │   │   ├── Episode.kt
│   │   │   │   │   ├── User.kt
│   │   │   │   │   ├── Banner.kt
│   │   │   │   │   ├── Shelf.kt
│   │   │   │   │   ├── CoinPackage.kt
│   │   │   │   │   ├── Transaction.kt
│   │   │   │   │   ├── VirtualAccount.kt
│   │   │   │   │   └── AuthModels.kt
│   │   │   │   ├── repository/
│   │   │   │   │   ├── AuthRepository.kt
│   │   │   │   │   ├── SeriesRepository.kt
│   │   │   │   │   ├── EpisodeRepository.kt
│   │   │   │   │   ├── UserRepository.kt
│   │   │   │   │   └── CoinRepository.kt
│   │   │   │   └── local/
│   │   │   │       ├── AppDatabase.kt         # Room DB
│   │   │   │       ├── dao/
│   │   │   │       ├── entity/
│   │   │   │       └── TokenManager.kt        # EncryptedSharedPrefs
│   │   │   ├── di/
│   │   │   │   ├── AppModule.kt               # Singletons
│   │   │   │   ├── NetworkModule.kt            # Retrofit, OkHttp
│   │   │   │   └── DatabaseModule.kt           # Room
│   │   │   ├── ui/
│   │   │   │   ├── theme/
│   │   │   │   │   ├── Theme.kt
│   │   │   │   │   ├── Color.kt
│   │   │   │   │   └── Type.kt
│   │   │   │   ├── navigation/
│   │   │   │   │   ├── NavGraph.kt             # NavHost routes
│   │   │   │   │   └── Screen.kt               # Sealed route class
│   │   │   │   ├── MainScreen.kt               # Scaffold + BottomNav
│   │   │   │   ├── home/
│   │   │   │   │   ├── HomeScreen.kt
│   │   │   │   │   ├── HomeViewModel.kt
│   │   │   │   │   └── components/
│   │   │   │   │       ├── BannerCarousel.kt
│   │   │   │   │       ├── ShelfRow.kt
│   │   │   │   │       └── SeriesCard.kt
│   │   │   │   ├── detail/
│   │   │   │   │   ├── SeriesDetailScreen.kt
│   │   │   │   │   ├── SeriesDetailViewModel.kt
│   │   │   │   │   └── EpisodeListItem.kt
│   │   │   │   ├── player/
│   │   │   │   │   ├── PlayerScreen.kt
│   │   │   │   │   ├── PlayerViewModel.kt
│   │   │   │   │   └── PlayerControls.kt
│   │   │   │   ├── search/
│   │   │   │   │   ├── SearchScreen.kt
│   │   │   │   │   └── SearchViewModel.kt
│   │   │   │   ├── auth/
│   │   │   │   │   ├── LoginScreen.kt
│   │   │   │   │   ├── RegisterScreen.kt
│   │   │   │   │   └── AuthViewModel.kt
│   │   │   │   ├── profile/
│   │   │   │   │   ├── ProfileScreen.kt
│   │   │   │   │   ├── EditProfileScreen.kt
│   │   │   │   │   └── ProfileViewModel.kt
│   │   │   │   ├── coins/
│   │   │   │   │   ├── CoinShopScreen.kt
│   │   │   │   │   ├── VirtualAccountCard.kt
│   │   │   │   │   └── CoinViewModel.kt
│   │   │   │   ├── favorites/
│   │   │   │   │   ├── FavoritesScreen.kt
│   │   │   │   │   └── FavoritesViewModel.kt
│   │   │   │   ├── history/
│   │   │   │   │   ├── HistoryScreen.kt
│   │   │   │   │   └── HistoryViewModel.kt
│   │   │   │   └── components/
│   │   │   │       ├── LoadingIndicator.kt
│   │   │   │       ├── ErrorView.kt
│   │   │   │       ├── AppBar.kt
│   │   │   │       └── BottomNavBar.kt
│   │   │   └── util/
│   │   │       ├── Constants.kt
│   │   │       ├── Extensions.kt
│   │   │       └── Resource.kt          # Sealed result type
│   │   └── res/
│   │       ├── values/
│   │       │   ├── strings.xml
│   │       │   ├── colors.xml
│   │       │   └── themes.xml
│   │       ├── drawable/                # Icons, splash
│   │       └── mipmap/                  # App icons
│   └── proguard-rules.pro
├── build.gradle.kts                     # Project-level
├── settings.gradle.kts
└── gradle.properties
```

### 10.3 Key Features

| Feature | Implementation |
|---------|---------------|
| Splash Screen | Branded splash with logo, 1.5s duration, auto-navigate |
| Onboarding | 3-slide intro (skip-able), shown on first launch |
| Home Feed | Banner carousel (auto-scroll), horizontal shelf rows |
| Series Detail | Cover image, synopsis, episode list with lock indicators |
| Video Player | ExoPlayer HLS, fullscreen toggle, progress tracking, resume from last position |
| Search | Debounced text input, grid results |
| Authentication | Email + password login, registration with display name |
| Coin Shop | Package grid, Payhub checkout (WebView popup), virtual bank account display |
| Favorites | Grid view, add/remove with heart toggle |
| Watch History | Chronological list, tap to resume |
| Profile | Edit display name, email, change password |
| Push Notifications | FCM for new episode alerts, promotional campaigns |
| Offline Support | Room DB cache for recently viewed content |
| Dark Mode | Dark-only theme (cinematic brand) |
| Dynamic Colors | Material You dynamic theming on Android 12+ |

### 10.4 Navigation Graph

```
Bottom Navigation:
├── Home (home icon)
│   └── SeriesDetail → EpisodePlayer (push)
├── Search (search icon)
│   └── SeriesDetail → EpisodePlayer (push)
├── Coin Shop (coin icon)
│   └── PayhubWebView (modal)
└── Profile (person icon)
    ├── Edit Profile (push)
    ├── Favorites (push) → SeriesDetail → EpisodePlayer
    ├── Watch History (push) → EpisodePlayer
    └── Logout → Login

Auth Flow (no bottom nav):
└── Login ↔ Register
```

### 10.5 API Client Setup (Retrofit)

```kotlin
// SOLOREELApi.kt
interface SOLOREELApi {
    @GET("api/v1/banners")
    suspend fun getBanners(@Query("active") active: String): ApiResponse<List<Banner>>

    @GET("api/v1/series")
    suspend fun getSeries(
        @Query("shelf") shelf: String?,
        @Query("size") size: Int = 12
    ): ApiResponse<List<Series>>

    @GET("api/v1/series/{slug}/by-slug")
    suspend fun getSeriesBySlug(@Path("slug") slug: String): ApiResponse<Series>

    @GET("api/v1/episodes/{slug}/by-slug")
    suspend fun getEpisodeBySlug(@Path("slug") slug: String): ApiResponse<Episode>

    @POST("api/v1/auth/login")
    suspend fun login(@Body request: LoginRequest): ApiResponse<AuthToken>

    @POST("api/v1/auth/register")
    suspend fun register(@Body request: RegisterRequest): ApiResponse<AuthToken>

    @GET("api/v1/user/profile")
    suspend fun getProfile(): ApiResponse<User>

    @GET("api/v1/coin-packages")
    suspend fun getCoinPackages(): ApiResponse<List<CoinPackage>>

    @POST("api/v1/coins/purchase")
    suspend fun purchaseCoins(@Body request: PurchaseRequest): ApiResponse<PaymentInit>

    @GET("api/v1/user/virtual-account")
    suspend fun getVirtualAccount(): ApiResponse<VirtualAccount>
}
```

---

## 11. iOS App Architecture

### 11.1 Technology Stack

| Component | Technology |
|-----------|-----------|
| **Language** | Swift 5.9+ |
| **UI Framework** | SwiftUI (iOS 16+) |
| **Navigation** | NavigationStack + TabView |
| **Networking** | Alamofire + Codable |
| **Image Loading** | Kingfisher (async image caching) |
| **Video Player** | AVPlayer with PiP (Picture in Picture) |
| **DI** | Manual DI via Environment + singleton containers |
| **Local Storage** | CoreData / SwiftData, UserDefaults |
| **Auth Token** | Keychain Services |
| **State Management** | @Observable (iOS 17+) / @StateObject + Combine |
| **Payments** | Payhub iOS SDK or SFSafariViewController checkout |
| **Push Notifications** | APNs (Apple Push Notification service) |
| **Analytics** | Firebase Analytics + Crashlytics |
| **Build System** | Xcode 15+, Swift Package Manager |
| **Min Deployment** | iOS 16.0 |
| **Target** | iOS 17.x |

### 11.2 Project Structure

```
ios/
├── SOLOREEL.xcodeproj/
├── SOLOREEL/
│   ├── SOLOREELApp.swift             # @main App struct
│   ├── ContentView.swift              # Root view (auth check → main or login)
│   ├── AppDelegate.swift              # Push notifications, Firebase config
│   ├── Info.plist                     # App permissions, URL schemes
│   ├── Assets.xcassets/               # App icons, colors, images
│   ├── Data/
│   │   ├── API/
│   │   │   ├── SOLOREELAPI.swift          # Alamofire router
│   │   │   ├── APIClient.swift             # Singleton HTTP client
│   │   │   ├── AuthInterceptor.swift       # JWT token adapter
│   │   │   └── Endpoint.swift              # URLRequestConvertible enum
│   │   ├── Models/
│   │   │   ├── Series.swift
│   │   │   ├── Episode.swift
│   │   │   ├── User.swift
│   │   │   ├── Banner.swift
│   │   │   ├── Shelf.swift
│   │   │   ├── CoinPackage.swift
│   │   │   ├── Payment.swift
│   │   │   ├── VirtualAccount.swift
│   │   │   └── APIResponse.swift           # Generic wrapper
│   │   ├── Repositories/
│   │   │   ├── AuthRepository.swift
│   │   │   ├── ContentRepository.swift
│   │   │   ├── UserRepository.swift
│   │   │   └── CoinRepository.swift
│   │   └── Local/
│   │       ├── KeychainManager.swift        # Secure token storage
│   │       ├── UserDefaultsManager.swift    # Preferences
│   │       └── CoreDataStack.swift          # Offline cache
│   ├── UI/
│   │   ├── Theme/
│   │   │   ├── AppTheme.swift               # Colors, fonts, spacing
│   │   │   ├── Colors.xcassets
│   │   │   └── ViewModifiers.swift
│   │   ├── Navigation/
│   │   │   ├── AppTabView.swift             # TabBar container
│   │   │   ├── AppRouter.swift              # Navigation path enums
│   │   │   └── DeepLinkHandler.swift
│   │   ├── Splash/
│   │   │   └── SplashView.swift             # Animated splash screen
│   │   ├── Onboarding/
│   │   │   ├── OnboardingView.swift
│   │   │   └── OnboardingPage.swift
│   │   ├── Home/
│   │   │   ├── HomeView.swift
│   │   │   ├── HomeViewModel.swift
│   │   │   └── Components/
│   │   │       ├── BannerCarouselView.swift
│   │   │       ├── ShelfRowView.swift
│   │   │       └── SeriesCardView.swift
│   │   ├── Detail/
│   │   │   ├── SeriesDetailView.swift
│   │   │   ├── SeriesDetailViewModel.swift
│   │   │   └── EpisodeRowView.swift
│   │   ├── Player/
│   │   │   ├── PlayerView.swift
│   │   │   ├── PlayerViewModel.swift
│   │   │   ├── PlayerControlsView.swift
│   │   │   └── PiPController.swift
│   │   ├── Search/
│   │   │   ├── SearchView.swift
│   │   │   └── SearchViewModel.swift
│   │   ├── Auth/
│   │   │   ├── LoginView.swift
│   │   │   ├── RegisterView.swift
│   │   │   └── AuthViewModel.swift
│   │   ├── Profile/
│   │   │   ├── ProfileView.swift
│   │   │   ├── EditProfileView.swift
│   │   │   └── ProfileViewModel.swift
│   │   ├── Coins/
│   │   │   ├── CoinShopView.swift
│   │   │   ├── VirtualAccountCardView.swift
│   │   │   ├── PayhubCheckoutView.swift   # SFSafariViewController wrapper
│   │   │   └── CoinViewModel.swift
│   │   ├── Favorites/
│   │   │   ├── FavoritesView.swift
│   │   │   └── FavoritesViewModel.swift
│   │   ├── History/
│   │   │   ├── WatchHistoryView.swift
│   │   │   └── WatchHistoryViewModel.swift
│   │   └── Components/
│   │       ├── LoadingView.swift
│   │       ├── ErrorView.swift
│   │       ├── EmptyStateView.swift
│   │       ├── LockedEpisodeOverlay.swift
│   │       ├── CoinBalanceBadge.swift
│   │       └── RatingStarsView.swift
│   ├── DI/
│   │   └── DIContainer.swift             # Service locator
│   ├── Extensions/
│   │   ├── View+Extensions.swift
│   │   ├── Color+Extensions.swift
│   │   ├── String+Extensions.swift
│   │   └── Date+Extensions.swift
│   └── Utilities/
│       ├── Constants.swift                # API_BASE_URL, keys
│       ├── Logger.swift
│       └── HapticManager.swift
├── SOLOREELTests/                       # Unit tests (XCTest)
│   ├── ViewModelTests/
│   ├── RepositoryTests/
│   └── APITests/
└── SOLOREELUITests/                     # UI tests (XCUITest)
```

### 11.3 Key Features

| Feature | Implementation |
|---------|---------------|
| Splash Screen | Animated logo with fade-in, 2s duration via `withAnimation` |
| Onboarding | PageTabView with 3 screens, skip button, shown on first launch (AppStorage flag) |
| Home Feed | Vertical ScrollView with LazyVStack, ParallaxHeader for banner carousel |
| Series Detail | Sticky header image, ScrollView with synopsis, episode list with lock overlay |
| Video Player | Custom AVPlayerViewController, HLS streaming, PiP via AVPictureInPictureController, progress save to CoreData |
| Search | `.searchable()` modifier with debounced Combine publisher |
| Authentication | Login form with email validation, biometric login (Face ID / Touch ID) via LocalAuthentication |
| Coin Shop | LazyVGrid of packages, Payhub checkout via SFSafariViewController, virtual bank account card with copy-to-clipboard |
| Favorites | Grid layout, swipe-to-delete, heart toggle animation |
| Watch History | List with thumbnail + progress bar, tap to resume |
| Profile | Form with validation, photo picker (PHPickerViewController), password change |
| Push Notifications | APNs registration, Firebase Cloud Messaging, rich notifications with image attachments |
| Offline Support | CoreData cache for home feed + recently watched, network monitor via NWPathMonitor |
| Dark Mode | Dark-only theme matching web brand (black backgrounds, red accents) |
| Dynamic Island | Live Activity for video progress (iOS 16.1+) |
| Widgets | Home Screen widgets: Continue Watching, Trending Today |
| Siri Shortcuts | "Play SOLOREEL", "Open my favorites" intents |
| App Clips | Instant preview of trending series without full install |

### 11.4 Navigation Architecture

```
TabView (4 tabs):
├── Home (house.fill icon)
│   └── NavigationStack
│       └── SeriesDetail → EpisodePlayer (push)
├── Search (magnifyingglass icon)
│   └── NavigationStack
│       └── SeriesDetail → EpisodePlayer (push)
├── Coins (bitcoinsign.circle.fill icon)
│   └── NavigationStack
│       ├── CoinShop
│       └── PayhubCheckout (sheet)
└── Profile (person.crop.circle.fill icon)
    └── NavigationStack
        ├── ProfileView
        ├── EditProfile (push)
        ├── Favorites (push) → SeriesDetail → EpisodePlayer
        ├── WatchHistory (push) → EpisodePlayer
        └── Logout → LoginView (fullScreenCover)

Auth Flow (fullScreenCover over TabView):
└── LoginView ↔ RegisterView (toggle)
```

### 11.5 API Client Setup (Alamofire)

```swift
// SOLOREELAPI.swift
import Alamofire

enum SOLOREELAPI: URLRequestConvertible {
    case getBanners(active: String)
    case getSeries(shelf: String?, size: Int)
    case getSeriesBySlug(slug: String)
    case getEpisodeBySlug(slug: String)
    case login(email: String, password: String)
    case register(email: String, username: String, password: String, displayName: String)
    case getProfile
    case getCoinPackages
    case purchaseCoins(packageId: Int, amount: Double, reference: String)
    case getVirtualAccount

    var baseURL: URL { URL(string: APIConstants.baseURL)! }

    var path: String {
        switch self {
        case .getBanners: return "/api/v1/banners"
        case .getSeries: return "/api/v1/series"
        case .getSeriesBySlug(let slug): return "/api/v1/series/\(slug)/by-slug"
        case .getEpisodeBySlug(let slug): return "/api/v1/episodes/\(slug)/by-slug"
        case .login: return "/api/v1/auth/login"
        case .register: return "/api/v1/auth/register"
        case .getProfile: return "/api/v1/user/profile"
        case .getCoinPackages: return "/api/v1/coin-packages"
        case .purchaseCoins: return "/api/v1/coins/purchase"
        case .getVirtualAccount: return "/api/v1/user/virtual-account"
        }
    }

    var method: HTTPMethod {
        switch self {
        case .login, .register, .purchaseCoins: return .post
        default: return .get
        }
    }

    func asURLRequest() throws -> URLRequest {
        var request = URLRequest(url: baseURL.appendingPathComponent(path))
        request.method = method
        request.headers = [
            "Content-Type": "application/json",
            "Accept": "application/json",
            "Authorization": "Bearer \(KeychainManager.shared.accessToken ?? "")"
        ]
        // Add parameters...
        return request
    }
}
```

### 11.6 iOS-Specific Features (vs Android)

| Feature | iOS Implementation |
|---------|-------------------|
| Face ID / Touch ID | `LAContext.evaluatePolicy()` for biometric login |
| Picture in Picture | `AVPictureInPictureController` for background video |
| Dynamic Island | `ActivityKit` for video progress in Dynamic Island |
| Home Screen Widgets | `WidgetKit` for continue-watching & trending widgets |
| Siri Shortcuts | `INIntent` donation for voice commands |
| App Clips | Lightweight `< 10MB` instant preview |
| Haptic Feedback | `UIImpactFeedbackGenerator` for unlock/purchase events |
| SharePlay | Group watching via FaceTime (future) |
| iCloud Sync | Watch history sync across Apple devices |
| Sign in with Apple | `ASAuthorizationController` as alternative auth |

---

## 12. Mobile App Authentication Flow

### 12.1 JWT Token Management

```
1. User enters email + password in app
2. POST /api/v1/auth/login → returns { access_token, refresh_token, expires_in }
3. Access token stored in EncryptedSharedPrefs (Android) / Keychain (iOS)
4. Every API request includes header: Authorization: Bearer {access_token}
5. When token expires (401 response), refresh with POST /api/v1/auth/refresh
6. On refresh failure, redirect to login screen
7. On app launch, check stored token validity, auto-login if valid
```

### 12.2 Biometric Authentication

```
1. User enables biometric login in Profile settings
2. On next login, app prompts for biometric (fingerprint / face)
3. LocalAuthentication (iOS) / BiometricPrompt (Android) validates
4. On success, retrieves stored credentials from secure storage
5. Performs silent login via API
6. Falls back to password entry on biometric failure
```

---

## 13. Mobile Video Player

### 13.1 Features

| Feature | Android (ExoPlayer) | iOS (AVPlayer) |
|---------|---------------------|----------------|
| HLS Streaming | `.m3u8` playlist support | Built-in HLS support |
| Adaptive Bitrate | Automatic quality selection | Automatic quality selection |
| Fullscreen | Landscape via `setSystemUiVisibility` | Landscape via `supportedInterfaceOrientations` |
| PiP | PictureInPictureMode | AVPictureInPictureController |
| Progress Save | Room DB + API sync | CoreData + API sync |
| Resume Playback | Save position on pause/exit | Save position on scene phase change |
| Gesture Controls | Volume (left), brightness (right), seek (horizontal swipe) | Same gesture system |
| Lock Screen Controls | MediaSession + MediaStyle notification | MPNowPlayingInfoCenter + MPRemoteCommandCenter |
| Subtitle Tracks | WebVTT / SRT via ExoPlayer | WebVTT via AVPlayerItemLegibleOutput |
| DRM Support | Widevine Modular | FairPlay Streaming |
| Download Offline | DownloadManager + ExoPlayer cache | HLS offline download via AVAssetDownloadTask |

### 13.2 Player UI Components

- Play/Pause toggle (center overlay, fades after 3s)
- Seek bar with preview thumbnail on drag
- Episode navigation (prev/next buttons)
- Quality selector (auto / 1080p / 720p / 480p)
- Subtitle toggle + language selector
- Lock/unlock overlay for paid episodes
- Coin balance indicator (corner badge)

---

## 14. Mobile Payment Flow

### 14.1 Android

```
1. User taps coin package → POST /coins/purchase
2. API returns payment authorization URL
3. Open Payhub checkout in Chrome Custom Tab
4. User completes payment on Payhub page
5. Payhub redirects to callback URL
6. App intercepts callback via deep link (SOLOREEL://payment/verify?ref=XXX)
7. Return to coin shop with success state
8. Coin balance updates via API call
```

### 14.2 iOS

```
1. User taps coin package → POST /coins/purchase
2. API returns payment authorization URL
3. Open Payhub checkout in SFSafariViewController
4. User completes payment on Payhub page
5. Payhub redirects to callback URL
6. App intercepts callback via URL scheme / universal link
7. Dismiss SFSafariViewController
8. Return to coin shop with success state
9. Coin balance updates via API call
```

---

## 15. Mobile Build & Release

### 15.1 Android Release

```
1. Update versionCode + versionName in build.gradle.kts
2. Generate signed bundle: ./gradlew bundleRelease
3. Sign with upload key (Play Console)
4. Upload .aab to Google Play Console
5. Fill store listing (screenshots, description, privacy policy)
6. Submit for review
7. Rollout: 10% → 50% → 100% staged release
```

### 15.2 iOS Release

```
1. Update CFBundleVersion + CFBundleShortVersionString in Info.plist
2. Archive in Xcode: Product → Archive
3. Validate and upload to App Store Connect
4. Fill store listing (screenshots for all device sizes, description, privacy labels)
5. Add export compliance, content rating
6. Submit for TestFlight internal testing
7. Submit for App Review
8. Release: manual or phased (7-day rollout)
```

### 15.3 App Store Metadata

| Field | Content |
|-------|---------|
| App Name | SOLOREEL |
| Subtitle | Vertical Short Dramas |
| Description | Watch thousands of vertical short drama series across multiple genres... |
| Keywords | short drama, vertical series, episodes, binge, romance, thriller |
| Category | Entertainment |
| Age Rating | 12+ (mild themes) |
| Privacy Policy URL | https://SOLOREEL.com/privacy |
| Support URL | https://SOLOREEL.com/support |

### 15.4 CI/CD Pipeline

```
GitHub Actions Workflow:
1. On push to main:
   - Run linting (ktlint / SwiftLint)
   - Run unit tests
   - Build debug APK / run Xcode build
2. On tag (v1.0.0):
   - Build release .aab (Android)
   - Archive + export .ipa (iOS, manual signing)
   - Upload to Firebase App Distribution (internal testing)
   - Create GitHub release with changelog
```

---

## 16. Mobile App Icons & Assets

### 16.1 Required Icon Sizes

**Android (mipmap):**
- mdpi: 48×48, hdpi: 72×72, xhdpi: 96×96,
  xxhdpi: 144×144, xxxhdpi: 192×192
- Adaptive icon: foreground 108dp + background 108dp

**iOS (Assets.xcassets):**
- iPhone: 60×60 (@2x), 60×60 (@3x)
- iPad: 76×76 (@2x), 83.5×83.5 (@2x)
- App Store: 1024×1024

### 16.2 Launch Screen / Splash

- Static branded image with logo centered, black background
- Short animation (scale + fade) before entering main UI
- Duration: 1.5s Android, 2s iOS

---

## 17. Site Branding

### 10.1 Logo

- Upload via Admin → Settings → Branding card
- Accepted: PNG, JPG, WEBP (max 2MB)
- Stored at `assets/uploads/logo_*.{ext}`
- Displayed in: header nav, footer, login/register pages, admin sidebar
- Fallback: first letter of site title in red gradient box
- Remove option: reverts to letter fallback

### 10.2 Favicon

- Upload via Admin → Settings → Branding card
- Accepted: PNG (32x32 or 64x64, max 500KB)
- Linked as: `<link rel="icon">` + `<link rel="apple-touch-icon">`
- Fallback: dynamic GD-generated favicon with site initial

### 10.3 OG Image

- Social share preview image (1200x630px recommended)
- Used when individual pages don't have their own image

### 10.4 Cinematic Preloader

- Full-screen black overlay with logo pulse animation
- Three-dot bouncing loader
- Fades out after 600ms via Alpine.js transition
- CSS keyframe: `preloaderPulse` (scale 1 → 1.05 → 1)

---

## 18. User Management (Admin)

### 11.1 User List (`/admin/users`)

- Paginated table: username, email, role, status, coins, join date
- Actions: Edit, Login As

### 11.2 Edit User (`/admin/users/{id}/edit`)

- Editable: username, email, display name, role (user/admin/super_admin), status, coin balance
- Password change (auto-hashed with Argon2ID)
- Danger zone: Block/Unblock, Login As User, Delete

### 11.3 Block / Unblock

- Sets user `status` to `blocked` / `active`
- Blocked users cannot login

### 11.4 Login As User

- Admin session is replaced with the target user's session
- `_admin_impersonating` flag set for identification
- Enables admin to troubleshoot user issues

### 11.5 Delete User

- Removes user record via API
- Permanent operation with confirmation dialog

---

## 19. Security

### 12.1 CSRF Protection

- Token generation per session
- `csrfField()` outputs hidden input
- Validated on form submission

### 12.2 XSS Prevention

- `h()` for HTML context escaping
- `hAttr()` for attribute context escaping
- All user input sanitized before output

### 12.3 Brute Force Protection

- Configurable max attempts (default: 5)
- Configurable lockout duration (default: 15 min)
- Configurable reset window (default: 1 hour)
- Tracks by IP and username
- Email alerts for security events

### 12.4 IP Access Control

- Whitelist: only listed IPs can access admin
- Blacklist: blocked IPs cannot access site
- Admin management UI for both lists

### 12.5 Country Access Rules

- Allow/block by country code
- Admin management UI

### 12.6 Login Attempt Monitoring

- Full log of all login attempts (success/failure)
- IP, username, timestamp, user agent

---

## 20. Email System

### 13.1 SMTP Configuration

- Configurable via Admin → Settings → Email card
- Host, port, encryption (TLS/SSL/None), username, password, from address

### 13.2 Email Templates

- Admin-editable templates for: welcome, password reset, security alerts
- HTML body with variable placeholders

### 13.3 Email Queue

- Outgoing emails queued for async processing
- Status tracking: pending, sent, failed
- Cron job processes queue

---

## 21. Installation Wizard

### Stage 1 — License Validation
- Validates license key against `LICENSE_API_URL`
- Checks PHP version and required extensions

### Stage 2 — Database Configuration
- MySQL host, port, database name, username, password
- Connection test before proceeding
- Writes to `.env` file

### Stage 3 — Admin Account
- Admin email, username, display name, password
- Site title
- Creates admin user with super_admin role
- Seeds initial coin balance

### Stage 4 — Finalization
- Runs SQL schema files (in order):
  1. `001_initial_schema_mysql.sql`
  2. `002_payment_gateway.sql`
- Creates storage directories (cache, sessions, uploads, logs)
- Creates assets/uploads directory
- Writes `install.lock` to prevent re-installation
- Shows completion page with links

---

## 22. Responsive Design

### 15.1 Breakpoints

| Breakpoint | Target |
|-----------|--------|
| Default | Mobile (< 640px) |
| `sm` (640px) | Large phones |
| `md` (768px) | Tablets |
| `lg` (1024px) | Desktop |

### 15.2 Admin Layout

- **Desktop**: sidebar visible (256px), content fills remaining space
- **Mobile**: sidebar hidden off-screen (`-translate-x-full`), hamburger toggle shows it as overlay
- **Overlay**: dark backdrop with click-to-close
- **Tables**: horizontal scroll on mobile via `.table-responsive`
- **Forms**: field labels stack vertically on mobile

### 15.3 Main Layout

- **Header**: sticky with blur backdrop on scroll
- **Navigation**: horizontal on desktop, hidden behind hamburger on mobile
- **Search**: full-screen overlay on both
- **Footer**: 4-column grid on desktop, stacks on mobile
- **Series grid**: 2 cols mobile → 6 cols desktop
- **Banner**: height adapts (50vh mobile → 80vh desktop)

---

## 23. Admin Panel Features Summary

| Section | Features |
|---------|----------|
| **Dashboard** | Stats cards (series, episodes, users, views), quick links, system info |
| **Series** | List, create, edit, delete, cover upload, shelf assignment, status toggle |
| **Episodes** | List with series filter, create, edit, video upload, thumbnail upload |
| **Shelves** | Add/remove shelves with emoji and slug |
| **Banners** | Add banners with image upload, title, subtitle, link |
| **Blog** | Create, edit, delete posts with rich text |
| **Users** | List, edit (password, role, coins), block/unblock, delete, login-as |
| **Coins** | Transaction history, package management |
| **Security** | Brute force config, login attempts log, IP whitelist/blacklist, user locks, country rules |
| **Settings** | General (title, tagline, timezone, maintenance), Branding (logo, favicon, OG), SEO (meta, JSON-LD), Analytics (GA/GTM), Email (SMTP), Admin Profile (name, email, password) |
| **Payments** | Payhub API keys, mode, enable/disable |
| **Emails** | Templates editor, queue viewer |
| **Sitemap** | Manual regenerate, status display |

---

## 24. Backend API Specification

The PHP frontend communicates with a backend API at `API_BASE_URL/api/v1/`. All responses are JSON.

### 17.1 Key Endpoints

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/banners?active=true` | Active banners |
| GET | `/shelves?active=true` | Active shelves |
| GET | `/series?shelf={slug}&size=12` | Series by shelf |
| GET | `/series/{slug}/by-slug` | Series detail |
| GET | `/series/{id}/episodes` | Episodes for series |
| GET | `/episodes/{slug}/by-slug` | Episode detail |
| GET | `/search?q={query}` | Search |
| GET | `/blog?page=1&size=10` | Blog posts |
| GET | `/blog/{slug}/by-slug` | Blog post detail |
| GET | `/blog-categories` | Blog categories |
| GET | `/coin-packages` | Coin packages |
| POST | `/episodes/{id}/unlock` | Unlock episode |
| GET | `/user/profile` | User profile |
| PUT | `/user/profile` | Update profile |
| GET | `/user/watch-history` | Watch history |
| GET | `/user/favorites` | Favorites |
| POST/DELETE | `/user/favorites/{seriesId}` | Toggle favorite |
| GET/POST/PUT/DELETE | `/admin/*` | Admin CRUD operations |
| GET | `/admin/stats` | Dashboard statistics |
| GET | `/admin/settings` | Site settings |
| PUT | `/admin/settings` | Update settings |

### 17.2 Authentication

- Internal token sent as `X-Internal-Token` header
- Admin endpoints require admin-level API authentication

---

## 25. Deployment Checklist

### 18.1 Pre-Deployment

- [ ] Run schema migration SQL files in order
- [ ] Verify PHP 8.0+ with required extensions: PDO, MySQL, cURL, GD, mbstring, fileinfo
- [ ] Set correct permissions: `assets/uploads/` (775), `storage/` subdirectories (775)
- [ ] Configure `.env` with production values
- [ ] Set `APP_DEBUG=false` for production

### 18.2 File Upload

- Upload entire `web/` directory contents to `public_html/`
- Upload `schema/` to a non-public directory or run SQL manually
- Ensure `.htaccess` is uploaded (hidden files)

### 18.3 Post-Deployment

- [ ] Run installation wizard at `/install/`
- [ ] Configure SMTP settings
- [ ] Upload logo and favicon
- [ ] Configure SEO defaults
- [ ] Set up Payhub API keys
- [ ] Regenerate sitemap
- [ ] Test all public pages (home, series, episodes, login, register, payment)
- [ ] Test admin panel (`/admin`)
- [ ] Verify email delivery
- [ ] Verify payment flow (sandbox mode first)
- [ ] Remove `install/` directory for security (optional)

### 18.4 Security Hardening

- [ ] Remove `install/` directory after setup
- [ ] Block direct access to `.env` and `.sql` files (handled by `.htaccess`)
- [ ] Set strong JWT secret (auto-generated during install)
- [ ] Enable brute force protection with appropriate thresholds
- [ ] Configure IP whitelist for admin access (optional)
- [ ] Set `APP_DEBUG=false`
- [ ] Use HTTPS (Let's Encrypt / cPanel AutoSSL)

---

## 26. File Dependency Map

When making changes, these files reference each other:

```
index.php → .env, app/helpers/*, app/core/*, app/controllers/*, admin/controllers/*, app/config/routes.php
routes.php → app/core/Router.php
main.php (layout) → app/helpers/seo.php, app/helpers/url.php, app/core/Canonical.php, app/core/Session.php, app/core/AIVisibility.php
admin/layout.php → app/helpers/url.php, app/core/Session.php
Controllers (frontend) → app/core/ApiClient.php, app/core/Cache.php, app/core/Canonical.php, app/core/Session.php, app/core/Auth.php
AdminControllers → app/core/ApiClient.php, app/core/Cache.php, app/core/Session.php, app/core/Database.php, app/core/SitemapGenerator.php
UserCoinController → app/core/Session.php, app/core/PayhubGateway.php, app/core/Database.php
PaymentController → app/core/Session.php, app/core/PayhubGateway.php, app/core/Database.php
PayhubGateway → app/core/Database.php
Canonical.php → SchemaBuilder, SitemapGenerator, RobotsGenerator, AIVisibility, FaviconGenerator
```

---

## 27. Known Limitations & Future Roadmap

### Current Limitations

- Backend API must be running for dynamic content (series, episodes, search)
- Admin CRUD operations depend on the backend API
- No WebSocket/real-time notifications
- Tailwind via CDN (not built locally) — slight FOUC on slow connections
- File-based caching may degrade with very high traffic (> 100K daily visitors)
- No automated testing suite
- No CI/CD pipeline

### Planned Enhancements

- [ ] Build backend API server (Go/Node.js)
- [ ] Complete Android app (Kotlin/Jetpack Compose)
- [ ] Create iOS app (Swift/SwiftUI)
- [ ] Publish Android app to Google Play Store
- [ ] Publish iOS app to Apple App Store
- [ ] WebSocket integration for real-time coin balance updates
- [ ] Proper Tailwind build pipeline (PostCSS + purge)
- [ ] CDN for uploaded assets (S3, Cloudinary)
- [ ] Multi-language support (i18n)
- [ ] Rating/review system for series
- [ ] Push notifications (FCM + APNs)
- [ ] Referral system
- [ ] Affiliate program
- [ ] Advanced analytics dashboard
- [ ] A/B testing framework
- [ ] Automated test suite (PHPUnit, XCTest, JUnit)
- [ ] CI/CD pipeline (GitHub Actions)
- [ ] Docker production deployment
- [ ] SharePlay / group watching
- [ ] Siri Shortcuts / Google Assistant integration
- [ ] Home Screen widgets (iOS + Android)
- [ ] Apple Watch + Wear OS companion apps
- [ ] App Clips (iOS instant preview)
- [ ] Dynamic Island live activities (iOS)
