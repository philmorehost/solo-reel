import SwiftUI

// Shared form field styles used by EditProfileView and VerifyOTPView.
// Styled to match the plain dark fields in AuthView.

struct AuthField: View {
    let icon: String
    let placeholder: String
    @Binding var text: String

    var body: some View {
        HStack(spacing: 10) {
            Image(systemName: icon).foregroundColor(Color(white: 0.45)).frame(width: 20)
            TextField(placeholder, text: $text)
                .textFieldStyle(.plain)
                .foregroundColor(.white)
                .autocapitalization(.none)
        }
        .padding()
        .background(Color(white: 0.1))
        .cornerRadius(12)
    }
}

struct AuthSecureField: View {
    let icon: String
    let placeholder: String
    @Binding var text: String

    var body: some View {
        HStack(spacing: 10) {
            Image(systemName: icon).foregroundColor(Color(white: 0.45)).frame(width: 20)
            SecureField(placeholder, text: $text)
                .textFieldStyle(.plain)
                .foregroundColor(.white)
        }
        .padding()
        .background(Color(white: 0.1))
        .cornerRadius(12)
    }
}

struct CustomTextField: View {
    let placeholder: String
    @Binding var text: String

    var body: some View {
        TextField(placeholder, text: $text)
            .textFieldStyle(.plain)
            .padding()
            .background(Color(white: 0.1))
            .cornerRadius(12)
            .foregroundColor(.white)
            .multilineTextAlignment(.center)
            .autocapitalization(.none)
    }
}

extension Color {
    /// Creates a color from a hex string like "DC2626" or "#DC2626".
    init(hex: String) {
        let cleaned = hex.trimmingCharacters(in: CharacterSet.alphanumerics.inverted)
        var value: UInt64 = 0
        Scanner(string: cleaned).scanHexInt64(&value)
        let r, g, b: Double
        if cleaned.count == 6 {
            r = Double((value >> 16) & 0xFF) / 255
            g = Double((value >> 8) & 0xFF) / 255
            b = Double(value & 0xFF) / 255
        } else {
            r = 1; g = 1; b = 1
        }
        self.init(red: r, green: g, blue: b)
    }
}
