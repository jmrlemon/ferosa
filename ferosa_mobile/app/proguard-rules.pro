# Add project specific ProGuard rules here.

# Keep line numbers for crash reports
-keepattributes SourceFile,LineNumberTable
-renamesourcefileattribute SourceFile

# ─── Retrofit / Gson ────────────────────────────────────────────────────────
# Keep Retrofit service interfaces
-keep,allowobfuscation interface com.example.ferosa_landscaping.data.api.ApiService

# Keep Gson serialized model classes (DTOs)
-keep class com.example.ferosa_landscaping.data.api.models.** { *; }

# Retrofit needs these
-keepattributes Signature
-keepattributes *Annotation*
-keep class retrofit2.** { *; }
-keepclasseswithmembers class * {
    @retrofit2.http.* <methods>;
}

# Gson
-keep class com.google.gson.** { *; }
-keepclassmembers class * {
    @com.google.gson.annotations.SerializedName <fields>;
}

# ─── OkHttp ─────────────────────────────────────────────────────────────────
-dontwarn okhttp3.**
-dontwarn okio.**
-keep class okhttp3.** { *; }

# ─── SceneView / ARCore ─────────────────────────────────────────────────────
-keep class io.github.sceneview.** { *; }
-keep class com.google.ar.** { *; }
-dontwarn com.google.ar.**

# ─── ModelCacheManager metadata (serialized with Gson) ───────────────────────
-keep class com.example.ferosa_landscaping.data.cache.ModelCacheManager$CacheEntry { *; }

# ─── Kotlin coroutines ──────────────────────────────────────────────────────
-dontwarn kotlinx.coroutines.**
