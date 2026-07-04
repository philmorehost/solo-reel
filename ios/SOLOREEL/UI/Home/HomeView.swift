import SwiftUI

struct HomeView: View {
    @State private var series: [Series] = []

    var body: some View {
        NavigationView {
            ScrollView {
                VStack(alignment: .leading) {
                    Text("Featured Releases")
                        .font(.title2)
                        .bold()
                        .padding(.horizontal)

                    if series.isEmpty {
                        ProgressView()
                            .padding()
                    } else {
                        ScrollView(.horizontal, showsIndicators: false) {
                            HStack(spacing: 15) {
                                ForEach(series) { s in
                                    VStack(alignment: .leading) {
                                        Rectangle()
                                            .fill(Color.gray.opacity(0.3))
                                            .frame(width: 120, height: 180)
                                            .cornerRadius(8)
                                        Text(s.title)
                                            .font(.caption)
                                            .lineLimit(1)
                                    }
                                }
                            }
                            .padding(.horizontal)
                        }
                    }
                }
            }
            .navigationTitle("SOLOREEL")
            .onAppear {
                APIClient.shared.fetchSeries { result in
                    if case .success(let data) = result {
                        DispatchQueue.main.async {
                            self.series = data
                        }
                    }
                }
            }
        }
    }
}
