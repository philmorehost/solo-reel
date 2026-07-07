import SwiftUI

struct EditProfileView: View {
    @State private var username = ""
    @State private var displayName = ""
    @State private var password = ""
    @State private var confirmPassword = ""
    
    @State private var isLoading = false
    @State private var errorMessage: String? = nil
    @State private var successMessage: String? = nil
    
    @Environment(\.dismiss) private var dismiss
    
    var body: some View {
        ScrollView {
            VStack(spacing: 24) {
                if let err = errorMessage {
                    Text(err).foregroundColor(.white).padding().frame(maxWidth: .infinity).background(Color.red.opacity(0.8)).cornerRadius(8)
                }
                if let success = successMessage {
                    Text(success).foregroundColor(.white).padding().frame(maxWidth: .infinity).background(Color.green.opacity(0.8)).cornerRadius(8)
                }
                
                VStack(spacing: 16) {
                    AuthField(icon: "person", placeholder: "Username", text: $username)
                    AuthField(icon: "person.text.rectangle", placeholder: "Display Name", text: $displayName)
                    
                    VStack(alignment: .leading, spacing: 4) {
                        Text("Change Password (Optional)").font(.notoSans(size: 12, relativeTo: .caption)).foregroundColor(.gray)
                        AuthSecureField(icon: "lock", placeholder: "New Password", text: $password)
                        AuthSecureField(icon: "lock.shield", placeholder: "Confirm New Password", text: $confirmPassword)
                    }.padding(.top, 8)
                }
                
                Button(action: saveProfile) {
                    if isLoading {
                        ProgressView().progressViewStyle(CircularProgressViewStyle(tint: .white))
                    } else {
                        Text("Save Changes").fontWeight(.bold)
                    }
                }
                .frame(maxWidth: .infinity)
                .frame(height: 50)
                .background(Color(red: 0.86, green: 0.15, blue: 0.15))
                .foregroundColor(.white)
                .cornerRadius(12)
                .disabled(isLoading)
                
                Spacer()
            }
            .padding()
        }
        .background(Color(red: 0.04, green: 0.04, blue: 0.04).ignoresSafeArea())
        .navigationTitle("Edit Profile")
        .navigationBarTitleDisplayMode(.inline)
        .preferredColorScheme(.dark)
        .task {
            loadProfile()
        }
    }
    
    private func loadProfile() {
        if let user = TokenManager.shared.username {
            username = user
        }
        Task {
            do {
                let profile = try await APIClient.shared.getProfile()
                DispatchQueue.main.async {
                    self.username = profile.username
                    self.displayName = profile.display_name ?? profile.username
                }
            } catch {
                // Ignore load errors, we just use the cache
            }
        }
    }
    
    private func saveProfile() {
        guard !username.isEmpty else {
            errorMessage = "Username is required"
            return
        }
        
        if !password.isEmpty && password != confirmPassword {
            errorMessage = "Passwords do not match"
            return
        }
        
        isLoading = true
        errorMessage = nil
        successMessage = nil
        
        Task {
            do {
                try await APIClient.shared.updateProfile(username: username, displayName: displayName, password: password.isEmpty ? nil : password)
                
                // Update local token manager
                TokenManager.shared.username = username
                
                DispatchQueue.main.async {
                    self.isLoading = false
                    self.successMessage = "Profile updated successfully!"
                    self.password = ""
                    self.confirmPassword = ""
                    
                    // Optionally dismiss after a delay
                    DispatchQueue.main.asyncAfter(deadline: .now() + 1.5) {
                        dismiss()
                    }
                }
            } catch {
                DispatchQueue.main.async {
                    self.isLoading = false
                    self.errorMessage = error.localizedDescription
                }
            }
        }
    }
}
