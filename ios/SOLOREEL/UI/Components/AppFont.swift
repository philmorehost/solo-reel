import SwiftUI

/// Matches reelshort.com's own body font — confirmed via their production CSS
/// (--fontFamily:"Noto Sans"), not a guess. Bundled as a single variable font
/// (NotoSans-VariableFont.ttf, registered in Info.plist) whose named weight
/// instances (NotoSans-Regular, -Medium, -SemiBold, -Bold, -Black, ...) become
/// individually selectable PostScript names once iOS loads the file.
extension Font {
    static func notoSans(size: CGFloat, weight: Font.Weight = .regular, relativeTo textStyle: Font.TextStyle = .body) -> Font {
        let psName: String
        switch weight {
        case .black: psName = "NotoSans-Black"
        case .heavy: psName = "NotoSans-ExtraBold"
        case .bold: psName = "NotoSans-Bold"
        case .semibold: psName = "NotoSans-SemiBold"
        case .medium: psName = "NotoSans-Medium"
        case .light: psName = "NotoSans-Light"
        case .thin, .ultraLight: psName = "NotoSans-Thin"
        default: psName = "NotoSans-Regular"
        }
        return .custom(psName, size: size, relativeTo: textStyle)
    }
}
