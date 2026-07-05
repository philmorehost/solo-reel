import SwiftUI
import LocalAuthentication

struct AuthView: View {
    @State private var email = ""; @State private var password = ""; @State private var username = ""
    @State private var isLoading = false; @State private var error: String?
    @State private var isRegister = false

    var body: some View {
        ZStack {
            Color.black.ignoresSafeArea()
            VStack(spacing: 20) {
                Spacer()
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
                }.frame(height: 50).background(Color.red).foregroundColor(.white).cornerRadius(12).padding(.horizontal)

                if let e = error { Text(e).font(.caption).foregroundColor(.red) }

                Button(isRegister ? "Have an account? Sign in" : "Don't have an account? Create one") {
                    withAnimation { isRegister.toggle() }
                }.foregroundColor(.red).padding(.top, 8)

                if !isRegister {
                    Button(action: loginWithBiometric) { Label("Use Face ID / Touch ID", systemImage: "faceid").foregroundColor(.white) }
                        .frame(height: 44).padding(.horizontal).overlay(RoundedRectangle(cornerRadius: 12).stroke(Color.gray))
                }
                Spacer()
            }
        }
    }

    func login() {
        isLoading = true; error = nil
        Task {
            do {
                if isRegister {
                    _ = try await APIClient.shared.register(username: username, email: email, password: password)
                } else {
                    let r = try await APIClient.shared.login(email: email, password: password)
                    await MainActor.run {
                        TokenManager.shared.token = r.token
                        TokenManager.shared.email = email
                        TokenManager.shared.username = r.user?.username
                        isLoading = false
                    }
                }
            } catch { await MainActor.run { isLoading = false; self.error = error.localizedDescription } }
        }
    }

    func loginWithBiometric() {
        let ctx = LAContext()
        ctx.evaluatePolicy(.deviceOwnerAuthenticationWithBiometrics, localizedReason: "Log in to SOLOREEL") { success, err in
            if success { TokenManager.shared.isLoggedIn = true }
        }
    }
}
