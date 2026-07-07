package com.soloreel.app.data.api

import android.content.Intent
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.asSharedFlow

/**
 * Payment checkout runs in a Chrome Custom Tab, not an embedded WebView — the
 * server redirects to soloreel://payment-complete?status=...&reference=...
 * once the flow ends. MainActivity forwards that intent here (there's no
 * ViewModel-scoped way to receive an Activity's intent directly), and
 * CoinViewModel collects it to resume the purchase flow.
 */
data class PaymentResult(val status: String, val reference: String?)

object PaymentResultBus {
    private val _events = MutableSharedFlow<PaymentResult>(extraBufferCapacity = 1)
    val events = _events.asSharedFlow()

    fun handleIntent(intent: Intent?) {
        val uri = intent?.data ?: return
        if (uri.scheme != "soloreel" || uri.host != "payment-complete") return
        val status = uri.getQueryParameter("status") ?: "unknown"
        val reference = uri.getQueryParameter("reference")
        _events.tryEmit(PaymentResult(status, reference))
    }
}
