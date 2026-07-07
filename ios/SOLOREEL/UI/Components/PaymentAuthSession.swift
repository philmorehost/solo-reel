import AuthenticationServices
import UIKit

/// Payment checkout runs in a real Safari browser session, not an embedded
/// WKWebView — payment gateways (Payhub/Paystack here) are notoriously
/// unreliable inside app WebViews (cookie/storage restrictions, popup-handling
/// quirks, fraud-detection heuristics that specifically target them).
/// ASWebAuthenticationSession uses Safari's actual engine, so behavior matches
/// "the website" exactly. Mirrors GoogleAuthSession's already-working pattern.
/// The server redirects to soloreel://payment-complete once the flow ends.
final class PaymentAuthSession: NSObject, ASWebAuthenticationPresentationContextProviding {
    static let shared = PaymentAuthSession()

    private var session: ASWebAuthenticationSession?

    func start(url: String, onSuccess: @escaping (String) -> Void, onDismiss: @escaping () -> Void) {
        guard let authURL = URL(string: url) else { onDismiss(); return }

        let session = ASWebAuthenticationSession(url: authURL, callbackURLScheme: "soloreel") { callbackURL, error in
            DispatchQueue.main.async {
                guard let callbackURL = callbackURL,
                      let components = URLComponents(url: callbackURL, resolvingAgainstBaseURL: false),
                      components.host == "payment-complete" else {
                    // Covers both explicit cancellation (user closed the sheet) and
                    // any other non-success outcome.
                    onDismiss()
                    return
                }
                let status = components.queryItems?.first(where: { $0.name == "status" })?.value ?? "unknown"
                let reference = components.queryItems?.first(where: { $0.name == "reference" })?.value ?? ""
                if status == "success" {
                    onSuccess(reference)
                } else {
                    onDismiss()
                }
            }
        }
        session.presentationContextProvider = self
        session.prefersEphemeralWebBrowserSession = false
        self.session = session
        session.start()
    }

    func cancel() {
        session?.cancel()
    }

    func presentationAnchor(for session: ASWebAuthenticationSession) -> ASPresentationAnchor {
        ASPresentationAnchor()
    }
}
