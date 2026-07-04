import SwiftUI

struct ContentView: View {
    @State private var showSplash = true

    var body: some View {
        if showSplash {
            SplashView(onFinished: { showSplash = false })
        } else {
            MainView()
        }
    }
}

struct MainView: View {
    var body: some View {
        ZStack {
            Color.black.ignoresSafeArea()
            VStack {
                Text("SOLOREEL")
                    .font(.largeTitle)
                    .fontWeight(.bold)
                    .foregroundColor(.red)

                Text("iOS Architecture Initialized")
                    .foregroundColor(.white)
                    .padding(.top, 8)
            }
        }
    }
}

struct ContentView_Previews: PreviewProvider {
    static var previews: some View {
        ContentView()
    }
}
