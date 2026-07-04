import SwiftUI

struct AuthView: View {
    var body: some View {
        VStack(spacing: 20) {
            Text("Sign in to SOLOREEL")
                .font(.title)
                .fontWeight(.bold)

            TextField("Email", text: .constant(""))
                .textFieldStyle(RoundedBorderTextFieldStyle())
                .padding(.horizontal)

            SecureField("Password", text: .constant(""))
                .textFieldStyle(RoundedBorderTextFieldStyle())
                .padding(.horizontal)

            Button(action: {
                // Login action
            }) {
                Text("Login")
                    .foregroundColor(.white)
                    .frame(maxWidth: .infinity)
                    .padding()
                    .background(Color.red)
                    .cornerRadius(8)
                    .padding(.horizontal)
            }

            Button(action: {
                // Google Auth implementation stub
            }) {
                Text("Sign in with Google")
                    .foregroundColor(.black)
                    .frame(maxWidth: .infinity)
                    .padding()
                    .background(Color.white)
                    .cornerRadius(8)
                    .padding(.horizontal)
            }
        }
    }
}
