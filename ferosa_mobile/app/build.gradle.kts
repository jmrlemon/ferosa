import java.util.Properties

plugins {
    alias(libs.plugins.android.application)
    alias(libs.plugins.kotlin.compose)
}

val ferosaServerUrl = providers.gradleProperty("FEROSA_SERVER_URL")
    .orElse(providers.environmentVariable("FEROSA_SERVER_URL"))
    .orElse("http://10.0.2.2/ferosa/ferosa-laravel/public")

// A physical Android device cannot use the emulator-only 10.0.2.2 address.
// Recreate the USB bridge before every debug build so Android Studio Run keeps
// the local XAMPP server available at http://127.0.0.1:8080.
val reverseDebugServerPort by tasks.registering(Exec::class) {
    group = "ferosa development"
    description = "Forwards the connected Android device port 8080 to XAMPP port 80."

    val localPropertiesFile = rootProject.file("local.properties")
    val localProperties = Properties().apply {
        if (localPropertiesFile.exists()) {
            localPropertiesFile.inputStream().use { input -> load(input) }
        }
    }
    val sdkDirectory = localProperties.getProperty("sdk.dir")
        ?: System.getenv("ANDROID_SDK_ROOT")
        ?: System.getenv("ANDROID_HOME")
    val adbName = if (System.getProperty("os.name").startsWith("Windows", ignoreCase = true)) "adb.exe" else "adb"
    val adbExecutable = sdkDirectory?.let { file("$it/platform-tools/$adbName").absolutePath } ?: adbName

    commandLine(adbExecutable, "reverse", "tcp:8080", "tcp:80")
    isIgnoreExitValue = true
}

tasks.matching { it.name == "preDebugBuild" }.configureEach {
    dependsOn(reverseDebugServerPort)
}

android {
    namespace = "com.example.ferosa_landscaping"
    compileSdk {
        version = release(36) {
            minorApiLevel = 1
        }
    }

    defaultConfig {
        applicationId = "com.example.ferosa_landscaping"
        minSdk = 24
        targetSdk = 36
        versionCode = 1
        versionName = "1.0"

        testInstrumentationRunner = "androidx.test.runner.AndroidJUnitRunner"
        buildConfigField("String", "SERVER_URL", "\"${ferosaServerUrl.get().trimEnd('/')}\"")
        manifestPlaceholders["usesCleartextTraffic"] = "true"
    }

    buildTypes {
        debug {
            manifestPlaceholders["usesCleartextTraffic"] = "true"
        }
        release {
            isMinifyEnabled = true
            isShrinkResources = true
            manifestPlaceholders["usesCleartextTraffic"] = "false"
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
        }
    }
    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_11
        targetCompatibility = JavaVersion.VERSION_11
    }
    buildFeatures {
        compose = true
        buildConfig = true
    }
}

dependencies {
    implementation(libs.androidx.core.ktx)
    implementation(libs.androidx.lifecycle.runtime.ktx)
    implementation(libs.androidx.lifecycle.runtime.compose)
    implementation(libs.androidx.activity.compose)
    implementation(platform(libs.androidx.compose.bom))
    implementation(libs.androidx.compose.ui)
    implementation(libs.androidx.compose.ui.graphics)
    implementation(libs.androidx.compose.ui.tooling.preview)
    implementation(libs.androidx.compose.material3)
    implementation(libs.sceneview.arsceneview)
    implementation(libs.androidx.compose.material.icons.extended)
    implementation(libs.androidx.browser)
    implementation(libs.retrofit.core)
    implementation(libs.retrofit.gson)
    implementation(libs.okhttp.core)
    implementation(libs.okhttp.logging)
    implementation(libs.gson)
    implementation(libs.coil.compose)
    implementation(libs.androidx.lifecycle.viewmodel.compose)
    testImplementation(libs.junit)
    androidTestImplementation(libs.androidx.junit)
    androidTestImplementation(libs.androidx.espresso.core)
    androidTestImplementation(platform(libs.androidx.compose.bom))
    androidTestImplementation(libs.androidx.compose.ui.test.junit4)
    debugImplementation(libs.androidx.compose.ui.tooling)
    debugImplementation(libs.androidx.compose.ui.test.manifest)
}
