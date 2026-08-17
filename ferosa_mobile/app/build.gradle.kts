import java.util.Properties

plugins {
    alias(libs.plugins.android.application)
    alias(libs.plugins.kotlin.compose)
}

val localProperties = Properties().apply {
    val localPropertiesFile = rootProject.file("local.properties")
    if (localPropertiesFile.isFile) {
        localPropertiesFile.inputStream().use { load(it) }
    }
}

val ferosaServerUrl = providers.gradleProperty("FEROSA_SERVER_URL")
    .orElse(providers.environmentVariable("FEROSA_SERVER_URL"))
    .orElse(localProperties.getProperty("FEROSA_SERVER_URL") ?: "http://10.0.2.2/ferosa/ferosa-laravel/public")

/**
 * A release build sets `usesCleartextTraffic=false`, so an APK built against the
 * cleartext development default installs fine and then fails every single
 * request with no visible reason. Fail the build instead of shipping that.
 */
val verifyReleaseServerUrl = tasks.register("verifyReleaseServerUrl") {
    group = "verification"
    description = "Ensures release builds point at an https:// server."

    val url = ferosaServerUrl
    doLast {
        val value = url.get()
        check(value.startsWith("https://")) {
            "FEROSA_SERVER_URL must be https:// for release builds (got \"$value\"). " +
                "Release APKs disable cleartext traffic, so an http:// server cannot be reached. " +
                "Pass -PFEROSA_SERVER_URL=https://<host>/path when assembling."
        }
    }
}

tasks.matching { it.name == "assembleRelease" || it.name == "bundleRelease" }
    .configureEach { dependsOn(verifyReleaseServerUrl) }

android {
    namespace = "com.example.ferosa_landscaping"
    compileSdk {
        version = release(36) {
            minorApiLevel = 1
        }
    }

    defaultConfig {
        // `com.example.*` is rejected by the Play Store. The Kotlin package
        // (`namespace`, below) is deliberately left alone - it is internal, and
        // renaming it would touch every file for no functional gain.
        applicationId = "com.ferosa.landscaping"
        minSdk = 24
        targetSdk = 36
        versionCode = 2
        versionName = "1.0.0"

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
    implementation(libs.androidx.core.splashscreen)
    implementation(libs.androidx.swiperefreshlayout)
    implementation(libs.androidx.work.runtime.ktx)
    implementation(libs.androidx.datastore.preferences)
    testImplementation(libs.junit)
    androidTestImplementation(libs.androidx.junit)
    androidTestImplementation(libs.androidx.espresso.core)
    androidTestImplementation(platform(libs.androidx.compose.bom))
    androidTestImplementation(libs.androidx.compose.ui.test.junit4)
    debugImplementation(libs.androidx.compose.ui.tooling)
    debugImplementation(libs.androidx.compose.ui.test.manifest)
}
