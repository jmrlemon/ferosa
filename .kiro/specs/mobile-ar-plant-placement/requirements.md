# Requirements Document

## Introduction

This document specifies the requirements for a native Android Augmented Reality (AR) plant placement feature for the Ferosa platform. The feature is a standalone Android application built with Kotlin and Google ARCore that enables customers to visualize plant products from the Ferosa catalog overlaid onto real-world surfaces using their device camera. Users can browse available plants, place them in AR on detected surfaces, and reposition them via touch gestures to preview how plants would look in their physical space before purchasing. The Android app communicates with the existing Ferosa Laravel backend via REST API for product catalog data, 3D model downloads, and cart operations.

## Glossary

- **AR_Activity**: The native Android Activity (or Fragment) that hosts the ARCore session and renders 3D plant models over the device camera feed using Sceneform or ARCore rendering APIs
- **Surface_Detector**: The ARCore plane detection subsystem responsible for identifying flat horizontal surfaces (floors, tables, shelves) in the camera feed using ARCore Plane Discovery
- **Plant_Model**: A 3D representation (glTF/GLB format) of a product from the Ferosa catalog, downloaded from the Laravel backend and scaled to approximate real-world dimensions
- **AR_Session**: An active Google ARCore session running on the user's Android device with camera tracking and plane detection enabled
- **Product_Catalog**: The existing Ferosa Laravel backend product database containing plant items with names, descriptions, images, prices, and categories, accessed via REST API
- **Placement_Anchor**: An ARCore Anchor fixed to a detected Plane where a Plant_Model is positioned in the AR scene
- **API_Client**: The Android networking layer (Retrofit/OkHttp) responsible for communicating with the Ferosa Laravel backend to fetch product data, download 3D model assets, and perform cart operations
- **Ferosa_Backend**: The existing Laravel application that serves product data, 3D model files, and handles shopping cart operations via REST API endpoints
- **Gesture_Handler**: The Android native touch gesture detection system (ScaleGestureDetector, GestureDetector) that processes tap, long-press, and drag interactions in the AR scene

## Requirements

### Requirement 1: AR Session Initialization

**User Story:** As a customer, I want to launch the AR experience from a product screen, so that I can see how a plant looks in my physical space.

#### Acceptance Criteria

1. WHEN the user taps the "View in AR" button on a product screen, THE AR_Activity SHALL display a loading indicator and request camera permission via Android runtime permissions, then initialize a Google ARCore session within 10 seconds
2. IF the device does not support ARCore or does not have ARCore Services installed, THEN THE AR_Activity SHALL display a message indicating that AR is not supported on the current device and remain on the product screen
3. IF the user denies camera permission, THEN THE AR_Activity SHALL display a message explaining that camera access is required for the AR feature and remain on the product screen
4. WHEN the AR_Session starts successfully, THE AR_Activity SHALL hide the loading indicator and display the live camera feed as the background of the AR scene via ARCore camera frame rendering
5. IF the AR_Session fails to initialize for any reason other than denied permission or unsupported device, THEN THE AR_Activity SHALL hide the loading indicator, display an error message indicating the session could not be started, and offer a retry option
6. IF the AR_Session does not initialize within 10 seconds, THEN THE AR_Activity SHALL abort the session request, hide the loading indicator, display a timeout error message, and offer a retry option

### Requirement 2: Surface Detection

**User Story:** As a customer, I want the app to detect flat surfaces in my space, so that I can place plants on them realistically.

#### Acceptance Criteria

1. WHILE the AR_Session is active, THE Surface_Detector SHALL scan for horizontal flat surfaces in the camera feed using ARCore Plane Discovery on each frame update
2. WHEN the Surface_Detector identifies a valid ARCore Plane with a minimum estimated area of 0.04 square meters (20cm × 20cm), THE AR_Activity SHALL display a visual reticle indicator on the detected surface using ARCore hit-test results
3. WHILE no surface is detected, THE AR_Activity SHALL display guidance text instructing the user to point the camera at a flat surface and move the device slowly
4. THE Surface_Detector SHALL detect surfaces within a range of 0.5 meters to 5 meters from the device camera as reported by ARCore depth estimation
5. IF the Surface_Detector loses tracking of a previously detected surface due to ARCore TrackingState changing to PAUSED or STOPPED, THEN THE AR_Activity SHALL remove the reticle and display the guidance text until a surface is detected again

### Requirement 3: Plant Model Loading and Display

**User Story:** As a customer, I want to see a realistic 3D model of a plant product in AR, so that I can evaluate its appearance in my space.

#### Acceptance Criteria

1. WHEN the user selects a product for AR viewing, THE API_Client SHALL download the associated Plant_Model in glTF or GLB format from the Ferosa_Backend REST API within 10 seconds of the request
2. THE Plant_Model SHALL be scaled using the admin-configured height value (in centimeters) so that the rendered model height is within ±10% of the specified real-world dimension
3. WHEN the Plant_Model is loaded successfully, THE AR_Activity SHALL render the model at the current reticle position on the detected surface using Sceneform or ARCore rendering
4. IF the Plant_Model fails to download or the loading time exceeds 15 seconds, THEN THE AR_Activity SHALL display an error message indicating the model could not be loaded and offer a retry option allowing up to 3 retry attempts
5. WHILE the Plant_Model is downloading and loading, THE AR_Activity SHALL display a loading indicator to the user
6. IF all 3 retry attempts fail, THEN THE AR_Activity SHALL display an error message indicating the model is unavailable and provide an option to return to the product screen

### Requirement 4: Plant Placement on Surface

**User Story:** As a customer, I want to place a plant on a detected surface by tapping, so that I can anchor it in a specific position.

#### Acceptance Criteria

1. WHEN the user taps the screen while the reticle is displayed on a detected surface and a Plant_Model is attached to the reticle, THE AR_Activity SHALL create a Placement_Anchor at the ARCore hit-test result position, attach the Plant_Model to the anchor, and display a brief visual confirmation of successful placement
2. WHEN a Plant_Model is placed, THE AR_Activity SHALL anchor the model using an ARCore Anchor so the model remains positionally fixed relative to the detected Plane as the user moves the device
3. THE AR_Activity SHALL allow the user to place up to 5 Plant_Models simultaneously in a single AR_Session, including multiple instances of the same product
4. IF the user attempts to place a Plant_Model when 5 Plant_Models are already placed, THEN THE AR_Activity SHALL display a message indicating the maximum placement limit has been reached and SHALL NOT place the model
5. WHEN the user selects a placed Plant_Model and activates the delete action, THE AR_Activity SHALL detach the ARCore Anchor, remove the Plant_Model from the AR scene, and free the placement slot
6. IF the user taps the screen while no Plant_Model is loaded or attached to the reticle, THEN THE AR_Activity SHALL not place any model and SHALL display guidance prompting the user to select a product from the catalog drawer

### Requirement 5: Drag-and-Drop Repositioning

**User Story:** As a customer, I want to drag placed plants to new positions, so that I can experiment with different arrangements.

#### Acceptance Criteria

1. WHEN the user performs a long-press gesture of at least 500 milliseconds on a placed Plant_Model detected via the Gesture_Handler, THE AR_Activity SHALL enter repositioning mode for that model indicated by a visible outline or glow effect around the model
2. WHILE in repositioning mode, WHEN the user drags across the screen, THE AR_Activity SHALL perform ARCore hit-tests along the user's finger position and move the selected Plant_Model along detected surfaces in real time
3. WHILE in repositioning mode, WHEN the user lifts their finger over a detected surface, THE AR_Activity SHALL create a new ARCore Anchor at the final position, attach the Plant_Model to the new anchor, detach the old anchor, and exit repositioning mode
4. IF the user drags the Plant_Model to an area with no detected surface (ARCore hit-test returns no result), THEN THE AR_Activity SHALL display the model at reduced opacity and snap it back to the last valid surface position when the user lifts their finger
5. IF the user taps outside the selected Plant_Model while in repositioning mode, THEN THE AR_Activity SHALL cancel repositioning and return the model to its original anchor position before repositioning began

### Requirement 6: Product Information Overlay

**User Story:** As a customer, I want to see product details while viewing a plant in AR, so that I can make an informed purchase decision.

#### Acceptance Criteria

1. WHEN the user taps on a placed Plant_Model while no product information panel is currently displayed, THE AR_Activity SHALL display a product information panel (Android View overlay) showing the product name, price, and an "Add to Cart" button within 1 second of the tap
2. WHEN the user taps on a different placed Plant_Model while a product information panel is already displayed, THE AR_Activity SHALL dismiss the current panel and display a new product information panel for the tapped Plant_Model
3. WHEN the user taps the "Add to Cart" button in the product information panel, THE API_Client SHALL send an add-to-cart request to the Ferosa_Backend REST API for a quantity of 1 of the corresponding product and upon success THE AR_Activity SHALL display a visible confirmation indicating the item was added
4. IF the add-to-cart operation fails due to a network error or the Ferosa_Backend returning an error response, THEN THE AR_Activity SHALL display an error message indicating the item could not be added to the cart and the product information panel SHALL remain open
5. WHEN the user taps outside the product information panel, THE AR_Activity SHALL dismiss the panel

### Requirement 7: AR Product Catalog Browser

**User Story:** As a customer, I want to browse and select different plants within the AR session, so that I can compare multiple products in my space without leaving AR.

#### Acceptance Criteria

1. WHILE the AR_Session is active, THE AR_Activity SHALL display a horizontally scrollable product catalog drawer (RecyclerView) at the bottom of the screen with touch targets of at least 48x48 dp for each catalog item per Material Design guidelines
2. THE API_Client SHALL fetch from the Ferosa_Backend only products that have an associated Plant_Model available for AR viewing
3. WHEN the user selects a different product from the catalog drawer, THE AR_Activity SHALL replace any currently unplaced Plant_Model on the reticle with the newly selected product's Plant_Model within 3 seconds
4. THE AR_Activity SHALL display a thumbnail image, product name (truncated to 25 characters with ellipsis if longer), and price for each item in the catalog drawer
5. IF the Plant_Model fails to download after the user selects a product from the catalog drawer, THEN THE AR_Activity SHALL display an error message indicating the model could not be loaded and retain the previously active Plant_Model on the reticle
6. WHILE a new Plant_Model is downloading after catalog selection, THE AR_Activity SHALL display a loading indicator on the reticle position

### Requirement 8: AR Session Management

**User Story:** As a customer, I want to cleanly exit the AR experience, so that I can return to normal browsing.

#### Acceptance Criteria

1. WHILE the AR_Session is active, THE AR_Activity SHALL display a close button with a minimum tap target of 48x48 dp per Material Design guidelines in the top-right corner of the screen
2. WHEN the user taps the close button, THE AR_Activity SHALL end the AR_Session, pause ARCore, release the camera, preserve any items added to the shopping cart during the session, and navigate the user back to the product screen within 2 seconds
3. WHEN the AR_Session ends unexpectedly due to an ARCore error or device interruption, THE AR_Activity SHALL display an error message indicating that the AR session was interrupted and provide a button to return to the product screen
4. WHEN the user presses the Android back button or the Activity is destroyed by the system, THE AR_Activity SHALL end the AR_Session, release the camera, and detach all ARCore Anchors within 2 seconds
5. IF the AR_Session cleanup does not complete within 5 seconds after an exit action, THEN THE AR_Activity SHALL force-release all camera and ARCore resources and navigate the user back to the product screen

### Requirement 9: 3D Model Administration

**User Story:** As an admin, I want to upload and manage 3D models for products, so that customers can view them in AR.

#### Acceptance Criteria

1. WHEN an admin navigates to a product's edit page in the Ferosa_Backend admin panel, THE Admin_Panel SHALL display a section for uploading a Plant_Model file in glTF or GLB format
2. WHEN an admin uploads a Plant_Model file, THE Admin_Panel SHALL validate that the file has a .gltf or .glb file extension, does not exceed 10 MB in size, and is parseable as a valid glTF 2.0 asset
3. IF the uploaded file has an unsupported extension, exceeds 10 MB, or is not parseable as a valid glTF 2.0 asset, THEN THE Admin_Panel SHALL display an error message indicating which specific validation check failed
4. WHEN a valid Plant_Model is uploaded successfully, THE Admin_Panel SHALL associate the model with the product, store the file accessible via a REST API endpoint, and mark the product as AR-enabled
5. WHEN an admin sets real-world dimensions for a Plant_Model, THE Admin_Panel SHALL accept a height value in centimeters between 1 and 500 and require the height to be set before the product can be marked as AR-enabled
6. WHEN a product already has an associated Plant_Model and the admin uploads a new file, THE Admin_Panel SHALL replace the existing model with the new file after successful validation
7. WHEN the admin removes an associated Plant_Model from a product, THE Admin_Panel SHALL delete the model file, remove the AR-enabled status from the product, and clear the stored height dimension

### Requirement 10: Device Compatibility and ARCore Availability

**User Story:** As a customer, I want to know immediately if my device supports AR, so that I do not waste time attempting to use an unsupported feature.

#### Acceptance Criteria

1. WHEN the product screen loads, THE AR_Activity SHALL check if ARCore is supported and installed on the device using ArCoreApk.checkAvailability() and resolve the check within 3 seconds, hiding the "View in AR" button until the check completes
2. IF the device supports ARCore and has ARCore Services installed, and the product has an associated Plant_Model, THEN THE AR_Activity SHALL display the "View in AR" button on the product screen
3. IF the device does not support ARCore, or ARCore Services are not installed and cannot be installed, or the product does not have an associated Plant_Model, THEN THE AR_Activity SHALL hide the "View in AR" button and display no AR-related UI elements
4. IF ARCore Services are supported but not installed, THEN THE AR_Activity SHALL prompt the user to install ARCore Services from the Google Play Store before enabling the "View in AR" button

### Requirement 11: Backend API Integration

**User Story:** As a customer, I want the AR app to seamlessly connect to the Ferosa product catalog, so that I can view up-to-date product information and add items to my cart.

#### Acceptance Criteria

1. WHEN the AR_Activity is launched, THE API_Client SHALL authenticate with the Ferosa_Backend using the user's existing session token or API key within 5 seconds of the launch event
2. IF authentication fails due to an expired token, invalid credentials, or a network error, THEN THE AR_Activity SHALL display an error message indicating the user could not be authenticated and provide an option to return to the product screen
3. WHEN authentication succeeds, THE API_Client SHALL fetch the list of AR-enabled products from the Ferosa_Backend GET /api/products?ar_enabled=true endpoint within 10 seconds and cache the response locally for the duration of the AR_Session
4. WHEN the user selects a product, THE API_Client SHALL download the Plant_Model file from the Ferosa_Backend GET /api/products/{id}/model endpoint
5. IF the API_Client receives a network error or HTTP error response from the Ferosa_Backend for any request other than authentication, THEN THE AR_Activity SHALL display an error message indicating which operation failed and offer a retry option allowing up to 3 retry attempts
6. THE API_Client SHALL include the user's session token or API key in the Authorization header of all requests to the Ferosa_Backend REST API
7. WHEN the user adds an item to the cart, THE API_Client SHALL send a POST request to the Ferosa_Backend POST /api/cart/add endpoint with the product ID and quantity within 5 seconds

### Requirement 12: Model Caching and Offline Behavior

**User Story:** As a customer, I want previously viewed 3D models to load quickly on subsequent views, so that I have a smooth experience without repeated downloads.

#### Acceptance Criteria

1. WHEN a Plant_Model is successfully downloaded, THE API_Client SHALL cache the model file in the Android device's internal storage with a maximum total cache size of 200 MB, recording the download timestamp and file size as cache metadata
2. WHEN the user selects a product whose Plant_Model is already cached and the cache entry is less than 7 days old, THE AR_Activity SHALL load the model from the local cache without making a network request to the Ferosa_Backend and render it within 2 seconds of selection
3. IF the user selects a product whose cached Plant_Model has a download timestamp older than 7 days and network connectivity is available, THEN THE API_Client SHALL check the Ferosa_Backend for an updated version and download the new version if available, while displaying a loading indicator during the check
4. IF the user selects a product whose cached Plant_Model has a download timestamp older than 7 days and network connectivity is not available, THEN THE AR_Activity SHALL load the stale cached model and display a notice indicating the model may not reflect the latest version
5. IF the device has no network connectivity when launching the AR feature, THEN THE AR_Activity SHALL display only products whose Plant_Models are available in the local cache and display a persistent banner in the catalog drawer indicating the device is offline and the catalog may be incomplete
6. IF the total cache size would exceed 200 MB after writing a new model file, THEN THE API_Client SHALL remove the least recently used cached model files until sufficient space is available before writing the new file
