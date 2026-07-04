import SwiftUI

struct ContentView: View {
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
