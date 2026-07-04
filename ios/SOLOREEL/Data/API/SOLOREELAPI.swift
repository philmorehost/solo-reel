import Foundation

struct ApiResponse<T: Codable>: Codable {
    let data: T?
    let error: String?
    let message: String?
    let token: String?
}

struct Series: Codable, Identifiable {
    let id: Int
    let title: String
    let slug: String
    let cover_image: String?
    let hero_image: String?
    let synopsis: String
}

struct Episode: Codable, Identifiable {
    let id: Int
    let title: String
    let slug: String
    let video_url: String
    let thumbnail_url: String
    let is_free: Bool
    let coin_cost: Double
}

struct CoinPackage: Codable, Identifiable {
    let id: Int
    let name: String
    let coins: Double
    let price: Double
    let currency: String
    let color_code: String
}

class APIClient {
    static let shared = APIClient()
    let baseURL = "https://soloshort.pmhserver.name.ng/api/v1/"

    func fetchSeries(completion: @escaping (Result<[Series], Error>) -> Void) {
        guard let url = URL(string: baseURL + "series") else { return }
        URLSession.shared.dataTask(with: url) { data, response, error in
            if let error = error { completion(.failure(error)); return }
            guard let data = data else { return }
            do {
                let result = try JSONDecoder().decode(ApiResponse<[Series]>.self, from: data)
                if let series = result.data {
                    completion(.success(series))
                }
            } catch {
                completion(.failure(error))
            }
        }.resume()
    }
}
