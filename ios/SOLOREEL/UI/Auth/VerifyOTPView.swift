import SwiftUI

struct VerifyOTPView: View {
    @Environment(\.presentationMode) var presentationMode
    @StateObject private var viewModel: VerifyOTPViewModel
    var onVerifySuccess: () -> Void
    
    init(userId: Int, email: String, onVerifySuccess: @escaping () -> Void) {
        _viewModel = StateObject(wrappedValue: VerifyOTPViewModel(userId: userId, email: email))
        self.onVerifySuccess = onVerifySuccess
    }
    
    var body: some View {
        ZStack {
            LinearGradient(gradient: Gradient(colors: [Color(hex: "0A0A0A"), Color(hex: "1A1A1A")]), startPoint: .top, endPoint: .bottom)
                .ignoresSafeArea()
            
            VStack(spacing: 24) {
                Spacer().frame(height: 60)
                
                VStack(spacing: 8) {
                    Text("SOLOREEL")
                        .font(.notoSans(size: 28, weight: .black))
                        .foregroundColor(Color(hex: "DC2626"))
                    Text("Verify Your Account")
                        .font(.notoSans(size: 14))
                        .foregroundColor(.gray)
                }
                
                Text("Enter the 6-digit code sent to \(viewModel.email)")
                    .font(.notoSans(size: 14))
                    .foregroundColor(.white)
                    .multilineTextAlignment(.center)
                    .padding(.horizontal, 32)
                
                VStack(spacing: 16) {
                    CustomTextField(placeholder: "OTP Code", text: $viewModel.otp)
                        .keyboardType(.numberPad)
                    
                    if let error = viewModel.error {
                        Text(error)
                            .foregroundColor(Color(hex: "EF4444"))
                            .font(.notoSans(size: 13))
                            .multilineTextAlignment(.center)
                    }
                    if let success = viewModel.success {
                        Text(success)
                            .foregroundColor(Color(hex: "22C55E"))
                            .font(.notoSans(size: 13))
                            .multilineTextAlignment(.center)
                    }
                    if let resendSuccess = viewModel.resendSuccess {
                        Text(resendSuccess)
                            .foregroundColor(Color(hex: "22C55E"))
                            .font(.notoSans(size: 13))
                            .multilineTextAlignment(.center)
                    }
                }
                .padding(.horizontal, 24)
                
                Button(action: {
                    viewModel.verify(onSuccess: onVerifySuccess)
                }) {
                    ZStack {
                        RoundedRectangle(cornerRadius: 8)
                            .fill(viewModel.isLoading ? Color(hex: "DC2626").opacity(0.7) : Color(hex: "DC2626"))
                            .frame(height: 50)
                        
                        if viewModel.isLoading {
                            ProgressView()
                                .progressViewStyle(CircularProgressViewStyle(tint: .white))
                        } else {
                            Text("Verify")
                                .font(.notoSans(size: 16, weight: .bold))
                                .foregroundColor(.white)
                        }
                    }
                }
                .padding(.horizontal, 24)
                .disabled(viewModel.isLoading)
                
                Button(action: {
                    viewModel.resend()
                }) {
                    Text("Resend Code")
                        .foregroundColor(Color(hex: "DC2626"))
                        .font(.notoSans(size: 14))
                }
                .padding(.top, 8)
                
                Spacer()
                
                Button(action: { presentationMode.wrappedValue.dismiss() }) {
                    Text("Back to Login")
                        .foregroundColor(.gray)
                        .font(.notoSans(size: 14))
                }
                .padding(.bottom, 24)
            }
        }
        .navigationBarHidden(true)
    }
}
