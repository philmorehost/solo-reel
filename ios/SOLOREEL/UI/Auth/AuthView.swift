import SwiftUI
import LocalAuthentication
import AuthenticationServices

struct AuthView: View {
    @State private var email = ""; @State private var password = ""; @State private var username = ""
    @State private var isLoading = false; @State private var error: String?
    @State private var isRegister = false
    
    @State private var showVerifyOTP = false
    @State private var verifyUserId: Int = 0
    @State private var verifyEmail: String = ""

    var body: some View {
        ZStack {
            Color.black.ignoresSafeArea()
            ScrollView {
            VStack(spacing: 20) {
                Spacer().frame(height: 60)
                Text("SOLOREEL").font(.system(size: 36, weight: .black)).foregroundColor(.red)
                Text("Vertical Short Dramas").font(.subheadline).foregroundColor(.gray)
                Spacer().frame(height: 20)

                VStack(spacing: 12) {
                    if isRegister {
                        TextField("Username", text: $username).textFieldStyle(.plain).padding().background(Color(white: 0.1)).cornerRadius(12).foregroundColor(.white).autocapitalization(.none)
                    }
                    TextField("Email", text: $email).textFieldStyle(.plain).padding().background(Color(white: 0.1)).cornerRadius(12).foregroundColor(.white).keyboardType(.emailAddress).autocapitalization(.none)
                    SecureField("Password", text: $password).textFieldStyle(.plain).padding().background(Color(white: 0.1)).cornerRadius(12).foregroundColor(.white)
                }.padding(.horizontal)

                Button(action: login) {
                    if isLoading { ProgressView().tint(.white) }
                    else { Text(isRegister ? "Create Account" : "Sign In").fontWeight(.bold).frame(maxWidth: .infinity) }
                }.frame(height: 50).frame(maxWidth: .infinity).background(Color.red).foregroundColor(.white).cornerRadius(12).padding(.horizontal)

                if let e = error { Text(e).font(.caption).foregroundColor(.red).multilineTextAlignment(.center).padding(.horizontal) }

                Button(isRegister ? "Have an account? Sign in" : "Don't have an account? Create one") {
                    withAnimation { isRegister.toggle() }
                }.foregroundColor(.red).padding(.top, 8)

                Button(action: loginWithGoogle) {
                    HStack(spacing: 12) {
                        Text("G").font(.system(size: 18, weight: .bold))
                        Text("Continue with Google").fontWeight(.medium)
                    }.frame(maxWidth: .infinity)
                }
                .frame(height: 50).foregroundColor(.white)
                .overlay(RoundedRectangle(cornerRadius: 12).stroke(Color.gray))
                .padding(.horizontal)

                if !isRegister {
                    Button(action: loginWithBiometric) { Label("Use Face ID / Touch ID", systemImage: "faceid").foregroundColor(.white).frame(maxWidth: .infinity) }
                        .frame(height: 50).overlay(RoundedRectangle(cornerRadius: 12).stroke(Color.gray)).padding(.horizontal)
                }

                Button("Continue as Guest") {
                    TokenManager.shared.continueAsGuest()
                }.foregroundColor(Color(white: 0.6)).padding(.top, 12)

                Spacer().frame(height: 40)
            }
            }
        }
        .fullScreenCover(isPresented: $showVerifyOTP) {
            VerifyOTPView(
                userId: verifyUserId,
                email: verifyEmail,
                onVerifySuccess: {
                    showVerifyOTP = false
                    // The API client token will be set in the view model, just need to update TokenManager login state
                    TokenManager.shared.isLoggedIn = true
                }
            )
        }
    }

    func applyAuth(token: String?, email authEmail: String?, username authUsername: String?, coins: Double?) {
        TokenManager.shared.email = authEmail
        TokenManager.shared.username = authUsername
        if let c = coins { TokenManager.shared.coins = c }
        TokenManager.shared.token = token // setting the token flips isLoggedIn → ContentView shows the app
    }

    func login() {
        isLoading = true; error = nil
        Task {
            do {
                if isRegister {
                    let r = try await APIClient.shared.register(username: username, email: email, password: password)
                    await MainActor.run {
                        isLoading = false
                        if r.requires_verification == true {
                            verifyUserId = r.user_id ?? 0
                            verifyEmail = email
                            showVerifyOTP = true
                        } else {
                            applyAuth(token: r.token, email: email, username: r.user?.username ?? username, coins: r.user?.coin_balance)
                        }
                    }
                } else {
                    let r = try await APIClient.shared.login(email: email, password: password)
                    await MainActor.run {
                        isLoading = false
                        applyAuth(token: r.token, email: email, username: r.user?.username, coins: r.user?.coin_balance)
                    }
                }
            } catch { await MainActor.run { isLoading = false; self.error = error.localizedDescription } }
        }
    }

    func loginWithGoogle() {
        error = nil
        GoogleAuthSession.shared.start { result in
            DispatchQueue.main.async {
                switch result {
                case .success(let auth):
                    applyAuth(token: auth.token, email: auth.email, username: auth.username, coins: auth.coins)
                case .failure(let err):
                    // User closing the sheet is not an error worth showing
                    if let authError = err as? ASWebAuthenticationSessionError, authError.code == .canceledLogin { return }
                    self.error = err.localizedDescription
                }
            }
        }
    }

    func loginWithBiometric() {
        let ctx = LAContext()
        ctx.evaluatePolicy(.deviceOwnerAuthenticationWithBiometrics, localizedReason: "Log in to SOLOREEL") { success, err in
            if success { DispatchQueue.main.async { TokenManager.shared.isLoggedIn = true } }
        }
    }
}

// MARK: - Google login via the site's OAuth flow (no Google SDK needed).
// Opens /auth/google?mobile=1 in a secure web session; the server completes the
// OAuth dance with the credentials configured in the admin panel and redirects
// back to soloreel://google-auth?token=<JWT>.
final class GoogleAuthSession: NSObject, ASWebAuthenticationPresentationContextProviding {
    static let shared = GoogleAuthSession()

    struct AuthPayload { let token: String; let email: String?; let username: String?; let coins: Double? }

    private var session: ASWebAuthenticationSession?

    func start(completion: @escaping (Result<AuthPayload, Error>) -> Void) {
        let authURL = URL(string: "https://soloshort.pmhserver.name.ng/auth/google?mobile=1")!
        let session = ASWebAuthenticationSession(url: authURL, callbackURLScheme: "soloreel") { callbackURL, error in
            if let error = error { completion(.failure(error)); return }
            guard let url = callbackURL,
                  let components = URLComponents(url: url, resolvingAgainstBaseURL: false),
                  let items = components.queryItems else {
                completion(.failure(NSError(domain: "GoogleAuth", code: 0, userInfo: [NSLocalizedDescriptionKey: "Google sign-in did not complete."])))
                return
            }
            func value(_ name: String) -> String? { items.first(where: { $0.name == name })?.value }
            if let serverError = value("error") {
                completion(.failure(NSError(domain: "GoogleAuth", code: 1, userInfo: [NSLocalizedDescriptionKey: serverError])))
                return
            }
            guard let token = value("token"), !token.isEmpty else {
                completion(.failure(NSError(domain: "GoogleAuth", code: 2, userInfo: [NSLocalizedDescriptionKey: "No token returned from server."])))
                return
            }
            completion(.success(AuthPayload(
                token: token,
                email: value("email"),
                username: value("username"),
                coins: value("coin_balance").flatMap(Double.init)
            )))
        }
        session.presentationContextProvider = self
        session.prefersEphemeralWebBrowserSession = false
        self.session = session
        session.start()
    }

    func presentationAnchor(for session: ASWebAuthenticationSession) -> ASPresentationAnchor {
        ASPresentationAnchor()
    }
}
