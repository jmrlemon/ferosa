package com.example.ferosa_landscaping.ui.web

import android.content.Context
import android.webkit.WebSettings
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CloudOff
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.core.net.toUri
import com.example.ferosa_landscaping.ui.theme.Brand50
import com.example.ferosa_landscaping.ui.theme.Brand600
import com.example.ferosa_landscaping.ui.theme.Surface50
import com.example.ferosa_landscaping.ui.theme.Surface500
import com.example.ferosa_landscaping.ui.theme.Surface900
import java.net.URI

/**
 * Injected on page commit/finish to fix Android WebView IME resetting the caret
 * to index 0 after each keystroke. Password fields were previously excluded
 * (`:not([type=password])`) but they hit the same bug on physical devices.
 */
internal val WEBVIEW_CURSOR_FIX_JS = """
(function(){
  // This runs from onPageFinished, which can fire when there is no document to
  // touch yet - an about:blank frame, or a page torn down by a fast tab switch.
  // Passing a null root to MutationObserver.observe() threw
  // "parameter 1 is not of type 'Node'" and aborted the rest of this script,
  // taking the in-app class and the caret fix with it.
  var root = document.body || document.documentElement;
  if(!root)return;

  function isPatchable(el){
    if(!el||el.tagName!=='INPUT')return false;
    var t=(el.getAttribute('type')||'text').toLowerCase();
    return ['checkbox','radio','hidden','button','submit','file','image','range','color','reset'].indexOf(t)<0;
  }

  if(document.body)document.body.classList.add('in-app');

  var patched=new WeakSet();
  function patch(el){
    if(!isPatchable(el)||patched.has(el))return;
    patched.add(el);
    el.setAttribute('dir','ltr');
    el.setAttribute('autocorrect','off');
    el.setAttribute('spellcheck','false');
    el.addEventListener('input',function(){
      var end=this.value.length;
      if(this.selectionStart===0&&end>0){
        try{this.setSelectionRange(end,end);}catch(e){}
      }
    },true);
  }

  // Re-running this injection must not stack a second observer on the page.
  if(window.__ferosaCursorFix)return;
  window.__ferosaCursorFix=true;

  try{
    Array.prototype.forEach.call(document.querySelectorAll('input'),patch);
    new MutationObserver(function(ms){
      ms.forEach(function(m){
        Array.prototype.forEach.call(m.addedNodes,function(n){
          if(n.tagName==='INPUT')patch(n);
          if(n.querySelectorAll)Array.prototype.forEach.call(n.querySelectorAll('input'),patch);
        });
      });
    }).observe(root,{childList:true,subtree:true});
  }catch(e){}
})();
""".trimIndent()

/** Marker the Laravel layouts look for to render the in-app (chrome-free) view. */
private const val FEROSA_APP_UA_MARKER = "FerosaApp/1.0"

internal const val WEBVIEW_LOADING_INDICATOR_DELAY_MS = 450L
internal const val WEBVIEW_LOAD_TIMEOUT_MS = 20_000L

/**
 * Appends the in-app marker to a WebView User-Agent, leaving it alone if it is
 * already there so a recreated WebView cannot stack duplicates.
 */
internal fun withFerosaAppMarker(userAgent: String?): String {
    val base = userAgent.orEmpty()
    return if (base.contains("FerosaApp")) base else "$base $FEROSA_APP_UA_MARKER".trim()
}

/**
 * The WebView text zoom to use, as a percentage.
 *
 * This used to be pinned to exactly 100 to dodge the Android IME cursor-at-0
 * bug, which had the side effect of throwing away the user's system font-size
 * setting across every web screen - most of the app. [WEBVIEW_CURSOR_FIX_JS] is
 * the actual mitigation for that bug and is still injected on every page, so
 * the zoom can follow the system again.
 *
 * The upper bound keeps the largest accessibility sizes from breaking the
 * Laravel layouts, which are not built to reflow past roughly double.
 */
internal fun systemTextZoomPercent(context: Context): Int =
    (context.resources.configuration.fontScale * 100).toInt().coerceIn(100, 200)

/**
 * Applies every setting shared by the login WebView and the main shell WebView,
 * so the two cannot drift apart.
 */
internal fun WebSettings.applyFerosaDefaults(context: Context, debugBuild: Boolean) {
    // The app is a shell around our own Laravel site, which needs JavaScript to
    // work at all. Navigation away from our host is handed to a Custom Tab / the
    // system browser (see shouldOverrideUrlLoading), so untrusted pages never
    // run inside this WebView.
    @Suppress("SetJavaScriptEnabled")
    javaScriptEnabled = true
    domStorageEnabled = true
    useWideViewPort = true
    loadWithOverviewMode = true
    // Lets the server render the in-app layout on the first paint. Without it
    // the site only learns it is running inside the app once the injected
    // script runs on page finish, so the web nav bar appears below the native
    // one for the whole load.
    userAgentString = withFerosaAppMarker(userAgentString)
    // Reuse cached CSS, JS, fonts and product images instead of refetching them
    // on every tab switch. The pages themselves send no-store headers, so
    // navigating still gets fresh data - this only spares the static assets.
    cacheMode = WebSettings.LOAD_DEFAULT
    textZoom = systemTextZoomPercent(context)
    // No flow loads a file:// URL. Leaving this on only widens what a
    // compromised page could reach on the device.
    allowFileAccess = false
    // Development serves the site over plain http on the LAN, which needs mixed
    // content allowed. A release build talks to https only (the Gradle build
    // enforces that), so there is no reason to keep the hole open there.
    @Suppress("DEPRECATION")
    mixedContentMode = if (debugBuild) {
        WebSettings.MIXED_CONTENT_ALWAYS_ALLOW
    } else {
        WebSettings.MIXED_CONTENT_NEVER_ALLOW
    }
}

/**
 * Whether a loaded URL is the page a tab asked for.
 *
 * Compares scheme, host, port and path, and requires every query parameter the
 * expected URL specifies to be present - so `/admin?tab=products` does not
 * match a bare `/admin`, while extra parameters the server adds are ignored.
 */
internal fun webDestinationMatches(actualUrl: String, expectedUrl: String): Boolean {
    val actual = actualUrl.toUri()
    val expected = expectedUrl.toUri()
    if (actual.scheme != expected.scheme ||
        actual.host != expected.host ||
        actual.port != expected.port ||
        actual.path?.trimEnd('/') != expected.path?.trimEnd('/')
    ) {
        return false
    }

    return expected.queryParameterNames.all { name ->
        actual.getQueryParameters(name) == expected.getQueryParameters(name)
    }
}

/**
 * Whether a WebView callback still belongs to the document the WebView is
 * displaying now.
 *
 * Android can deliver `onPageFinished` and error callbacks after a later
 * `loadUrl()` has already started. Treating one of those callbacks as current
 * lets the previous tab overwrite the new tab's readiness state. Requiring the
 * same complete query keeps destinations such as the admin tabs distinct while
 * still tolerating a trailing slash or fragment change.
 */
internal fun webCallbackMatchesCurrentDocument(
    callbackUrl: String,
    currentUrl: String?,
): Boolean {
    if (currentUrl == null) return false

    return runCatching {
        val callback = URI(callbackUrl)
        val current = URI(currentUrl)
        callback.scheme.equals(current.scheme, ignoreCase = true) &&
            callback.host.equals(current.host, ignoreCase = true) &&
            callback.port == current.port &&
            callback.path.trimEnd('/') == current.path.trimEnd('/') &&
            callback.rawQuery == current.rawQuery
    }.getOrDefault(false)
}

/**
 * Shown when the WebView cannot reach the server.
 *
 * Without this the WebView shows its own grey "webpage not available" page,
 * which looks like the app itself is broken.
 */
@Composable
internal fun ConnectionErrorScreen(onRetry: () -> Unit) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(Surface50)
            .padding(32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center,
    ) {
        Box(
            modifier = Modifier
                .size(72.dp)
                .clip(CircleShape)
                .background(Brand50),
            contentAlignment = Alignment.Center,
        ) {
            Icon(
                Icons.Default.CloudOff,
                contentDescription = null,
                tint = Brand600,
                modifier = Modifier.size(32.dp),
            )
        }
        Spacer(Modifier.height(20.dp))
        Text(
            "No connection",
            style = MaterialTheme.typography.titleLarge,
            color = Surface900,
            fontWeight = FontWeight.Bold,
        )
        Spacer(Modifier.height(8.dp))
        Text(
            "We couldn't reach Ferosa. Check your internet connection and try again.",
            style = MaterialTheme.typography.bodyMedium,
            color = Surface500,
            textAlign = TextAlign.Center,
            lineHeight = 21.sp,
        )
        Spacer(Modifier.height(24.dp))
        Button(
            onClick = onRetry,
            colors = ButtonDefaults.buttonColors(containerColor = Brand600),
            shape = RoundedCornerShape(12.dp),
            modifier = Modifier.defaultMinSize(minHeight = 48.dp),
        ) {
            Icon(Icons.Default.Refresh, contentDescription = null, modifier = Modifier.size(18.dp))
            Spacer(Modifier.width(8.dp))
            Text("Try again", fontWeight = FontWeight.SemiBold)
        }
    }
}
