import SwiftUI

struct SearchView: View {
    @State private var query = ""
    @State private var results: [Series] = []
    @State private var isLoading = false
    @State private var requestSent = false
    @State private var showRequestSheet = false
    private var showNoResults: Bool { !isLoading && !query.isEmpty && results.isEmpty }

    var body: some View {
        NavigationStack {
            VStack(alignment: .leading, spacing: 0) {
                Text("Search").font(.title).bold().foregroundColor(.white).padding(.horizontal).padding(.top, 16)

                // Search bar
                HStack {
                    Image(systemName: "magnifyingglass").foregroundColor(Color(white: 0.4))
                    TextField("Search series...", text: $query).foregroundColor(.white)
                        .onChange(of: query) { _ in
                            requestSent = false
                            Task { try? await Task.sleep(nanoseconds: 400_000_000); await performSearch() }
                        }
                    if !query.isEmpty {
                        Button { query = ""; results = [] } label: { Image(systemName: "xmark.circle.fill").foregroundColor(Color(white: 0.4)) }
                    }
                }.padding(12).background(Color(white: 0.08)).cornerRadius(14).padding(.horizontal).padding(.vertical, 12)

                if isLoading {
                    Spacer()
                    ProgressView().tint(.red).frame(maxWidth: .infinity)
                    Spacer()
                } else if results.isEmpty && query.isEmpty {
                    // Idle state
                    VStack(spacing: 12) {
                        Spacer()
                        Text("🎬").font(.system(size: 52))
                        Text("Search for your favourite series").foregroundColor(Color(white: 0.35)).multilineTextAlignment(.center)
                        Spacer()
                    }.frame(maxWidth: .infinity)
                } else if showNoResults {
                    // No results state
                    VStack(spacing: 20) {
                        Spacer().frame(height: 32)
                        Text("🔍").font(.system(size: 52))
                        Text("No results for").foregroundColor(Color(white: 0.4))
                        Text("\"\(query)\"").font(.headline).bold().foregroundColor(.white)

                        VStack(spacing: 12) {
                            Text("Don't see what you want?").font(.headline).foregroundColor(.white)
                            Text("Request it and we'll notify you when it's available!").font(.subheadline).foregroundColor(Color(white: 0.55)).multilineTextAlignment(.center)
                            if requestSent {
                                Label("Request sent! We'll notify you.", systemImage: "checkmark.circle.fill").foregroundColor(Color(red: 0.29, green: 0.87, blue: 0.5)).fontWeight(.semibold)
                            } else {
                                Button { showRequestSheet = true } label: {
                                    Label("Request \"\(query.prefix(20))\"", systemImage: "plus.circle.fill")
                                        .fontWeight(.bold).frame(maxWidth: .infinity).frame(height: 48)
                                        .background(Color(red: 0.86, green: 0.15, blue: 0.15)).foregroundColor(.white).cornerRadius(12)
                                }
                            }
                        }.padding(20).background(Color(white: 0.08)).cornerRadius(16)
                        Spacer()
                    }.padding(.horizontal)
                } else {
                    ScrollView {
                        LazyVGrid(columns: [GridItem(.flexible()), GridItem(.flexible())], spacing: 12) {
                            ForEach(results) { s in
                                NavigationLink(destination: SeriesDetailView(slug: s.slug)) {
                                    SeriesCard(series: s)
                                }
                            }
                        }.padding(12)
                    }
                }
            }
            .background(Color(red: 0.04, green: 0.04, blue: 0.04))
            .preferredColorScheme(.dark)
        }
        .sheet(isPresented: $showRequestSheet) {
            SeriesRequestSheet(initialTitle: query, onSubmit: { title, desc, email in
                Task {
                    try? await APIClient.shared.createSeriesRequest(
                        title: title, description: desc.isEmpty ? nil : desc,
                        email: email.isEmpty ? nil : email,
                        guestId: TokenManager.shared.isGuest ? TokenManager.shared.guestId : nil
                    )
                    requestSent = true
                }
            })
        }
    }

    func performSearch() async {
        guard !query.isEmpty else { results = []; return }
        isLoading = true
        do { results = try await APIClient.shared.search(q: query) } catch { results = [] }
        isLoading = false
    }
}

struct SeriesCard: View {
    let series: Series
    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            ZStack(alignment: .topTrailing) {
                AsyncImage(url: URL(string: series.cover_image_url ?? "")) { phase in
                    switch phase {
                    case .success(let image): image.resizable().aspectRatio(contentMode: .fill)
                    default: Color(white: 0.12)
                    }
                }.frame(height: 200).clipped().cornerRadius(12)

                if let count = series.episode_count, count > 0 {
                    Text("\(count) EP").font(.caption2).bold().foregroundColor(.white).padding(.horizontal, 6).padding(.vertical, 2).background(Color.black.opacity(0.7)).cornerRadius(6).padding(6)
                }
            }
            Text(series.title).font(.caption).fontWeight(.medium).foregroundColor(.white).lineLimit(2)
            if let genre = series.genre { Text(genre).font(.caption2).foregroundColor(Color(white: 0.4)).lineLimit(1) }
        }
    }
}

struct SeriesRequestSheet: View {
    let initialTitle: String
    let onSubmit: (String, String, String) -> Void
    @Environment(\.dismiss) var dismiss
    @State private var title: String
    @State private var description = ""
    @State private var email = TokenManager.shared.email ?? ""
    @State private var isLoading = false

    init(initialTitle: String, onSubmit: @escaping (String, String, String) -> Void) {
        self.initialTitle = initialTitle; self.onSubmit = onSubmit
        _title = State(initialValue: initialTitle)
    }

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(alignment: .leading, spacing: 16) {
                    HStack { Text("📩").font(.system(size: 28)); Text("Request a Series").font(.title2).bold().foregroundColor(.white) }
                    Text("We'll notify you when it's available!").foregroundColor(Color(white: 0.55)).font(.subheadline)

                    VStack(alignment: .leading, spacing: 6) {
                        Text("Series Title *").font(.caption).foregroundColor(Color(white: 0.55))
                        TextField("", text: $title).textFieldStyle(.plain).foregroundColor(.white).padding(12).background(Color(white: 0.1)).cornerRadius(10).overlay(RoundedRectangle(cornerRadius: 10).stroke(Color(white: 0.25), lineWidth: 1))
                    }
                    VStack(alignment: .leading, spacing: 6) {
                        Text("Description (optional)").font(.caption).foregroundColor(Color(white: 0.55))
                        TextEditor(text: $description).foregroundColor(.white).frame(height: 80).padding(8).background(Color(white: 0.1)).cornerRadius(10).overlay(RoundedRectangle(cornerRadius: 10).stroke(Color(white: 0.25), lineWidth: 1))
                    }
                    if TokenManager.shared.isGuest {
                        VStack(alignment: .leading, spacing: 6) {
                            Text("Your Email (for notification)").font(.caption).foregroundColor(Color(white: 0.55))
                            TextField("", text: $email).textFieldStyle(.plain).foregroundColor(.white).padding(12).background(Color(white: 0.1)).cornerRadius(10).overlay(RoundedRectangle(cornerRadius: 10).stroke(Color(white: 0.25), lineWidth: 1)).keyboardType(.emailAddress).autocapitalization(.none)
                        }
                    }
                    HStack(spacing: 12) {
                        Button("Cancel") { dismiss() }.frame(maxWidth: .infinity).frame(height: 48).background(Color(white: 0.1)).foregroundColor(Color(white: 0.6)).cornerRadius(12)
                        Button {
                            guard !title.isEmpty else { return }
                            isLoading = true; onSubmit(title, description, email); dismiss()
                        } label: {
                            if isLoading { ProgressView().tint(.white) } else { Text("Send Request").bold() }
                        }.frame(maxWidth: .infinity).frame(height: 48).background(!title.isEmpty ? Color(red: 0.86, green: 0.15, blue: 0.15) : Color.gray).foregroundColor(.white).cornerRadius(12).disabled(title.isEmpty)
                    }
                }.padding(20)
            }.background(Color(red: 0.08, green: 0.08, blue: 0.08)).preferredColorScheme(.dark)
            .navigationTitle("").navigationBarTitleDisplayMode(.inline)
        }
    }
}
