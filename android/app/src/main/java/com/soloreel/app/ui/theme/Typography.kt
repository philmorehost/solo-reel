package com.soloreel.app.ui.theme

import androidx.compose.material3.Typography
import androidx.compose.ui.text.ExperimentalTextApi
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.Font
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontVariation
import androidx.compose.ui.text.font.FontWeight
import com.soloreel.app.R

/**
 * Matches reelshort.com's own body font — confirmed via their production CSS
 * (--fontFamily:"Noto Sans"), not a guess. Single variable font file, instantiated
 * per weight via FontVariation (ignored gracefully on API < 26, where it renders
 * at the font's default weight).
 */
@OptIn(ExperimentalTextApi::class)
private fun notoSans(weight: FontWeight) = Font(
    resId = R.font.noto_sans,
    weight = weight,
    variationSettings = FontVariation.Settings(FontVariation.weight(weight.weight))
)

val NotoSansFamily = FontFamily(
    notoSans(FontWeight.Normal),
    notoSans(FontWeight.Medium),
    notoSans(FontWeight.SemiBold),
    notoSans(FontWeight.Bold),
    notoSans(FontWeight.ExtraBold),
)

val SoloreelTypography = Typography().let { base ->
    Typography(
        displayLarge = base.displayLarge.copy(fontFamily = NotoSansFamily),
        displayMedium = base.displayMedium.copy(fontFamily = NotoSansFamily),
        displaySmall = base.displaySmall.copy(fontFamily = NotoSansFamily),
        headlineLarge = base.headlineLarge.copy(fontFamily = NotoSansFamily),
        headlineMedium = base.headlineMedium.copy(fontFamily = NotoSansFamily),
        headlineSmall = base.headlineSmall.copy(fontFamily = NotoSansFamily),
        titleLarge = base.titleLarge.copy(fontFamily = NotoSansFamily),
        titleMedium = base.titleMedium.copy(fontFamily = NotoSansFamily),
        titleSmall = base.titleSmall.copy(fontFamily = NotoSansFamily),
        bodyLarge = base.bodyLarge.copy(fontFamily = NotoSansFamily),
        bodyMedium = base.bodyMedium.copy(fontFamily = NotoSansFamily),
        bodySmall = base.bodySmall.copy(fontFamily = NotoSansFamily),
        labelLarge = base.labelLarge.copy(fontFamily = NotoSansFamily),
        labelMedium = base.labelMedium.copy(fontFamily = NotoSansFamily),
        labelSmall = base.labelSmall.copy(fontFamily = NotoSansFamily),
    )
}
