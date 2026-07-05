package com.soloreel.app.ui.auth

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun RegisterScreen(
    onRegisterSuccess: () -> Unit,
    onNavigateToLogin: () -> Unit,
    viewModel: RegisterViewModel = hiltViewModel()
) {
    val state by viewModel.state.collectAsState()

    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(Brush.verticalGradient(colors = listOf(Color(0xFF0A0A0A), Color(0xFF1A1A1A))))
    ) {
        Column(
            modifier = Modifier.fillMaxSize().padding(24.dp).verticalScroll(rememberScrollState()),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Spacer(Modifier.height(60.dp))
            Text("SOLOREEL", fontSize = 28.sp, fontWeight = FontWeight.Black, color = Color(0xFFDC2626))
            Text("Create Account", fontSize = 14.sp, color = Color.Gray)
            Spacer(Modifier.height(24.dp))

            OutlinedTextField(value = state.username, onValueChange = viewModel::setUsername, label = { Text("Username") }, modifier = Modifier.fillMaxWidth(),
                colors = OutlineTextFieldDefaults())
            Spacer(Modifier.height(12.dp))
            OutlinedTextField(value = state.email, onValueChange = viewModel::setEmail, label = { Text("Email") }, modifier = Modifier.fillMaxWidth(),
                colors = OutlineTextFieldDefaults())
            Spacer(Modifier.height(12.dp))
            OutlinedTextField(value = state.password, onValueChange = viewModel::setPassword, label = { Text("Password") }, modifier = Modifier.fillMaxWidth(),
                visualTransformation = androidx.compose.ui.text.input.PasswordVisualTransformation(),
                colors = OutlineTextFieldDefaults())
            Spacer(Modifier.height(24.dp))

            Button(onClick = { viewModel.register(onRegisterSuccess) }, modifier = Modifier.fillMaxWidth().height(50.dp),
                enabled = !state.isLoading, colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFDC2626))
            ) {
                if (state.isLoading) CircularProgressIndicator(color = Color.White, modifier = Modifier.size(24.dp))
                else Text("Create Account", fontWeight = FontWeight.Bold, fontSize = 16.sp)
            }

            state.error?.let { Spacer(Modifier.height(12.dp)); Text(it, color = Color(0xFFEF4444), fontSize = 13.sp, textAlign = TextAlign.Center) }
            Spacer(Modifier.height(16.dp))
            TextButton(onClick = onNavigateToLogin) { Text("Already have an account? Sign in", color = Color(0xFFDC2626)) }
            Spacer(Modifier.height(60.dp))
        }
    }
}

@Composable
private fun OutlineTextFieldDefaults() = OutlinedTextFieldDefaults.colors(
    focusedBorderColor = Color(0xFFDC2626), unfocusedBorderColor = Color(0xFF333333),
    focusedTextColor = Color.White, unfocusedTextColor = Color.White, cursorColor = Color(0xFFDC2626)
)
