import SwiftUI

struct ProfileView: View {
    var body: some View {
        NavigationStack {
            VStack(spacing: 20) {
                Spacer().frame(height: 20)
                Circle().fill(Color.red).frame(width: 80, height: 80)
                    .overlay(Text(TokenManager.shared.username?.first?.uppercased() ?? "U").font(.largeTitle).bold().foregroundColor(.white))
                Text(TokenManager.shared.username ?? "User").font(.title2).bold()
                Text(TokenManager.shared.email ?? "").foregroundColor(.gray)
                Text("\(TokenManager.shared.coins) coins").foregroundColor(.yellow).fontWeight(.semibold)

                ProfileRow(icon: "clock", text: "Watch History")
                ProfileRow(icon: "heart", text: "Favorites")
                ProfileRow(icon: "gearshape", text: "Settings")

                Spacer()
                Button(action: { TokenManager.shared.logout() }) {
                    Text("Logout").fontWeight(.bold).frame(maxWidth: .infinity).frame(height: 48).background(Color.red).foregroundColor(.white).cornerRadius(12)
                }.padding(.horizontal, 32)
                Spacer()
            }.background(Color.black).preferredColorScheme(.dark)
        }
    }
}

struct ProfileRow: View {
    let icon: String; let text: String
    var body: some View {
        HStack {
            Image(systemName: icon).foregroundColor(.red).frame(width: 24)
            Text(text).foregroundColor(.white)
            Spacer()
            Image(systemName: "chevron.right").foregroundColor(.gray)
        }.padding().background(Color(white: 0.1)).cornerRadius(12).padding(.horizontal)
    }
}
