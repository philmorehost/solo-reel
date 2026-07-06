import Foundation

// MARK: - Models
struct ApiResponse<T: Codable>: Codable { let status: Bool?; let data: T?; let message: String?; let error: String? }
struct LoginBody: Codable { let email: String; let password: String }
struct RegisterBody: Codable { let username: String; let email: String; let password: String; let display_name: String }
struct AuthResult: Codable { let user: User?; let token: String? }
struct GuestInitBody: Codable { let guest_id: String }
struct GuestWallet: Codable { let guest_id: String; let coin_balance: Double }
struct SeriesRequestBody: Codable { let title: String; let description: String?; let email: String?; let guest_id: String? }

struct Banner: Codable, Identifiable {
    let id: Int; let title: String?; let subtitle: String?; let image_url: String?; let link_url: String?
}
struct Series: Codable, Identifiable {
    let id: Int; let title: String; let slug: String
    let cover_image_url: String?; let synopsis: String?; let genre: String?; let status: String?; let episode_count: Int?
    enum CodingKeys: String, CodingKey {
        case id, title, slug, synopsis, genre, status, episode_count
        case cover_image_url = "cover_image_url"
    }
}
struct Episode: Codable, Identifiable {
    let id: Int; let title: String; let slug: String; let series_id: Int?; let series_title: String?
    let video_hls_url: String?; let thumbnail_url: String?; let is_free: Bool?; let coin_cost: Double?
    let episode_number: Int?; let description: String?; let video_duration_seconds: Int?
}
struct CoinPackage: Codable, Identifiable { let id: Int; let name: String; let coins: Int; let price: Double; let currency: String }
struct User: Codable {
    let id: Int; let username: String; let email: String; let display_name: String?
    let coin_balance: Double?; let role: String?
    let bonus_coins: Double?; let bonus_expires_at: String?
}
struct WatchHistoryItem: Codable, Identifiable { let id: Int; let series_title: String?; let episode_title: String?; let thumbnail_url: String?; let slug: String?; let watched_at: String?; let progress_seconds: Int? }
struct WeeklyBonusStatus: Codable { let bonus_coins: Double; let bonus_expires_at: String?; let weekly_amount: Double }
struct PaymentInit: Codable { let authorization_url: String?; let reference: String? }

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
        let decoded = try JSONDecoder().decode(ApiResponse<T>.self, from: data)
        guard let responseData = decoded.data else {
            throw NSError(domain: "APIError", code: 0, userInfo: [NSLocalizedDescriptionKey: decoded.error ?? decoded.message ?? "Unknown API error"])
        }
        return responseData
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
    func getSeries(shelf: String? = nil) async throws -> [Series] {
        let q = shelf.map { "shelf=\($0)&" } ?? ""
        return try await request("series?\(q)size=20")
    }
    func getSeriesDetail(slug: String) async throws -> Series { try await request("series/\(slug)/by-slug") }
    func getEpisodes(seriesId: Int) async throws -> [Episode] { try await request("series/\(seriesId)/episodes") }
    func getEpisode(slug: String) async throws -> Episode { try await request("episodes/\(slug)/by-slug") }
    func search(q: String) async throws -> [Series] { try await request("search?q=\(q.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? "")") }
    func getCoinPackages() async throws -> [CoinPackage] { try await request("coin-packages") }
    func getProfile() async throws -> User { try await request("user/profile") }
    func getWatchHistory() async throws -> [WatchHistoryItem] { try await request("user/watch-history") }
    func getFavorites() async throws -> [Series] { try await request("user/favorites") }
    func getBonusStatus() async throws -> WeeklyBonusStatus { try await request("user/bonus-status") }
    func initGuest(guestId: String) async throws -> GuestWallet {
        let body = try JSONEncoder().encode(GuestInitBody(guest_id: guestId))
        return try await request("guest/init", method: "POST", body: body)
    }
    func purchaseCoins(packageId: Int) async throws -> PaymentInit {
        let body = try JSONEncoder().encode(["package_id": packageId])
        return try await request("coins/purchase", method: "POST", body: body)
    }
    func createSeriesRequest(title: String, description: String?, email: String?, guestId: String?) async throws {
        let body = try JSONEncoder().encode(SeriesRequestBody(title: title, description: description, email: email, guest_id: guestId))
        let _: Bool = try await { () async throws -> Bool in
            var req = URLRequest(url: URL(string: base + "series-requests")!)
            req.httpMethod = "POST"; req.setValue("application/json", forHTTPHeaderField: "Content-Type")
            if let t = token { req.setValue("Bearer \(t)", forHTTPHeaderField: "Authorization") }
            req.httpBody = body
            let _ = try await URLSession.shared.data(for: req)
            return true
        }()
    }
}

// MARK: - Token Manager
class TokenManager: ObservableObject {
    static let shared = TokenManager()
    @Published var isLoggedIn = false

    var token: String? {
        get { UserDefaults.standard.string(forKey: "token") }
        set { UserDefaults.standard.set(newValue, forKey: "token"); isLoggedIn = newValue != nil }
    }
    var email: String? {
        get { UserDefaults.standard.string(forKey: "email") }
        set { UserDefaults.standard.set(newValue, forKey: "email") }
    }
    var username: String? {
        get { UserDefaults.standard.string(forKey: "username") }
        set { UserDefaults.standard.set(newValue, forKey: "username") }
    }
    var coins: Double {
        get { UserDefaults.standard.double(forKey: "coins") }
        set { UserDefaults.standard.set(newValue, forKey: "coins") }
    }
    var guestCoins: Double {
        get { UserDefaults.standard.double(forKey: "guest_coins") }
        set { UserDefaults.standard.set(newValue, forKey: "guest_coins") }
    }
    // Auto-generated persistent guest ID
    var guestId: String {
        if let stored = UserDefaults.standard.string(forKey: "guest_id") { return stored }
        let newId = UUID().uuidString
        UserDefaults.standard.set(newId, forKey: "guest_id")
        return newId
    }
    var isGuest: Bool { !isLoggedIn }

    func logout() {
        let gid = guestId; let gc = guestCoins
        ["token","email","username","coins"].forEach { UserDefaults.standard.removeObject(forKey: $0) }
        UserDefaults.standard.set(gid, forKey: "guest_id")
        UserDefaults.standard.set(gc, forKey: "guest_coins")
        isLoggedIn = false
    }
}
