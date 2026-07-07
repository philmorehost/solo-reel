import SwiftUI

struct SplashView: View {
    @State private var opacity: Double = 0
    @State private var scale: CGFloat = 0.6
    @State private var glow: CGFloat = 0.35
    @State private var isAnimating = false
    var onFinished: () -> Void

    var body: some View {
        ZStack {
            LinearGradient(
                colors: [
                    Color(red: 0.04, green: 0.04, blue: 0.04),
                    Color(red: 0.07, green: 0.07, blue: 0.07),
                    Color(red: 0.04, green: 0.04, blue: 0.04)
                ],
                startPoint: .top,
                endPoint: .bottom
            )
            .ignoresSafeArea()

            VStack(spacing: 24) {
                Image("logo_splash")
                    .resizable()
                    .aspectRatio(contentMode: .fit)
                    .frame(width: 240, height: 240)
                    .scaleEffect(scale)
                    .opacity(opacity)
                    .shadow(color: Color(red: 0.86, green: 0.15, blue: 0.15).opacity(isAnimating ? glow : 0), radius: 30)
                    .shadow(color: .white.opacity(isAnimating ? glow * 0.5 : 0), radius: 16)
                    .animation(
                        .easeInOut(duration: 1.2)
                        .repeatForever(autoreverses: true),
                        value: glow
                    )

                Text("SOLOREEL")
                    .font(.notoSans(size: 20, weight: .bold))
                    .foregroundColor(.white.opacity(0.6))
                    .opacity(opacity)
            }
        }
        .onAppear {
            withAnimation(.easeOut(duration: 0.8)) {
                opacity = 1
                scale = 1
            }

            DispatchQueue.main.asyncAfter(deadline: .now() + 0.6) {
                isAnimating = true
                glow = 1.0
            }

            DispatchQueue.main.asyncAfter(deadline: .now() + 2.2) {
                withAnimation(.easeIn(duration: 0.4)) {
                    opacity = 0
                    scale = 1.2
                }
                DispatchQueue.main.asyncAfter(deadline: .now() + 0.4) {
                    onFinished()
                }
            }
        }
    }
}
