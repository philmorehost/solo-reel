# Default ProGuard rules for SOLOREEL
-keepattributes Signature,Annotation,InnerClasses,EnclosingMethod
-keepattributes *Annotation*
-keep class * extends java.lang.annotation.Annotation

# Retrofit
-dontwarn retrofit2.**
-keep class retrofit2.** { *; }

# Keep all data and model classes for Retrofit/Gson deserialization
-keep class com.soloreel.app.data.** { *; }
-keepclassmembers class com.soloreel.app.data.** { *; }

# Kotlin Coroutines
-dontwarn kotlinx.coroutines.**
-keepclassmembernames class kotlinx.** { volatile <fields>; }
