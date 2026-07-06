package com.soloreel.app.ui.auth

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.soloreel.app.data.api.ResendOtpBody
import com.soloreel.app.data.api.SOLOREELApi
import com.soloreel.app.data.api.TokenManager
import com.soloreel.app.data.api.VerifyOtpBody
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class VerifyOtpState(
    val otp: String = "",
    val isLoading: Boolean = false,
    val error: String? = null,
    val success: String? = null,
    val resendSuccess: String? = null
)

@HiltViewModel
class VerifyOtpViewModel @Inject constructor(
    private val api: SOLOREELApi,
    private val tokenManager: TokenManager
) : ViewModel() {
    private val _state = MutableStateFlow(VerifyOtpState())
    val state: StateFlow<VerifyOtpState> = _state.asStateFlow()

    fun setOtp(v: String) { _state.value = _state.value.copy(otp = v, error = null, resendSuccess = null) }

    fun verify(userId: Int, onSuccess: () -> Unit) {
        val s = _state.value
        if (s.otp.length != 6) {
            _state.value = s.copy(error = "Enter a valid 6-digit OTP")
            return
        }
        viewModelScope.launch {
            _state.value = s.copy(isLoading = true, error = null, resendSuccess = null)
            try {
                val res = api.verifyOtp(VerifyOtpBody(userId, s.otp, tokenManager.guestId))
                if (res.status == true && res.data?.token != null) {
                    tokenManager.accessToken = res.data.token
                    res.data.user?.email?.let { tokenManager.userEmail = it }
                    res.data.user?.let {
                        tokenManager.userName = it.username
                        tokenManager.userCoins = it.coin_balance ?: 0.0
                    }
                    _state.value = _state.value.copy(isLoading = false, success = "Account verified!")
                    onSuccess()
                } else {
                    _state.value = _state.value.copy(isLoading = false, error = res.message ?: "Verification failed")
                }
            } catch (e: Exception) {
                _state.value = _state.value.copy(isLoading = false, error = e.message ?: "Connection error")
            }
        }
    }

    fun resend(email: String) {
        viewModelScope.launch {
            _state.value = _state.value.copy(isLoading = true, error = null, resendSuccess = null)
            try {
                val res = api.resendOtp(ResendOtpBody(email))
                if (res.status == true) {
                    _state.value = _state.value.copy(isLoading = false, resendSuccess = "OTP resent successfully")
                } else {
                    _state.value = _state.value.copy(isLoading = false, error = res.message ?: "Failed to resend OTP")
                }
            } catch (e: Exception) {
                _state.value = _state.value.copy(isLoading = false, error = e.message ?: "Connection error")
            }
        }
    }
}
