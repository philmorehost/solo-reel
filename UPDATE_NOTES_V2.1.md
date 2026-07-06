# SOLOREEL v2.1 — Update Notes & Deployment Checklist

This update fixes the reported app issues and adds the series-request notification
system. Changes span the website (`web/`), Android (`android/`), and iOS (`ios/`).

## What was fixed / added

1. **Series cover images now display in the apps.** The API returns full
   `https://...` URLs for series covers, episode thumbnails, and banners
   (previously relative paths the apps could not load).
2. **Search page lists all series.** Opening Search shows the entire catalogue;
   typing filters the list live (400ms debounce) in both apps. The `/api/v1/search`
   endpoint returns all series when the query is empty.
3. **Coin purchases work for guests and registered users.**
   - New hosted mobile checkout page (`/pay/checkout?reference=...`) renders the
     Payhub popup inside the app WebView; the API now returns `authorization_url`.
   - Implemented the missing `guest-purchase` and `payment/verify` API endpoints.
     Verified payments credit coins directly (registered → `users.coin_balance`,
     guests → `guest_wallets.coin_balance`).
   - iOS bug fixed: the auth token was never attached to API requests (caused
     "HTTP 401 Unauthorized"). iOS also gained a "Continue as Guest" mode.
4. **Login, registration, and Google login fixed.**
   - The auth API now returns a consistent `{status, data: {token, user}}` envelope
     (Android's parser required it — login always "failed" before).
   - Registration returns a token → new users land straight in their account.
   - Android: successful login now navigates to Home (callback was never fired);
     Google button uses a configurable Web Client ID.
   - iOS: added "Continue with Google" via the server's OAuth flow (no Google SDK
     needed); login state now survives app restarts.
   - See **GOOGLE_SIGNIN_SETUP.md** for the one-time credential setup.
5. **Series requests + notifications.**
   - New admin page **Admin → Series Requests**: list with request counts, 🔥 HOT
     badges, status filter, and a "Mark Available" action with a series picker.
   - Marking a request available automatically flags it as a hot request, queues
     email notifications, and creates in-app notifications for every requester
     (registered and guest).
   - Both apps show a notification bell with an unread badge on Home, a
     notifications inbox, and post a phone notification for new items when the app
     opens (no Firebase account required).
   - The mark-available API is now admin-only (was previously unauthenticated).
6. **Missing API endpoints implemented** (previously routed but fatal):
   `series/{id}/episodes`, `episodes/{slug}/by-slug`, `shelves`, user
   watch-history/favorites, `coins/guest-purchase`, `payment/verify`.

## Deployment steps

1. **Upload the updated `web/` files** to the server (controllers, templates,
   routes, admin files listed in git).
2. **Run the new migration** against the database:
   ```sql
   SOURCE web/schema/012_payments_and_notifications.sql;
   ```
   (Adds `payment_transactions.guest_id`, makes `user_id` nullable, creates the
   `notifications` table.)
3. **Cron**: make sure the email queue processor runs so "series available" emails
   go out:
   ```
   */5 * * * * php /path/to/web/cron/process-email-queue.php
   ```
4. **Google login**: follow `GOOGLE_SIGNIN_SETUP.md` (server `site_config` values +
   Android `local.properties`).
5. **Android**: rebuild (`./gradlew assembleRelease`). New: `POST_NOTIFICATIONS`
   permission, notifications screen, guest checkout.
6. **iOS**: regenerate the Xcode project if you use XcodeGen (`xcodegen generate`
   inside `ios/`), then build in Xcode. New files are under
   `SOLOREEL/UI/Notifications/`; `Info.plist` gained the `soloreel://` URL scheme.

## Quick smoke test after deploy

- `GET /api/v1/series` → every `cover_image_url` starts with `https://`.
- `GET /api/v1/search` (no `q`) → full series list.
- `POST /api/v1/auth/login` → response contains `status: true` and `data.token`.
- `POST /api/v1/coins/guest-purchase` with `{"package_id":1,"guest_id":"test-1"}`
  → returns an `authorization_url`; open it → Payhub popup appears.
- Admin → Series Requests → Mark Available → requester gets an email (queued) and
  an entry in `notifications`; the request shows the HOT badge.
