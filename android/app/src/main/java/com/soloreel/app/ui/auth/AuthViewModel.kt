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
                    tokenManager.savedPassword = s.password
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
        if (tokenManager.userEmail == null || tokenManager.savedPassword == null) return false
        val bm = BiometricManager.from(context)
        return bm.canAuthenticate(BiometricManager.Authenticators.BIOMETRIC_STRONG) == BiometricManager.BIOMETRIC_SUCCESS
    }

    fun onBiometricSuccess() {
        val email = tokenManager.userEmail ?: return
        val pwd = tokenManager.savedPassword ?: return
        _state.value = _state.value.copy(email = email, password = pwd)
        login()
    }

    fun signInWithGoogle(context: Context) {
        viewModelScope.launch {
            _state.value = _state.value.copy(isLoading = true, error = null)
            try {
                val credentialManager = androidx.credentials.CredentialManager.create(context)
                val googleIdOption = com.google.android.libraries.identity.googleid.GetGoogleIdOption.Builder()
                    .setFilterByAuthorizedAccounts(false)
                    .setServerClientId("YOUR_WEB_CLIENT_ID") // Requires a valid Web Client ID in production
                    .build()
                val request = androidx.credentials.GetCredentialRequest.Builder()
                    .addCredentialOption(googleIdOption)
                    .build()
                val result = credentialManager.getCredential(context, request)
                val credential = result.credential
                if (credential is androidx.credentials.CustomCredential && credential.type == com.google.android.libraries.identity.googleid.GoogleIdTokenCredential.TYPE_GOOGLE_ID_TOKEN_CREDENTIAL) {
                    val googleIdTokenCredential = com.google.android.libraries.identity.googleid.GoogleIdTokenCredential.createFrom(credential.data)
                    val email = googleIdTokenCredential.id
                    val displayName = googleIdTokenCredential.displayName ?: ""
                    
                    val res = api.googleLogin(GoogleLoginBody(email, displayName))
                    if (res.status == true && res.data?.token != null) {
                        tokenManager.accessToken = res.data.token
                        tokenManager.userEmail = email
                        res.data.user?.let { tokenManager.userName = it.username; tokenManager.userCoins = it.coin_balance ?: 0.0 }
                        _state.value = _state.value.copy(isLoading = false, isLoggedIn = true, success = "Welcome!")
                    } else {
                        _state.value = _state.value.copy(isLoading = false, error = res.message ?: "Google Login failed")
                    }
                } else {
                    _state.value = _state.value.copy(isLoading = false, error = "Invalid credential type")
                }
            } catch (e: Exception) {
                // If they don't have a Client ID, it'll fail here gracefully
                _state.value = _state.value.copy(isLoading = false, error = "Google Sign In requires Configuration.")
            }
        }
    }
}
