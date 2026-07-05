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

    fun register(onSuccess: () -> Unit) {
        val s = _state.value
        if (s.username.isBlank() || s.email.isBlank() || s.password.isBlank()) { _state.value = s.copy(error = "Fill all fields"); return }
        viewModelScope.launch {
            _state.value = s.copy(isLoading = true, error = null)
            try {
                val res = api.register(RegisterBody(s.username, s.email, s.password, s.username))
                if (res.status == true && res.data?.token != null) {
                    tokenManager.accessToken = res.data.token; tokenManager.userEmail = s.email
                    res.data.user?.let { tokenManager.userName = it.username }
                    _state.value = _state.value.copy(isLoading = false, success = "Registered!")
                    onSuccess()
                } else {
                    _state.value = _state.value.copy(isLoading = false, error = res.message ?: "Registration failed")
                }
            } catch (e: Exception) {
                _state.value = _state.value.copy(isLoading = false, error = e.message ?: "Connection error")
            }
        }
    }
}
