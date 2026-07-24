package com.example.ferosa_landscaping

import com.example.ferosa_landscaping.data.api.ApiService
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test
import retrofit2.http.GET
import retrofit2.http.POST

class ApiContractTest {
    @Test
    fun `server base URL is valid for Retrofit`() {
        assertTrue(API_BASE_URL.startsWith("http://") || API_BASE_URL.startsWith("https://"))
        assertTrue(API_BASE_URL.endsWith('/'))
    }

    @Test
    fun `AR API paths match Laravel routes`() {
        val methods = ApiService::class.java.declaredMethods.associateBy { it.name }

        assertEquals("api/ar/products", requireNotNull(methods.getValue("getArProducts").getAnnotation(GET::class.java)).value)
        assertEquals("api/ar/products/{id}/model", requireNotNull(methods.getValue("downloadModel").getAnnotation(GET::class.java)).value)
        assertEquals("api/ar/products/{id}/model-info", requireNotNull(methods.getValue("getModelInfo").getAnnotation(GET::class.java)).value)
        assertEquals("api/ar/cart/add", requireNotNull(methods.getValue("addToCart").getAnnotation(POST::class.java)).value)
    }
}
