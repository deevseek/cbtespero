package com.espero.cbt

import android.app.Activity
import android.content.pm.ActivityInfo
import android.os.Build
import android.os.Bundle
import android.provider.Settings
import android.view.WindowManager
import io.flutter.embedding.android.FlutterActivity
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.plugin.common.MethodChannel

class MainActivity: FlutterActivity() {
    private val channel = "espero_cbt/security"
    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)
        MethodChannel(flutterEngine.dartExecutor.binaryMessenger, channel).setMethodCallHandler { call, result ->
            when (call.method) {
                "enableExamMode" -> { enableExamMode(call.argument<String>("orientation") ?: "portrait"); result.success(true) }
                "disableExamMode" -> { disableExamMode(); result.success(true) }
                "deviceInfo" -> result.success(mapOf(
                    "device_id" to Settings.Secure.getString(contentResolver, Settings.Secure.ANDROID_ID),
                    "device_name" to "${Build.MANUFACTURER} ${Build.MODEL}",
                    "platform" to "android-${Build.VERSION.SDK_INT}",
                    "app_version" to "1.0.0"
                ))
                "startLockTaskMode" -> { try { startLockTask(); result.success(true) } catch (e: Exception) { result.success(false) } }
                else -> result.notImplemented()
            }
        }
    }
    private fun enableExamMode(orientation: String) {
        window.addFlags(WindowManager.LayoutParams.FLAG_SECURE)
        requestedOrientation = if (orientation == "landscape") ActivityInfo.SCREEN_ORIENTATION_LANDSCAPE else ActivityInfo.SCREEN_ORIENTATION_PORTRAIT
        window.decorView.systemUiVisibility = (
            android.view.View.SYSTEM_UI_FLAG_IMMERSIVE_STICKY or android.view.View.SYSTEM_UI_FLAG_FULLSCREEN or
            android.view.View.SYSTEM_UI_FLAG_HIDE_NAVIGATION or android.view.View.SYSTEM_UI_FLAG_LAYOUT_FULLSCREEN or
            android.view.View.SYSTEM_UI_FLAG_LAYOUT_HIDE_NAVIGATION or android.view.View.SYSTEM_UI_FLAG_LAYOUT_STABLE)
    }
    private fun disableExamMode() { window.clearFlags(WindowManager.LayoutParams.FLAG_SECURE); requestedOrientation = ActivityInfo.SCREEN_ORIENTATION_UNSPECIFIED }
    override fun onWindowFocusChanged(hasFocus: Boolean) { super.onWindowFocusChanged(hasFocus); if (hasFocus) enableExamMode("portrait") }
}
