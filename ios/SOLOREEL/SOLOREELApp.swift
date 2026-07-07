import SwiftUI
import GoogleMobileAds
import AppTrackingTransparency

@main
struct SOLOREELApp: App {
    init() {
        GADMobileAds.sharedInstance().start(completionHandler: nil)
    }

    var body: some Scene {
        WindowGroup {
            ContentView()
                .preventScreenshot()
                .preferredColorScheme(.dark)
                .task {
                    // Required before AdMob can serve personalized ads on iOS 14.5+;
                    // without this prompt the app silently falls back to non-personalized
                    // ads and users never see the tracking-permission dialog at all.
                    if ATTrackingManager.trackingAuthorizationStatus == .notDetermined {
                        _ = await ATTrackingManager.requestTrackingAuthorization()
                    }
                }
        }
    }
}
