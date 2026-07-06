import Foundation

@MainActor
class VerifyOTPViewModel: ObservableObject {
    @Published var otp: String = ""
    @Published var isLoading = false
    @Published var error: String?
    @Published var success: String?
    @Published var resendSuccess: String?
    
    let userId: Int
    let email: String
    
    init(userId: Int, email: String) {
        self.userId = userId
        self.email = email
    }
    
    func verify(onSuccess: @escaping () -> Void) {
        guard otp.count == 6 else {
            self.error = "Enter a valid 6-digit OTP"
            return
        }
        isLoading = true
        error = nil
        resendSuccess = nil
        
        Task {
            do {
                let result = try await APIClient.shared.verifyOTP(userId: userId, otp: otp, guestId: TokenManager.shared.guestId)
                if let token = result.token {
                    APIClient.shared.token = token
                    self.success = "Account verified!"
                    onSuccess()
                } else {
                    self.error = "Verification failed"
                }
            } catch {
                self.error = error.localizedDescription
            }
            isLoading = false
        }
    }
    
    func resend() {
        isLoading = true
        error = nil
        resendSuccess = nil
        
        Task {
            do {
                try await APIClient.shared.resendOTP(email: email)
                self.resendSuccess = "OTP resent successfully"
            } catch {
                self.error = error.localizedDescription
            }
            isLoading = false
        }
    }
}
