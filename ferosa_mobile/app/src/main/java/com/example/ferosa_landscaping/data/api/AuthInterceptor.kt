package com.example.ferosa_landscaping.data.api

import android.webkit.CookieManager
import okhttp3.Interceptor
import okhttp3.Response
import java.net.URLDecoder
import java.nio.charset.StandardCharsets

class AuthInterceptor : Interceptor {
    override fun intercept(chain: Interceptor.Chain): Response {
        val original = chain.request()
        val request = original.newBuilder().apply {
            addHeader("Accept", "application/json")

            CookieManager.getInstance().getCookie(original.url.toString())
                ?.split(';')
                ?.map { it.trim() }
                ?.firstOrNull { it.startsWith("XSRF-TOKEN=") }
                ?.substringAfter('=')
                ?.let { URLDecoder.decode(it, StandardCharsets.UTF_8.name()) }
                ?.let { addHeader("X-XSRF-TOKEN", it) }
        }.build()
        return chain.proceed(request)
    }
}
