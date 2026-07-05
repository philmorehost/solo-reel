package com.soloreel.app

import android.app.AlertDialog
import android.app.Application
import android.content.Context
import android.content.Intent
import dagger.hilt.android.HiltAndroidApp
import java.io.PrintWriter
import java.io.StringWriter

@HiltAndroidApp
class SOLOREELApp : Application() {

    companion object {
        var lastCrash: String? = null
    }

    override fun onCreate() {
        super.onCreate()

        // Global crash handler - shows dialog instead of silent close
        val defaultHandler = Thread.getDefaultUncaughtExceptionHandler()
        Thread.setDefaultUncaughtExceptionHandler { thread, throwable ->
            val sw = StringWriter()
            throwable.printStackTrace(PrintWriter(sw))
            lastCrash = sw.toString()

            // Try to show a crash dialog on the main thread
            try {
                val intent = Intent(this, CrashActivity::class.java)
                intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TOP)
                intent.putExtra("crash_log", lastCrash)
                startActivity(intent)
                android.os.Process.killProcess(android.os.Process.myPid())
                System.exit(1)
            } catch (e: Exception) {
                defaultHandler?.uncaughtException(thread, throwable)
            }
        }
    }
}
