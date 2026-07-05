package com.soloreel.app

import android.os.Bundle
import android.widget.ScrollView
import android.widget.TextView
import android.app.Activity
import android.graphics.Color
import android.text.Spannable
import android.text.SpannableString
import android.text.style.ForegroundColorSpan
import android.widget.LinearLayout
import android.widget.Button

class CrashActivity : Activity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        val crashLog = intent.getStringExtra("crash_log") ?: "Unknown error"

        val root = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            setPadding(32, 48, 32, 32)
            setBackgroundColor(Color.parseColor("#0A0A0A"))
        }

        val title = TextView(this).apply {
            text = "App Crashed"
            textSize = 22f
            setTextColor(Color.RED)
            setPadding(0, 0, 0, 16)
        }
        root.addView(title)

        val message = TextView(this).apply {
            text = "The app encountered an unexpected error. Scroll to see the details:"
            textSize = 14f
            setTextColor(Color.LTGRAY)
            setPadding(0, 0, 0, 12)
        }
        root.addView(message)

        val scrollView = ScrollView(this).apply {
            setBackgroundColor(Color.parseColor("#1A1A1A"))
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                0, 1f
            )
        }

        val logText = TextView(this).apply {
            text = crashLog
            textSize = 11f
            setTextColor(Color.WHITE)
            typeface = android.graphics.Typeface.MONOSPACE
            setPadding(16, 16, 16, 16)
            setHorizontallyScrolling(true)
        }
        scrollView.addView(logText)
        root.addView(scrollView)

        val restartBtn = Button(this).apply {
            text = "Restart App"
            textSize = 16f
            setTextColor(Color.WHITE)
            setBackgroundColor(Color.parseColor("#DC2626"))
            setOnClickListener {
                // Try to restart the app
                val intent = packageManager.getLaunchIntentForPackage(packageName)
                if (intent != null) {
                    intent.addFlags(android.content.Intent.FLAG_ACTIVITY_CLEAR_TOP or android.content.Intent.FLAG_ACTIVITY_NEW_TASK)
                    startActivity(intent)
                }
                finish()
            }
        }
        val btnParams = LinearLayout.LayoutParams(
            LinearLayout.LayoutParams.MATCH_PARENT,
            LinearLayout.LayoutParams.WRAP_CONTENT
        ).apply { topMargin = 24 }
        root.addView(restartBtn, btnParams)

        setContentView(root)
    }
}
