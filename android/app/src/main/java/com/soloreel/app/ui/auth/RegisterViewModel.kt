package com.soloreel.app.ui.auth

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.soloreel.app.data.api.*
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class RegisterState(val username: String = "", val email: String = "", val password: String = "",
    val isLoading: Boolean = false, val error: String? = null, val success: String? = null)

@HiltViewModel
class RegisterViewModel @Inject constructor(
    private val api: SOLOREELApi,
    private val tokenManager: TokenManager
) : ViewModel() {
    private val _state = MutableStateFlow(RegisterState())
    val state: StateFlow<RegisterState> = _state.asStateFlow()
    fun setUsername(v: String) { _state.value = _state.value.copy(username = v, error = null) }
    fun setEmail(v: String) { _state.value = _state.value.copy(email = v, error = null) }
    fun setPassword(v: String) { _state.value = _state.value.copy(password = v, error = null) }

    fun register(onSuccess: (requiresVerification: Boolean, userId: Int, email: String) -> Unit) {
        val s = _state.value
        if (s.username.isBlank() || s.email.isBlank() || s.password.isBlank()) { _state.value = s.copy(error = "Fill all fields"); return }
        viewModelScope.launch {
            _state.value = s.copy(isLoading = true, error = null)
            try {
                val res = api.register(RegisterBody(s.username, s.email, s.password, s.username, tokenManager.guestId))
                if (res.status == true) {
                    if (res.data?.requires_verification == true) {
                        _state.value = _state.value.copy(isLoading = false, success = "OTP Sent!")
                        onSuccess(true, res.data.user_id ?: 0, s.email)
                    } else if (res.data?.token != null) {
                        tokenManager.accessToken = res.data.token; tokenManager.userEmail = s.email
                        res.data.user?.let { tokenManager.userName = it.username }
                        _state.value = _state.value.copy(isLoading = false, success = "Registered!")
                        onSuccess(false, 0, "")
                    } else {
                        _state.value = _state.value.copy(isLoading = false, error = "Invalid response")
                    }
                } else {
                    _state.value = _state.value.copy(isLoading = false, error = res.message ?: "Registration failed")
                }
            } catch (e: Exception) {
                _state.value = _state.value.copy(isLoading = false, error = e.message ?: "Connection error")
            }
        }
    }
    fun signInWithGoogle(context: android.content.Context, onSuccess: () -> Unit) {
        viewModelScope.launch {
            _state.value = _state.value.copy(isLoading = true, error = null)
            try {
                val credentialManager = androidx.credentials.CredentialManager.create(context)
                val googleIdOption = com.google.android.libraries.identity.googleid.GetGoogleIdOption.Builder()
                    .setFilterByAuthorizedAccounts(false)
                    .setServerClientId("YOUR_WEB_CLIENT_ID") 
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
                    
                    val res = api.googleLogin(GoogleLoginBody(email, displayName, tokenManager.guestId))
                    if (res.status == true && res.data?.token != null) {
                        tokenManager.accessToken = res.data.token
                        tokenManager.userEmail = email
                        res.data.user?.let { tokenManager.userName = it.username; tokenManager.userCoins = it.coin_balance ?: 0.0 }
                        _state.value = _state.value.copy(isLoading = false, success = "Welcome!")
                        onSuccess()
                    } else {
                        _state.value = _state.value.copy(isLoading = false, error = res.message ?: "Google Login failed")
                    }
                } else {
                    _state.value = _state.value.copy(isLoading = false, error = "Invalid credential type")
                }
            } catch (e: Exception) {
                _state.value = _state.value.copy(isLoading = false, error = "Google Sign In requires Configuration.")
            }
        }
    }
}
