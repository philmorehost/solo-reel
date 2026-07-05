import SwiftUI

struct SearchView: View {
    @State private var query = ""
    @State private var results: [Series] = []

    var body: some View {
        NavigationStack {
            VStack {
                HStack {
                    Image(systemName: "magnifyingglass").foregroundColor(.gray)
                    TextField("Search series...", text: $query).foregroundColor(.white)
                        .onChange(of: query) { _ in Task { try? await Task.sleep(nanoseconds: 400_000_000); await search() } }
                }.padding().background(Color(white: 0.1)).cornerRadius(12).padding(.horizontal)

                ScrollView {
                    LazyVGrid(columns: [GridItem(.flexible()), GridItem(.flexible())], spacing: 12) {
                        ForEach(results) { s in
                            NavigationLink(destination: SeriesDetailView(slug: s.slug)) {
                                VStack {
                                    AsyncImage(url: URL(string: s.cover_image_url ?? "")) { phase in
                                        (phase.image?.resizable() ?? Color.gray).frame(height: 180).cornerRadius(12)
                                    }
                                    Text(s.title).font(.caption).foregroundColor(.white).lineLimit(2)
                                }
                            }
                        }
                    }.padding()
                }
            }.background(Color.black).preferredColorScheme(.dark)
        }
    }
    func search() async {
        guard !query.isEmpty else { results = []; return }
        do { results = try await APIClient.shared.search(q: query) } catch {}
    }
}
