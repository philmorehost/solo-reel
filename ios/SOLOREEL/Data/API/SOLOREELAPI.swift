import Foundation

// MARK: - Models
struct ApiResponse<T: Codable>: Codable { let status: Bool?; let data: T?; let message: String? }
struct LoginBody: Codable { let email: String; let password: String }
struct RegisterBody: Codable { let username: String; let email: String; let password: String; let display_name: String }
struct AuthResult: Codable { let user: User?; let token: String? }
struct Banner: Codable, Identifiable { let id: Int; let title: String?; let subtitle: String?; let image_url: String? }
struct Series: Codable, Identifiable { let id: Int; let title: String; let slug: String; let cover_image_url: String?; let synopsis: String?; let genre: String?; let status: String?; let episode_count: Int? }
struct Episode: Codable, Identifiable { let id: Int; let title: String; let slug: String; let series_id: Int?; let series_title: String?; let video_hls_url: String?; let thumbnail_url: String?; let is_free: Bool?; let coin_cost: Double?; let episode_number: Int?; let description: String? }
struct CoinPackage: Codable, Identifiable { let id: Int; let name: String; let coins: Int; let price: Double; let currency: String }
struct User: Codable { let id: Int; let username: String; let email: String; let display_name: String?; let coin_balance: Double?; let role: String? }

// MARK: - API Client
class APIClient {
    static let shared = APIClient()
    private let base = "https://soloshort.pmhserver.name.ng/api/v1/"
    var token: String?

    private func request<T: Codable>(_ path: String, method: String = "GET", body: Data? = nil) async throws -> T {
        var req = URLRequest(url: URL(string: base + path)!)
        req.httpMethod = method
        req.setValue("application/json", forHTTPHeaderField: "Content-Type")
        if let t = token { req.setValue("Bearer \(t)", forHTTPHeaderField: "Authorization") }
        req.httpBody = body
        let (data, _) = try await URLSession.shared.data(for: req)
        return try JSONDecoder().decode(ApiResponse<T>.self, from: data).data!
    }

    func login(email: String, password: String) async throws -> AuthResult {
        let body = try JSONEncoder().encode(LoginBody(email: email, password: password))
        return try await request("auth/login", method: "POST", body: body)
    }
    func register(username: String, email: String, password: String) async throws -> AuthResult {
        let body = try JSONEncoder().encode(RegisterBody(username: username, email: email, password: password, display_name: username))
        return try await request("auth/register", method: "POST", body: body)
    }
    func getBanners() async throws -> [Banner] { try await request("banners?active=true") }
    func getSeries() async throws -> [Series] { try await request("series?size=20") }
    func getSeriesDetail(slug: String) async throws -> Series { try await request("series/\(slug)/by-slug") }
    func getEpisodes(seriesId: Int) async throws -> [Episode] { try await request("series/\(seriesId)/episodes") }
    func getEpisode(slug: String) async throws -> Episode { try await request("episodes/\(slug)/by-slug") }
    func search(q: String) async throws -> [Series] { try await request("search?q=\(q.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? "")") }
    func getCoinPackages() async throws -> [CoinPackage] { try await request("coin-packages") }
    func getProfile() async throws -> User { try await request("user/profile") }
}

// MARK: - Token Manager
class TokenManager: ObservableObject {
    static let shared = TokenManager()
    @Published var isLoggedIn = false
    var token: String? {
        get { UserDefaults.standard.string(forKey: "token") }
        set { UserDefaults.standard.set(newValue, forKey: "token"); UserDefaults.standard.synchronize(); isLoggedIn = newValue != nil }
    }
    var email: String? {
        get { UserDefaults.standard.string(forKey: "email") }
        set { UserDefaults.standard.set(newValue, forKey: "email") }
    }
    var username: String? {
        get { UserDefaults.standard.string(forKey: "username") }
        set { UserDefaults.standard.set(newValue, forKey: "username") }
    }
    var coins: Int {
        get { UserDefaults.standard.integer(forKey: "coins") }
        set { UserDefaults.standard.set(newValue, forKey: "coins") }
    }
    func logout() {
        UserDefaults.standard.removeObject(forKey: "token")
        UserDefaults.standard.removeObject(forKey: "email")
        UserDefaults.standard.removeObject(forKey: "username")
        UserDefaults.standard.removeObject(forKey: "coins")
        isLoggedIn = false
    }
}
