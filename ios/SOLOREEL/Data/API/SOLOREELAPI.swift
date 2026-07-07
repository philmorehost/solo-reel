import Foundation

// MARK: - Models
struct ApiResponse<T: Codable>: Codable { let status: Bool?; let data: T?; let message: String?; let error: String? }
struct LoginBody: Codable { let email: String; let password: String }
struct RegisterBody: Codable { let username: String; let email: String; let password: String; let display_name: String }
struct AuthResult: Codable { let user: User?; let token: String?; let requires_verification: Bool?; let user_id: Int? }
struct GuestInitBody: Codable { let guest_id: String }
struct GuestWallet: Codable { let guest_id: String; let coin_balance: Double }
struct VerifyOtpBody: Codable { let user_id: Int; let otp: String; let guest_id: String? }
struct ResendOtpBody: Codable { let email: String }
struct SeriesRequestBody: Codable { let title: String; let description: String?; let email: String?; let guest_id: String? }

struct Banner: Codable, Identifiable {
    let id: Int; let title: String?; let subtitle: String?; let image_url: String?; let link_url: String?
    let is_ad: Bool?; let media_type: String?; let duration_seconds: Int?
}
struct InterstitialAd: Codable {
    let id: Int; let title: String?; let media_url: String?; let media_type: String?; let target_url: String?
}
struct AdPricing: Codable {
    let duration_seconds: Int; let platform_placement: String; let price: Double; let currency: String?
}
struct MyAd: Codable, Identifiable {
    let id: Int; let title: String?; let media_url: String?; let media_type: String?; let target_url: String?
    let duration_seconds: Int; let platform_placement: String; let payment_status: String
    let is_active: Bool; let is_expired: Bool; let expires_at: String?
}
struct Shelf: Codable, Identifiable {
    let id: Int; let name: String; let slug: String; let emoji: String?
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
    let is_unlocked: Bool?; let unlock_method: String?
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
struct GuestPurchaseBody: Codable { let package_id: Int; let guest_id: String }
struct PaymentVerifyResult: Codable { let coin_balance: Double?; let coins_awarded: Double? }
struct AppNotification: Codable, Identifiable {
    let id: Int; let title: String; let body: String?; let type: String?
    let series_id: Int?; let is_read: Bool; let created_at: String?
}

// MARK: - API Client
class APIClient {
    static let shared = APIClient()
    private let base = "https://soloshort.pmhserver.name.ng/api/v1/"

    // Proxy to the persisted token so requests always carry the current login,
    // even after an app restart (previously an in-memory value that was never set).
    var token: String? {
        get { TokenManager.shared.token }
        set { TokenManager.shared.token = newValue }
    }

    private func rawRequest(_ path: String, method: String, body: Data?) async throws -> Data {
        var req = URLRequest(url: URL(string: base + path)!)
        req.httpMethod = method
        req.setValue("application/json", forHTTPHeaderField: "Content-Type")
        if let t = token { req.setValue("Bearer \(t)", forHTTPHeaderField: "Authorization") }
        req.httpBody = body
        let (data, _) = try await URLSession.shared.data(for: req)
        return data
    }

    private func request<T: Codable>(_ path: String, method: String = "GET", body: Data? = nil) async throws -> T {
        let data = try await rawRequest(path, method: method, body: body)
        let decoded = try JSONDecoder().decode(ApiResponse<T>.self, from: data)
        guard let responseData = decoded.data else {
            throw NSError(domain: "APIError", code: 0, userInfo: [NSLocalizedDescriptionKey: decoded.error ?? decoded.message ?? "Unknown API error"])
        }
        return responseData
    }

    /** For endpoints whose success response carries no `data` payload. */
    private func requestVoid(_ path: String, method: String = "GET", body: Data? = nil) async throws {
        struct Envelope: Codable { let status: Bool?; let message: String?; let error: String? }
        let data = try await rawRequest(path, method: method, body: body)
        if let decoded = try? JSONDecoder().decode(Envelope.self, from: data), decoded.status != true {
            throw NSError(domain: "APIError", code: 0, userInfo: [NSLocalizedDescriptionKey: decoded.error ?? decoded.message ?? "Request failed"])
        }
    }

    func login(email: String, password: String) async throws -> AuthResult {
        let body = try JSONEncoder().encode(LoginBody(email: email, password: password))
        return try await request("auth/login", method: "POST", body: body)
    }

    func register(username: String, email: String, password: String) async throws -> AuthResult {
        let body = try JSONEncoder().encode(RegisterBody(username: username, email: email, password: password, display_name: username))
        return try await request("auth/register", method: "POST", body: body)
    }
    
    func verifyOTP(userId: Int, otp: String, guestId: String?) async throws -> AuthResult {
        let body = try JSONEncoder().encode(VerifyOtpBody(user_id: userId, otp: otp, guest_id: guestId))
        return try await request("auth/verify-otp", method: "POST", body: body)
    }
    
    func resendOTP(email: String) async throws {
        let body = try JSONEncoder().encode(ResendOtpBody(email: email))
        try await requestVoid("auth/resend-otp", method: "POST", body: body)
    }
    func getBanners() async throws -> [Banner] { try await request("banners?active=true") }
    func getShelves() async throws -> [Shelf] { try await request("shelves?active=true") }
    func getSeries(shelf: String? = nil) async throws -> [Series] {
        let q = shelf.map { "shelf=\($0)&" } ?? ""
        return try await request("series?\(q)size=20")
    }
    func getSeriesDetail(slug: String) async throws -> Series { try await request("series/\(slug)/by-slug") }
    func getEpisodes(seriesId: Int, guestId: String? = nil) async throws -> [Episode] {
        let q = guestId.map { "?guest_id=\($0)" } ?? ""
        return try await request("series/\(seriesId)/episodes\(q)")
    }
    func getEpisode(slug: String, guestId: String? = nil) async throws -> Episode {
        let q = guestId.map { "?guest_id=\($0)" } ?? ""
        return try await request("episodes/\(slug)/by-slug\(q)")
    }
    /** Unlocks a locked episode by spending coins (registered user or guest wallet). */
    func unlockWithCoins(episodeId: Int, guestId: String? = nil) async throws {
        let body = try JSONEncoder().encode(guestId.map { ["guest_id": $0] } ?? [:])
        try await requestVoid("episodes/unlock/\(episodeId)", method: "POST", body: body)
    }
    /** Unlocks a locked episode after the user watches a rewarded ad to completion. */
    func unlockWithAd(episodeId: Int, guestId: String? = nil) async throws {
        let body = try JSONEncoder().encode(guestId.map { ["guest_id": $0] } ?? [:])
        try await requestVoid("episodes/unlock-with-ad/\(episodeId)", method: "POST", body: body)
    }
    func search(q: String) async throws -> [Series] { try await request("search?q=\(q.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? "")") }
    func getCoinPackages() async throws -> [CoinPackage] { try await request("coin-packages") }
    func getProfile() async throws -> User { try await request("user/profile") }
    func updateProfile(username: String, displayName: String, password: String?) async throws {
        struct UpdateProfileBody: Codable { let username: String; let display_name: String; let password: String? }
        let body = try JSONEncoder().encode(UpdateProfileBody(username: username, display_name: displayName, password: password))
        try await requestVoid("user/profile", method: "PUT", body: body)
    }
    func getWatchHistory() async throws -> [WatchHistoryItem] { try await request("user/watch-history") }
    func getFavorites() async throws -> [Series] { try await request("user/favorites") }
    func getBonusStatus() async throws -> WeeklyBonusStatus { try await request("user/bonus-status") }
    func getAdsConfig() async throws -> [String: String] { try await request("ads-config") }
    func getInterstitialAd() async throws -> InterstitialAd { try await request("ads/interstitial") }
    func getAdsPricing() async throws -> [AdPricing] { try await request("ads/pricing") }
    func getMyAds() async throws -> [MyAd] { try await request("ads/my-ads") }
    func renewAd(id: Int) async throws -> PaymentInit { try await request("ads/renew/\(id)", method: "POST") }

    /// Submits a new self-serve ad as multipart/form-data (image or video upload).
    func subscribeAd(title: String, targetUrl: String, durationSeconds: Int, platformPlacement: String, mediaData: Data, mediaFilename: String, mediaMimeType: String) async throws -> PaymentInit {
        let boundary = "Boundary-\(UUID().uuidString)"
        var body = Data()
        func addField(_ name: String, _ value: String) {
            body.append("--\(boundary)\r\n".data(using: .utf8)!)
            body.append("Content-Disposition: form-data; name=\"\(name)\"\r\n\r\n".data(using: .utf8)!)
            body.append("\(value)\r\n".data(using: .utf8)!)
        }
        addField("title", title)
        addField("target_url", targetUrl)
        addField("duration_seconds", String(durationSeconds))
        addField("platform_placement", platformPlacement)

        body.append("--\(boundary)\r\n".data(using: .utf8)!)
        body.append("Content-Disposition: form-data; name=\"media_file\"; filename=\"\(mediaFilename)\"\r\n".data(using: .utf8)!)
        body.append("Content-Type: \(mediaMimeType)\r\n\r\n".data(using: .utf8)!)
        body.append(mediaData)
        body.append("\r\n".data(using: .utf8)!)
        body.append("--\(boundary)--\r\n".data(using: .utf8)!)

        var req = URLRequest(url: URL(string: base + "ads/subscribe")!)
        req.httpMethod = "POST"
        req.setValue("multipart/form-data; boundary=\(boundary)", forHTTPHeaderField: "Content-Type")
        if let t = token { req.setValue("Bearer \(t)", forHTTPHeaderField: "Authorization") }
        req.httpBody = body

        let (data, _) = try await URLSession.shared.data(for: req)
        let decoded = try JSONDecoder().decode(ApiResponse<PaymentInit>.self, from: data)
        guard let responseData = decoded.data else {
            throw NSError(domain: "APIError", code: 0, userInfo: [NSLocalizedDescriptionKey: decoded.error ?? decoded.message ?? "Unknown API error"])
        }
        return responseData
    }
    func initGuest(guestId: String) async throws -> GuestWallet {
        let body = try JSONEncoder().encode(GuestInitBody(guest_id: guestId))
        return try await request("guest/init", method: "POST", body: body)
    }
    func purchaseCoins(packageId: Int) async throws -> PaymentInit {
        let body = try JSONEncoder().encode(["package_id": packageId])
        return try await request("coins/purchase", method: "POST", body: body)
    }
    func guestPurchaseCoins(packageId: Int, guestId: String) async throws -> PaymentInit {
        let body = try JSONEncoder().encode(GuestPurchaseBody(package_id: packageId, guest_id: guestId))
        return try await request("coins/guest-purchase", method: "POST", body: body)
    }
    func verifyPayment(reference: String) async throws -> PaymentVerifyResult {
        let ref = reference.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? reference
        return try await request("payment/verify?reference=\(ref)")
    }
    func getGuestBalance(guestId: String) async throws -> GuestWallet {
        try await request("guest/balance?guest_id=\(guestId)")
    }
    func getNotifications(guestId: String? = nil) async throws -> [AppNotification] {
        let q = guestId.map { "?guest_id=\($0)" } ?? ""
        return try await request("notifications\(q)")
    }
    func markNotificationRead(id: Int, guestId: String? = nil) async throws {
        var body: Data? = nil
        if let g = guestId { body = try JSONEncoder().encode(["guest_id": g]) }
        try await requestVoid("notifications/\(id)/read", method: "POST", body: body)
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
    @Published var isLoggedIn: Bool

    private init() {
        // Restore login state from the persisted token so users stay signed in.
        isLoggedIn = UserDefaults.standard.string(forKey: "token") != nil
    }

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

    // Guest browsing: lets unregistered users into the app (search, watch, buy coins).
    @Published var guestMode: Bool = UserDefaults.standard.bool(forKey: "guest_mode")

    func continueAsGuest() {
        UserDefaults.standard.set(true, forKey: "guest_mode")
        guestMode = true
    }

    func logout() {
        let gid = guestId; let gc = guestCoins
        ["token","email","username","coins","guest_mode"].forEach { UserDefaults.standard.removeObject(forKey: $0) }
        UserDefaults.standard.set(gid, forKey: "guest_id")
        UserDefaults.standard.set(gc, forKey: "guest_coins")
        isLoggedIn = false
        guestMode = false
    }
}
