package com.example.ferosa_landscaping.data.api

import android.webkit.CookieManager
import okhttp3.Cookie
import okhttp3.CookieJar
import okhttp3.HttpUrl

/** Shares Laravel's authenticated WebView session with native Retrofit calls. */
class WebViewCookieJar(
    private val cookieManager: CookieManager = CookieManager.getInstance()
) : CookieJar {
    override fun saveFromResponse(url: HttpUrl, cookies: List<Cookie>) {
        cookies.forEach { cookie ->
            cookieManager.setCookie(url.toString(), cookie.toString())
        }
        cookieManager.flush()
    }

    override fun loadForRequest(url: HttpUrl): List<Cookie> {
        val header = cookieManager.getCookie(url.toString()) ?: return emptyList()

        return header.split(';')
            .mapNotNull { Cookie.parse(url, it.trim()) }
    }
}
