import SwiftUI

struct SplashView: View {
    @State private var opacity: Double = 0
    @State private var scale: CGFloat = 0.6
    @State private var rotation: Double = 0
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
                    .frame(width: 160, height: 160)
                    .scaleEffect(scale)
                    .opacity(opacity)
                    .rotationEffect(.degrees(isAnimating ? rotation : 0))
                    .animation(
                        .linear(duration: 2)
                        .repeatForever(autoreverses: false),
                        value: rotation
                    )

                Text("SOLOREEL")
                    .font(.system(size: 20, weight: .bold))
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
                rotation = 360
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
