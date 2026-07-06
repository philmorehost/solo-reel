# AdMob Setup Guide (SOLOREEL)

Rewarded ads ("Watch Ad to Unlock") are fully wired in both apps using Google's
public **test** IDs, so ads already show up and work end-to-end in development.
Follow these steps once you're ready to earn real revenue.

## 1. Create your AdMob account and app entries

1. Go to https://apps.admob.com/ and sign in / create an account.
2. **Apps → Add app** — add SOLOREEL for **Android** and again for **iOS**
   (two separate app entries, each gets its own App ID).
3. For each app, go to **Ad units → Add ad unit → Rewarded**, name it e.g.
   "Episode Unlock", and copy the **Ad unit ID**.
4. Copy each platform's **App ID** too (Apps → your app → App settings).

## 2. Enter your IDs in Admin → Settings → Ads

- **Ad unit IDs** (`admob_android_rewarded_unit_id` / `admob_ios_rewarded_unit_id`)
  are fetched by both apps at startup from `/api/v1/ads-config` — paste them into
  the admin Ads card and both apps pick them up automatically on next launch,
  **no app rebuild needed**.
- **App IDs** (`admob_android_app_id` / `admob_ios_app_id`) are also saved in the
  same admin card for reference, but **cannot** be changed at runtime — Google
  requires the App ID to be baked into the app at build time (Android manifest
  meta-data, iOS `Info.plist`). To apply a real App ID you must still update the
  files below and rebuild/resubmit the app once:
  - `android/app/src/main/AndroidManifest.xml`: replace the test value of
    `com.google.android.gms.ads.APPLICATION_ID`.
  - `ios/SOLOREEL/Info.plist`: replace the test value of `GADApplicationIdentifier`.
  - Double-check the `SKAdNetworkItems` list in `Info.plist` against Google's
    current published list (https://developers.google.com/admob/ios/sk-ad-network)
    before release — ad networks are occasionally added/retired.

## 4. Testing

- While using test IDs, ads are clearly labeled "Test Ad" — this is expected and
  confirms the integration works; it does **not** mean real ads will show.
- After swapping to real IDs, add your device as a test device
  (Android: `RequestConfiguration` test device list; iOS: `GADMobileAds.sharedInstance().requestConfiguration.testDeviceIdentifiers`)
  while verifying in production builds, so you don't accidentally generate
  invalid traffic on your own account.

## 5. Where "unlock with ad" is enforced

- The reward only unlocks the episode after the ad SDK's reward callback fires
  (`RewardedAdManager.showAd(onRewarded: ...)` on both platforms) — dismissing
  the ad early never calls the backend unlock endpoint.
- Admins choose per-episode whether unlocking requires coins, an ad, or either,
  via the `unlock_method` field on the episode (Admin → Episodes).
