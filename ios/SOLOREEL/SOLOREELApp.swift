import SwiftUI
import GoogleMobileAds

@main
struct SOLOREELApp: App {
    init() {
        GADMobileAds.sharedInstance().start(completionHandler: nil)
    }

    var body: some Scene {
        WindowGroup {
            ContentView()
                .preferredColorScheme(.dark)
        }
    }
}
