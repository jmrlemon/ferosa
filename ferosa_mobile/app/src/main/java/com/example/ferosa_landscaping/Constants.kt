package com.example.ferosa_landscaping

/**
 * Base URL for the Ferosa web server.
 *
 * Android Emulator: use 10.0.2.2 to reach the development computer.
 * Physical development device: use an ADB-reversed localhost port.
 *
 * The active value is configured as FEROSA_SERVER_URL in gradle.properties.
 */
val SERVER_URL: String = BuildConfig.SERVER_URL.trimEnd('/')

val API_BASE_URL: String = "$SERVER_URL/"
