package com.soloreel.app.ui.auth

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
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
fun VerifyOtpScreen(
    userId: Int,
    email: String,
    onVerifySuccess: () -> Unit,
    onNavigateBack: () -> Unit,
    viewModel: VerifyOtpViewModel = hiltViewModel()
) {
    val state by viewModel.state.collectAsState()

    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(Brush.verticalGradient(colors = listOf(Color(0xFF0A0A0A), Color(0xFF1A1A1A))))
    ) {
        Column(
            modifier = Modifier.fillMaxSize().padding(24.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Spacer(Modifier.height(60.dp))
            Text("SOLOREEL", fontSize = 28.sp, fontWeight = FontWeight.Black, color = Color(0xFFDC2626))
            Text("Verify Your Account", fontSize = 14.sp, color = Color.Gray)
            Spacer(Modifier.height(16.dp))
            Text("Enter the 6-digit code sent to $email", fontSize = 14.sp, color = Color.White, textAlign = TextAlign.Center)
            Spacer(Modifier.height(24.dp))

            OutlinedTextField(
                value = state.otp,
                onValueChange = viewModel::setOtp,
                label = { Text("OTP Code") },
                modifier = Modifier.fillMaxWidth(),
                keyboardOptions = androidx.compose.foundation.text.KeyboardOptions(keyboardType = androidx.compose.ui.text.input.KeyboardType.Number),
                colors = OutlinedTextFieldDefaults.colors(
                    focusedBorderColor = Color(0xFFDC2626), unfocusedBorderColor = Color(0xFF333333),
                    focusedTextColor = Color.White, unfocusedTextColor = Color.White, cursorColor = Color(0xFFDC2626)
                )
            )
            Spacer(Modifier.height(24.dp))

            Button(
                onClick = { viewModel.verify(userId, onVerifySuccess) },
                modifier = Modifier.fillMaxWidth().height(50.dp),
                enabled = !state.isLoading,
                colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFDC2626))
            ) {
                if (state.isLoading) CircularProgressIndicator(color = Color.White, modifier = Modifier.size(24.dp))
                else Text("Verify", fontWeight = FontWeight.Bold, fontSize = 16.sp)
            }

            state.error?.let { Spacer(Modifier.height(12.dp)); Text(it, color = Color(0xFFEF4444), fontSize = 13.sp, textAlign = TextAlign.Center) }
            state.success?.let { Spacer(Modifier.height(12.dp)); Text(it, color = Color(0xFF22C55E), fontSize = 13.sp, textAlign = TextAlign.Center) }
            state.resendSuccess?.let { Spacer(Modifier.height(12.dp)); Text(it, color = Color(0xFF22C55E), fontSize = 13.sp, textAlign = TextAlign.Center) }
            
            Spacer(Modifier.height(16.dp))
            TextButton(onClick = { viewModel.resend(email) }) { Text("Resend Code", color = Color(0xFFDC2626)) }
            
            Spacer(Modifier.weight(1f))
            TextButton(onClick = onNavigateBack) { Text("Back to Login", color = Color.Gray) }
            Spacer(Modifier.height(24.dp))
        }
    }
}
