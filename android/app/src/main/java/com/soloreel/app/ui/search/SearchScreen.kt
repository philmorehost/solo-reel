package com.soloreel.app.ui.search

import androidx.compose.foundation.*
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.grid.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.navigation.NavHostController
import coil.compose.AsyncImage
import com.soloreel.app.data.api.SOLOREELApi
import com.soloreel.app.data.api.TokenManager
import com.soloreel.app.data.model.Series
import com.soloreel.app.data.model.SeriesRequest
import com.soloreel.app.ui.navigation.Screen
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class SearchState(
    val query: String = "",
    val results: List<Series> = emptyList(),
    val isLoading: Boolean = false,
    val requestSent: Boolean = false,
    val requestLoading: Boolean = false,
    val requestError: String? = null
)

@HiltViewModel
class SearchViewModel @Inject constructor(
    private val api: SOLOREELApi,
    private val tokenManager: TokenManager
) : ViewModel() {
    private val _state = MutableStateFlow(SearchState())
    val state: StateFlow<SearchState> = _state.asStateFlow()
    private var searchJob: Job? = null

    init { loadAllSeries() }

    private fun loadAllSeries() {
        searchJob?.cancel()
        searchJob = viewModelScope.launch {
            _state.value = _state.value.copy(isLoading = true)
            try {
                val r = api.search("", size = 100)
                _state.value = _state.value.copy(results = r.data ?: emptyList(), isLoading = false)
            } catch (e: Exception) {
                _state.value = _state.value.copy(isLoading = false)
            }
        }
    }

    fun search(q: String) {
        _state.value = _state.value.copy(query = q, requestSent = false, requestError = null)
        searchJob?.cancel()
        if (q.isBlank()) { loadAllSeries(); return }
        searchJob = viewModelScope.launch {
            delay(400)
            _state.value = _state.value.copy(isLoading = true)
            try {
                val r = api.search(q)
                _state.value = _state.value.copy(results = r.data ?: emptyList(), isLoading = false)
            } catch (e: Exception) {
                _state.value = _state.value.copy(isLoading = false, results = emptyList())
            }
        }
    }

    fun sendSeriesRequest(title: String, description: String, email: String) {
        viewModelScope.launch {
            _state.value = _state.value.copy(requestLoading = true, requestError = null)
            try {
                val body = SeriesRequest(
                    title = title,
                    description = description.ifBlank { null },
                    email = email.ifBlank { null },
                    guest_id = if (tokenManager.isGuest) tokenManager.guestId else null
                )
                api.createSeriesRequest(body)
                _state.value = _state.value.copy(requestLoading = false, requestSent = true)
            } catch (e: Exception) {
                _state.value = _state.value.copy(requestLoading = false, requestError = "Failed to send request. Please try again.")
            }
        }
    }

    fun getUserEmail() = tokenManager.userEmail ?: ""
    fun isLoggedIn() = tokenManager.isLoggedIn
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SearchScreen(navController: NavHostController, vm: SearchViewModel = hiltViewModel()) {
    val state by vm.state.collectAsState()
    var showRequestDialog by remember { mutableStateOf(false) }

    Column(modifier = Modifier.fillMaxSize().background(Color(0xFF0A0A0A)).padding(16.dp)) {
        Text("Search", color = Color.White, fontSize = 22.sp, fontWeight = FontWeight.Bold)
        Spacer(Modifier.height(12.dp))

        OutlinedTextField(
            value = state.query,
            onValueChange = vm::search,
            placeholder = { Text("Search series...", color = Color(0xFF555555)) },
            leadingIcon = { Icon(Icons.Default.Search, null, tint = Color(0xFF555555)) },
            trailingIcon = {
                if (state.query.isNotEmpty()) {
                    IconButton(onClick = { vm.search("") }) {
                        Icon(Icons.Default.Close, null, tint = Color(0xFF555555))
                    }
                }
            },
            modifier = Modifier.fillMaxWidth(),
            singleLine = true,
            shape = RoundedCornerShape(14.dp),
            colors = OutlinedTextFieldDefaults.colors(
                focusedBorderColor = Color(0xFFDC2626), unfocusedBorderColor = Color(0xFF222222),
                focusedContainerColor = Color(0xFF111111), unfocusedContainerColor = Color(0xFF111111),
                focusedTextColor = Color.White, unfocusedTextColor = Color.White, cursorColor = Color(0xFFDC2626)
            )
        )
        Spacer(Modifier.height(16.dp))

        when {
            state.isLoading -> Box(Modifier.fillMaxWidth().padding(32.dp), contentAlignment = Alignment.Center) {
                CircularProgressIndicator(color = Color(0xFFDC2626))
            }
            state.results.isNotEmpty() -> {
                LazyVerticalGrid(
                    columns = GridCells.Fixed(2),
                    contentPadding = PaddingValues(4.dp),
                    horizontalArrangement = Arrangement.spacedBy(10.dp),
                    verticalArrangement = Arrangement.spacedBy(10.dp),
                    modifier = Modifier.fillMaxSize()
                ) {
                    items(state.results) { series ->
                        SeriesCard(series = series, onClick = {
                            navController.navigate(Screen.SeriesDetail.createRoute(series.slug))
                        })
                    }
                }
            }
            state.query.isNotBlank() && !state.isLoading -> {
                // No results state
                Column(modifier = Modifier.fillMaxWidth().padding(top = 40.dp), horizontalAlignment = Alignment.CenterHorizontally) {
                    Text("🔍", fontSize = 52.sp)
                    Spacer(Modifier.height(12.dp))
                    Text("No results for", color = Color(0xFF666666), fontSize = 15.sp)
                    Text("\"${state.query}\"", color = Color.White, fontSize = 18.sp, fontWeight = FontWeight.Bold)
                    Spacer(Modifier.height(24.dp))
                    Card(
                        modifier = Modifier.fillMaxWidth().padding(horizontal = 8.dp),
                        colors = CardDefaults.cardColors(containerColor = Color(0xFF161616)),
                        shape = RoundedCornerShape(16.dp),
                        border = BorderStroke(1.dp, Color(0xFF333333))
                    ) {
                        Column(modifier = Modifier.padding(20.dp), horizontalAlignment = Alignment.CenterHorizontally) {
                            Text("Don't see what you want?", color = Color.White, fontSize = 16.sp, fontWeight = FontWeight.SemiBold)
                            Spacer(Modifier.height(6.dp))
                            Text("Request it and we'll notify you when it's available!", color = Color(0xFF888888), fontSize = 13.sp, textAlign = TextAlign.Center)
                            Spacer(Modifier.height(16.dp))
                            if (state.requestSent) {
                                Row(verticalAlignment = Alignment.CenterVertically) {
                                    Icon(Icons.Default.CheckCircle, null, tint = Color(0xFF4ADE80), modifier = Modifier.size(20.dp))
                                    Spacer(Modifier.width(8.dp))
                                    Text("Request sent! We'll notify you.", color = Color(0xFF4ADE80), fontWeight = FontWeight.Bold)
                                }
                            } else {
                                Button(
                                    onClick = { showRequestDialog = true },
                                    modifier = Modifier.fillMaxWidth().height(48.dp),
                                    shape = RoundedCornerShape(12.dp),
                                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFDC2626))
                                ) {
                                    Icon(Icons.Default.AddCircle, null, modifier = Modifier.size(18.dp))
                                    Spacer(Modifier.width(8.dp))
                                    Text("Request \"${state.query}\"", fontWeight = FontWeight.Bold)
                                }
                            }
                        }
                    }
                }
            }
            else -> {
                // Empty state — show hint
                Column(modifier = Modifier.fillMaxWidth().padding(top = 60.dp), horizontalAlignment = Alignment.CenterHorizontally) {
                    Text("🎬", fontSize = 52.sp)
                    Spacer(Modifier.height(12.dp))
                    Text("Search for your favourite series", color = Color(0xFF555555), fontSize = 15.sp, textAlign = TextAlign.Center)
                }
            }
        }
    }

    if (showRequestDialog) {
        SeriesRequestDialog(
            initialTitle = state.query,
            initialEmail = vm.getUserEmail(),
            isLoggedIn = vm.isLoggedIn(),
            isLoading = state.requestLoading,
            onDismiss = { showRequestDialog = false },
            onSubmit = { title, desc, email ->
                vm.sendSeriesRequest(title, desc, email)
                showRequestDialog = false
            }
        )
    }
}

@Composable
fun SeriesCard(series: Series, onClick: () -> Unit) {
    Column(modifier = Modifier.clickable(onClick = onClick)) {
        Box(
            modifier = Modifier.fillMaxWidth().aspectRatio(0.68f)
                .clip(RoundedCornerShape(12.dp)).background(Color(0xFF1A1A1A))
        ) {
            AsyncImage(
                model = series.cover_image_url,
                contentDescription = series.title,
                modifier = Modifier.fillMaxSize(),
                contentScale = ContentScale.Crop
            )
            // Episode count badge
            if ((series.episode_count ?: 0) > 0) {
                Box(
                    modifier = Modifier.padding(6.dp).align(Alignment.TopEnd)
                        .background(Color(0xCC000000), RoundedCornerShape(6.dp))
                        .padding(horizontal = 6.dp, vertical = 2.dp)
                ) {
                    Text("${series.episode_count} EP", color = Color.White, fontSize = 10.sp, fontWeight = FontWeight.Bold)
                }
            }
        }
        Spacer(Modifier.height(6.dp))
        Text(series.title, color = Color.White, fontSize = 13.sp, maxLines = 2, fontWeight = FontWeight.Medium)
        series.genre?.let { Text(it, color = Color(0xFF666666), fontSize = 11.sp, maxLines = 1) }
    }
}

@Composable
fun SeriesRequestDialog(
    initialTitle: String,
    initialEmail: String,
    isLoggedIn: Boolean,
    isLoading: Boolean,
    onDismiss: () -> Unit,
    onSubmit: (title: String, description: String, email: String) -> Unit
) {
    var title by remember { mutableStateOf(initialTitle) }
    var description by remember { mutableStateOf("") }
    var email by remember { mutableStateOf(initialEmail) }

    Dialog(onDismissRequest = onDismiss) {
        Card(
            shape = RoundedCornerShape(20.dp),
            colors = CardDefaults.cardColors(containerColor = Color(0xFF161616))
        ) {
            Column(modifier = Modifier.padding(24.dp)) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text("📩", fontSize = 24.sp)
                    Spacer(Modifier.width(10.dp))
                    Text("Request a Series", color = Color.White, fontSize = 18.sp, fontWeight = FontWeight.Bold)
                }
                Spacer(Modifier.height(4.dp))
                Text("We'll notify you when it's available!", color = Color(0xFF888888), fontSize = 13.sp)
                Spacer(Modifier.height(16.dp))

                OutlinedTextField(
                    value = title, onValueChange = { title = it },
                    label = { Text("Series Title *", color = Color(0xFF888888)) },
                    modifier = Modifier.fillMaxWidth(), singleLine = true,
                    colors = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor = Color(0xFFDC2626), unfocusedBorderColor = Color(0xFF333333),
                        focusedTextColor = Color.White, unfocusedTextColor = Color.White
                    )
                )
                Spacer(Modifier.height(10.dp))
                OutlinedTextField(
                    value = description, onValueChange = { description = it },
                    label = { Text("Description (optional)", color = Color(0xFF888888)) },
                    modifier = Modifier.fillMaxWidth(), maxLines = 3,
                    colors = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor = Color(0xFFDC2626), unfocusedBorderColor = Color(0xFF333333),
                        focusedTextColor = Color.White, unfocusedTextColor = Color.White
                    )
                )
                if (!isLoggedIn) {
                    Spacer(Modifier.height(10.dp))
                    OutlinedTextField(
                        value = email, onValueChange = { email = it },
                        label = { Text("Your Email (for notification)", color = Color(0xFF888888)) },
                        modifier = Modifier.fillMaxWidth(), singleLine = true,
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedBorderColor = Color(0xFFDC2626), unfocusedBorderColor = Color(0xFF333333),
                            focusedTextColor = Color.White, unfocusedTextColor = Color.White
                        )
                    )
                }
                Spacer(Modifier.height(20.dp))
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                    OutlinedButton(
                        onClick = onDismiss,
                        modifier = Modifier.weight(1f),
                        colors = ButtonDefaults.outlinedButtonColors(contentColor = Color(0xFF888888)),
                        border = BorderStroke(1.dp, Color(0xFF333333))
                    ) { Text("Cancel") }
                    Button(
                        onClick = { if (title.isNotBlank()) onSubmit(title, description, email) },
                        modifier = Modifier.weight(1f),
                        colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFDC2626)),
                        enabled = !isLoading && title.isNotBlank()
                    ) {
                        if (isLoading) CircularProgressIndicator(modifier = Modifier.size(18.dp), color = Color.White, strokeWidth = 2.dp)
                        else Text("Send Request", fontWeight = FontWeight.Bold)
                    }
                }
            }
        }
    }
}
