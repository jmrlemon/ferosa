package com.example.ferosa_landscaping.util

import android.app.Activity
import com.google.ar.core.ArCoreApk
import com.google.ar.core.Session
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.delay
import kotlinx.coroutines.withContext
import kotlinx.coroutines.withTimeoutOrNull

/**
 * Represents the result of an ARCore availability check.
 */
sealed class ArAvailability {
    /** ARCore is installed and ready to use. */
    object Supported : ArAvailability()

    /** Device supports ARCore but services are not installed yet. */
    object SupportedNotInstalled : ArAvailability()

    /** Device does not support ARCore at all. */
    object Unsupported : ArAvailability()

    /** The availability check failed (timed out or errored). */
    data class CheckFailed(val reason: String) : ArAvailability()
}

/**
 * Wraps ARCore availability checks with a 3-second timeout.
 *
 * Use [checkAvailability] to determine device AR support, and [shouldShowArButton]
 * to combine ARCore availability with product model data for UI visibility decisions.
 *
 * Usage from a product screen or AR launch flow:
 * ```
 * val checker = ArCompatibilityChecker()
 * val availability = checker.checkAvailability(activity)
 * val showButton = checker.shouldShowArButton(
 *     availability = availability,
 *     hasPlantModel = product.plantModel != null
 * )
 * ```
 */
class ArCompatibilityChecker {

    companion object {
        /** Maximum time allowed for the ARCore availability check. */
        private const val CHECK_TIMEOUT_MS = 3000L
        private const val CHECK_POLL_INTERVAL_MS = 100L
    }

    /**
     * Checks ARCore availability on the device with a 3-second timeout.
     *
     * This method queries [ArCoreApk.checkAvailability] and maps the result to an
     * [ArAvailability] sealed class. If the check does not resolve within 3 seconds,
     * returns [ArAvailability.CheckFailed].
     *
     * @param activity The current Activity context required by ArCoreApk
     * @return The AR availability status of the device
     */
    suspend fun checkAvailability(activity: Activity): ArAvailability {
        return withContext(Dispatchers.Main) {
            val result = withTimeoutOrNull(CHECK_TIMEOUT_MS) {
                try {
                    var availability = ArCoreApk.getInstance().checkAvailability(activity)
                    while (availability == ArCoreApk.Availability.UNKNOWN_CHECKING) {
                        delay(CHECK_POLL_INTERVAL_MS)
                        availability = ArCoreApk.getInstance().checkAvailability(activity)
                    }
                    mapAvailability(availability)
                } catch (e: Exception) {
                    ArAvailability.CheckFailed(e.message ?: "Unknown error during AR check")
                }
            }
            result ?: ArAvailability.CheckFailed("ARCore availability check timed out")
        }
    }

    /**
     * Determines whether the "View in AR" button should be visible.
     *
     * The button is shown only when:
     * - ARCore is fully supported and installed on the device
     * - The product has an associated PlantModel (3D model available)
     *
     * For [ArAvailability.SupportedNotInstalled], the button is hidden until
     * the user installs ARCore services via [requestInstall].
     *
     * @param availability The result from [checkAvailability]
     * @param hasPlantModel Whether the product has a 3D model associated
     * @return true if the AR button should be displayed
     */
    fun shouldShowArButton(availability: ArAvailability, hasPlantModel: Boolean): Boolean {
        if (!hasPlantModel) return false
        return availability is ArAvailability.Supported
    }

    /**
     * Determines whether the user should be prompted to install ARCore services.
     *
     * @param availability The result from [checkAvailability]
     * @return true if the device supports ARCore but services are not installed
     */
    fun shouldPromptInstall(availability: ArAvailability): Boolean {
        return availability is ArAvailability.SupportedNotInstalled
    }

    /**
     * Requests ARCore Services installation via the Play Store.
     *
     * Call this when [shouldPromptInstall] returns true. This will show the user
     * an installation prompt from Google Play. The method returns true if the
     * installation was requested (user needs to complete it), or false if ARCore
     * is already installed.
     *
     * @param activity The Activity to use for the installation prompt
     * @param userRequestedInstall Pass true on the first call, false on subsequent
     *        calls (e.g., when returning from onResume after user action)
     * @return true if installation was requested and pending, false if already installed
     * @throws com.google.ar.core.exceptions.UnavailableException if installation is not possible
     */
    fun requestInstall(activity: Activity, userRequestedInstall: Boolean): Boolean {
        val installStatus = ArCoreApk.getInstance().requestInstall(activity, userRequestedInstall)
        return installStatus == ArCoreApk.InstallStatus.INSTALL_REQUESTED
    }

    /**
     * Verifies ARCore can create a real session on this device.
     *
     * Some devices report ARCore installed but fail when ARCore looks for the
     * device calibration/profile required to start camera tracking.
     */
    suspend fun validateSessionCreation(activity: Activity): String? {
        return withContext(Dispatchers.Main) {
            try {
                Session(activity).close()
                null
            } catch (e: Exception) {
                e.message ?: "This device cannot start an ARCore session."
            }
        }
    }

    /**
     * Maps the raw [ArCoreApk.Availability] enum to our [ArAvailability] sealed class.
     */
    private fun mapAvailability(availability: ArCoreApk.Availability): ArAvailability {
        return when {
            availability.isSupported -> {
                // isSupported is true for both SUPPORTED_INSTALLED and SUPPORTED_NOT_INSTALLED
                if (availability == ArCoreApk.Availability.SUPPORTED_INSTALLED) {
                    ArAvailability.Supported
                } else {
                    ArAvailability.SupportedNotInstalled
                }
            }
            availability == ArCoreApk.Availability.UNKNOWN_CHECKING -> {
                // Still checking — treat as a transient state; in practice the timeout handles this
                ArAvailability.CheckFailed("ARCore availability is still being determined")
            }
            availability == ArCoreApk.Availability.UNKNOWN_TIMED_OUT -> {
                ArAvailability.CheckFailed("ARCore availability check timed out on device")
            }
            else -> {
                // UNSUPPORTED_DEVICE_NOT_CAPABLE or UNKNOWN_ERROR
                ArAvailability.Unsupported
            }
        }
    }
}
