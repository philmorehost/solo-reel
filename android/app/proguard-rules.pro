# Default ProGuard rules for SOLOREEL
-keepattributes Signature,Annotation,InnerClasses,EnclosingMethod
-keepattributes *Annotation*
-keep class * extends java.lang.annotation.Annotation

# Retrofit
-dontwarn retrofit2.**
-keep class retrofit2.** { *; }

# Gson
-keep class com.soloreel.app.data.model.** { *; }

# Kotlin Coroutines
-dontwarn kotlinx.coroutines.**
-keepclassmembernames class kotlinx.** { volatile <fields>; }
