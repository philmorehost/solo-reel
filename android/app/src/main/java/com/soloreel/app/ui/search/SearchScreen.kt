package com.soloreel.app.ui.search

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.navigation.NavHostController
import coil.compose.rememberAsyncImagePainter
import com.soloreel.app.data.api.SOLOREELApi
import com.soloreel.app.data.model.Series
import com.soloreel.app.ui.navigation.Screen
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.FlowPreview
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class SearchState(val query: String = "", val results: List<Series> = emptyList(), val isLoading: Boolean = false)

@HiltViewModel
class SearchViewModel @Inject constructor(private val api: SOLOREELApi) : ViewModel() {
    private val _state = MutableStateFlow(SearchState())
    val state: StateFlow<SearchState> = _state.asStateFlow()
    private var searchJob: Job? = null

    fun search(q: String) {
        _state.value = _state.value.copy(query = q)
        searchJob?.cancel()
        if (q.isBlank()) { _state.value = _state.value.copy(results = emptyList()); return }
        searchJob = viewModelScope.launch {
            delay(400)
            _state.value = _state.value.copy(isLoading = true)
            try { val r = api.search(q); _state.value = _state.value.copy(results = r.data ?: emptyList(), isLoading = false) }
            catch (e: Exception) { _state.value = _state.value.copy(isLoading = false) }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SearchScreen(navController: NavHostController, vm: SearchViewModel = hiltViewModel()) {
    val state by vm.state.collectAsState()

    Column(modifier = Modifier.fillMaxSize().background(Color(0xFF0A0A0A)).padding(16.dp)) {
        OutlinedTextField(
            value = state.query, onValueChange = vm::search,
            placeholder = { Text("Search series...", color = Color.Gray) },
            leadingIcon = { Icon(Icons.Default.Search, null, tint = Color.Gray) },
            modifier = Modifier.fillMaxWidth(),
            singleLine = true,
            colors = OutlinedTextFieldDefaults.colors(focusedBorderColor = Color(0xFFDC2626), unfocusedBorderColor = Color(0xFF333333),
                focusedTextColor = Color.White, unfocusedTextColor = Color.White, cursorColor = Color(0xFFDC2626)
            )
        )
        Spacer(Modifier.height(12.dp))

        if (state.isLoading) { Box(Modifier.fillMaxWidth().padding(32.dp), contentAlignment = Alignment.Center) { CircularProgressIndicator(color = Color(0xFFDC2626)) } }
        else {
            LazyVerticalGrid(
                columns = GridCells.Fixed(2),
                contentPadding = PaddingValues(4.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp),
                verticalArrangement = Arrangement.spacedBy(8.dp),
                modifier = Modifier.fillMaxSize()
            ) {
                items(state.results) { series ->
                    Column(
                        modifier = Modifier.clickable { navController.navigate(Screen.SeriesDetail.createRoute(series.slug)) }
                    ) {
                        Box(Modifier.fillMaxWidth().height(180.dp).clip(RoundedCornerShape(12.dp)).background(Color(0xFF1A1A1A))) {
                            if (series.cover_image_url != null) {
                                androidx.compose.foundation.Image(
                                    painter = rememberAsyncImagePainter(series.cover_image_url),
                                    contentDescription = null, modifier = Modifier.fillMaxSize(), contentScale = ContentScale.Crop
                                )
                            }
                        }
                        Text(series.title, color = Color.White, fontSize = 13.sp, maxLines = 2, fontWeight = FontWeight.Medium)
                    }
                }
                if (state.query.isNotBlank() && state.results.isEmpty()) {
                    item { Text("No results found", color = Color.Gray, modifier = Modifier.padding(16.dp)) }
                }
            }
        }
    }
}
