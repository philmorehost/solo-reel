package com.soloreel.app.ui.auth

import android.content.Context
import android.provider.Settings
import androidx.biometric.BiometricManager
import androidx.biometric.BiometricPrompt
import androidx.core.content.ContextCompat
import androidx.fragment.app.FragmentActivity
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.soloreel.app.data.api.*
import dagger.hilt.android.lifecycle.HiltViewModel
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class AuthState(
    val email: String = "", val password: String = "",
    val isLoading: Boolean = false, val error: String? = null, val success: String? = null, val isLoggedIn: Boolean = false
)

@HiltViewModel
class AuthViewModel @Inject constructor(
    private val api: SOLOREELApi,
    private val tokenManager: TokenManager,
    @ApplicationContext private val context: Context
) : ViewModel() {

    private val _state = MutableStateFlow(AuthState())
    val state: StateFlow<AuthState> = _state.asStateFlow()

    fun setEmail(v: String) { _state.value = _state.value.copy(email = v, error = null) }
    fun setPassword(v: String) { _state.value = _state.value.copy(password = v, error = null) }

    fun login() {
        val s = _state.value
        if (s.email.isBlank() || s.password.isBlank()) { _state.value = s.copy(error = "Fill all fields"); return }
        viewModelScope.launch {
            _state.value = s.copy(isLoading = true, error = null)
            try {
                val res = api.login(LoginBody(s.email, s.password))
                if (res.status == true && res.data?.token != null) {
                    tokenManager.accessToken = res.data.token
                    tokenManager.userEmail = s.email
                    res.data.user?.let { tokenManager.userName = it.username; tokenManager.userCoins = it.coin_balance ?: 0.0 }
                    _state.value = _state.value.copy(isLoading = false, isLoggedIn = true, success = "Welcome!")
                } else {
                    _state.value = _state.value.copy(isLoading = false, error = res.message ?: "Login failed")
                }
            } catch (e: Exception) {
                _state.value = _state.value.copy(isLoading = false, error = e.message ?: "Connection error")
            }
        }
    }

    fun isBiometricAvailable(): Boolean {
        val bm = BiometricManager.from(context)
        return bm.canAuthenticate(BiometricManager.Authenticators.BIOMETRIC_STRONG) == BiometricManager.BIOMETRIC_SUCCESS
    }

    fun loginWithBiometric() {
        if (context !is FragmentActivity) return
        val prompt = BiometricPrompt(context, ContextCompat.getMainExecutor(context),
            object : BiometricPrompt.AuthenticationCallback() {
                override fun onAuthenticationSucceeded(result: BiometricPrompt.AuthenticationResult) {
                    _state.value = _state.value.copy(isLoggedIn = true, success = "Biometric OK")
                }
                override fun onAuthenticationError(errorCode: Int, errString: CharSequence) {
                    _state.value = _state.value.copy(error = errString.toString())
                }
            })
        prompt.authenticate(BiometricPrompt.PromptInfo.Builder()
            .setTitle("SOLOREEL Login").setSubtitle("Use your fingerprint")
            .setAllowedAuthenticators(BiometricManager.Authenticators.BIOMETRIC_STRONG)
            .build())
    }
}
