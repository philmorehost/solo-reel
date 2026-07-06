# Google Sign-In Setup Guide (SOLOREEL)

Google login is fully wired in the website, Android app, and iOS app. It only needs
your Google OAuth credentials. Follow these steps once.

## 1. Create the OAuth credentials (Google Cloud Console)

1. Go to https://console.cloud.google.com/ and create (or select) a project, e.g. **SOLOREEL**.
2. Open **APIs & Services → OAuth consent screen**:
   - User type: **External** → Create.
   - App name: `SOLOREEL`, support email: your email, developer contact: your email.
   - Scopes: add `email` and `profile`.
   - Publish the app (or add your test accounts while in testing mode).
3. Open **APIs & Services → Credentials → Create Credentials → OAuth client ID**:

### a) Web application client (required — used by the website, iOS, and Android)
   - Application type: **Web application**
   - Name: `SOLOREEL Web`
   - Authorized redirect URIs — add:
     - `https://soloshort.pmhserver.name.ng/auth/google/callback`
   - Click Create and copy the **Client ID** and **Client Secret**.

### b) Android client (required for the native Android Google sheet)
   - Application type: **Android**
   - Package name: `com.soloreel.app`
   - SHA-1 fingerprint: run
     `keytool -list -v -keystore %USERPROFILE%\.android\debug.keystore -alias androiddebugkey -storepass android`
     (use your release keystore's SHA-1 for the Play Store build)
   - No client ID needs to be pasted anywhere for this one — it just has to exist
     so Google trusts the app's signature.

## 2. Configure the server (website + iOS)

In the database table `site_config`, set:

| setting_key            | setting_value            |
|------------------------|--------------------------|
| `google_auth_enabled`  | `1`                      |
| `google_client_id`     | *Web Client ID* from (a) |
| `google_client_secret` | *Client Secret* from (a) |

(If your admin panel has a Google Auth settings card, use it instead of SQL.)

This immediately enables:
- **Website**: the "Sign in with Google" flow at `/auth/google`.
- **iOS app**: the "Continue with Google" button — it opens the same server flow in a
  secure in-app browser and receives the login token back via the `soloreel://` URL
  scheme. **No GoogleService-Info.plist or Google SDK is needed.**

## 3. Configure the Android app

The Android app uses Google's Credential Manager and needs the **Web Client ID** (the
same value as `google_client_id` above, NOT the Android client ID):

1. Open `android/local.properties` (create it if missing) and add:
   ```
   GOOGLE_WEB_CLIENT_ID=1234567890-abcdefg.apps.googleusercontent.com
   ```
2. Rebuild the app. Until this value is set, the Google button shows a clear
   "not configured yet" message instead of failing silently.

For CI builds you can instead pass it as a Gradle property or the
`GOOGLE_WEB_CLIENT_ID` environment variable.

## 4. Test

- Website: visit `/login` → "Sign in with Google".
- iOS: tap **Continue with Google** → Google page opens in-app → returns signed in.
- Android: tap **Continue with Google** → native account sheet appears → signed in.

Troubleshooting:
- *"Google login is not enabled or configured"* → step 2 not done.
- *Android sheet never appears / developer error* → SHA-1 fingerprint missing or
  wrong package name in the Android OAuth client (step 1b), or the Web Client ID in
  `local.properties` is wrong.
- *redirect_uri_mismatch* → the redirect URI in step 1a must exactly match
  `https://<your-domain>/auth/google/callback`.
