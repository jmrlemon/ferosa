# Implementation Plan: Mobile AR Plant Placement

## Overview

This plan upgrades the existing Ferosa Android AR experience from a hardcoded demo to a fully integrated product visualization system backed by the Laravel API. The Laravel backend is built first (migration, model, controller, routes, admin panel) since the Android app depends on it. The Android side then adds Retrofit/OkHttp networking, model caching, ViewModel-driven architecture, refactored UI, improved gestures, and device compatibility checks.

## Tasks

- [ ] 1. Laravel backend: migration, model, and AR controller
  - [x] 1.1 Create `plant_models` migration and `PlantModel` Eloquent model
    - Create migration file `database/migrations/xxxx_create_plant_models_table.php` with columns: `id`, `product_id` (foreign key, unique, cascade delete), `file_path`, `file_name`, `file_size` (unsigned big integer), `height_cm` (decimal 6,1), `timestamps`
    - Create `app/Models/PlantModel.php` with `$fillable`, `product()` BelongsTo relationship
    - Add `plantModel()` HasOne relationship to existing `app/Models/Product.php`
    - _Requirements: 9.1, 9.4, 9.5_

  - [-] 1.2 Create `ArController` with REST API endpoints
    - Create `app/Http/Controllers/ArController.php`
    - Implement `index()`: GET `/api/ar/products` — returns only products with an associated PlantModel that are active and not archived, mapping to the JSON structure defined in design (id, name, description, price, thumbnail_url, model_url, height_cm, category, in_stock)
    - Implement `downloadModel()`: GET `/api/ar/products/{product}/model` — streams the GLB file from storage with proper headers
    - Implement `modelInfo()`: GET `/api/ar/products/{product}/model-info` — returns `updated_at` and `file_size` for cache freshness checks
    - Implement `addToCart()`: POST `/api/cart/add` — validates product_id and quantity, checks stock, adds to cart
    - _Requirements: 7.2, 11.3, 11.4, 11.7, 3.1_

  - [-] 1.3 Register API routes in `routes/api.php`
    - Add `auth:sanctum` middleware group with `ar` prefix for product listing, model download, and model-info endpoints
    - Add `auth:sanctum` middleware route for `POST /cart/add`
    - _Requirements: 11.1, 11.6_

  - [ ]* 1.4 Write unit tests for ArController
    - Test `index()` returns only AR-enabled, active, non-archived products
    - Test `downloadModel()` returns 404 when product has no PlantModel
    - Test `addToCart()` validates product_id, rejects out-of-stock items with 422
    - Test unauthenticated requests return 401
    - _Requirements: 7.2, 11.5, 11.6_

- [x] 2. Laravel backend: admin panel 3D model upload
  - [x] 2.1 Create admin panel view and controller logic for AR model management
    - Create `resources/views/admin/partials/ar-models.blade.php` with file upload form (accepts .glb/.gltf), height_cm input field (1–500), and delete button
    - Extend `AdminController.php` (or create dedicated methods) to handle model upload with validation: file extension (.glb/.gltf), max size 10MB, height_cm between 1 and 500
    - Implement model replacement (delete old file when new one uploaded)
    - Implement model removal (delete file, remove PlantModel record)
    - Store uploaded files in `storage/app/public/ar-models/`
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6, 9.7_

  - [ ]* 2.2 Write property test for model upload validation
    - **Property 7: Model Upload Validation**
    - **Validates: Requirements 9.2, 9.3**

  - [ ]* 2.3 Write property test for height dimension validation
    - **Property 8: Height Dimension Validation**
    - **Validates: Requirements 9.5**

- [x] 3. Checkpoint - Laravel backend complete
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 4. Android: add networking dependencies and API layer
  - [x] 4.1 Add Retrofit, OkHttp, Gson, Coil, and ViewModel dependencies
    - Add version entries to `gradle/libs.versions.toml`: retrofit 2.11.0, okhttp 4.12.0, gson 2.11.0, coil 2.7.0
    - Add library entries: retrofit-core, retrofit-gson, okhttp-core, okhttp-logging, gson, coil-compose, androidx-lifecycle-viewmodel-compose
    - Add `implementation` lines to `app/build.gradle.kts`
    - Add `ACCESS_NETWORK_STATE` permission to `AndroidManifest.xml`
    - _Requirements: 11.1, 12.1_

  - [-] 4.2 Create `ApiService` Retrofit interface and `AuthInterceptor`
    - Create `app/src/main/java/com/example/ferosa_landscaping/data/api/ApiService.kt` with endpoints: `getArProducts()`, `downloadModel()`, `addToCart()`, `getModelInfo()`
    - Create `app/src/main/java/com/example/ferosa_landscaping/data/api/AuthInterceptor.kt` — adds Bearer token and Accept: application/json headers to all requests
    - Create `app/src/main/java/com/example/ferosa_landscaping/data/api/models/ArProductDto.kt` with DTOs: `ArProductDto`, `AddToCartRequest`, `CartResponse`, `ModelInfoDto`
    - Add `API_BASE_URL` constant to `Constants.kt` derived from `SERVER_URL`
    - _Requirements: 11.1, 11.6, 11.3, 11.4_

  - [ ]* 4.3 Write property test for authorization header inclusion
    - **Property 9: Authorization Header Inclusion**
    - **Validates: Requirements 11.6**

- [x] 5. Android: model caching layer
  - [x] 5.1 Implement `ModelCacheManager`
    - Create `app/src/main/java/com/example/ferosa_landscaping/data/cache/ModelCacheManager.kt`
    - Implement cache storage in app internal storage with JSON metadata file tracking: productId, fileName, downloadedAt (epoch ms), fileSize, lastAccessedAt
    - Implement `getCachedModel()`: returns File if cached and fresh (< 7 days)
    - Implement `cacheModel()`: writes file + updates metadata
    - Implement `isFresh()`: checks if age < 7 days
    - Implement `evictLRU()`: removes least recently accessed entries until space available
    - Implement `getTotalCacheSize()`: sums all cached file sizes
    - Enforce 200 MB total cache limit
    - _Requirements: 12.1, 12.2, 12.6_

  - [ ]* 5.2 Write property test for cache write with metadata
    - **Property 10: Cache Write with Metadata**
    - **Validates: Requirements 12.1**

  - [ ]* 5.3 Write property test for cache freshness decision
    - **Property 11: Cache Freshness Decision**
    - **Validates: Requirements 12.2, 12.3**

  - [ ]* 5.4 Write property test for LRU cache eviction
    - **Property 13: LRU Cache Eviction Maintains Size Limit**
    - **Validates: Requirements 12.6**

- [x] 6. Android: repositories, ViewModel, and state management
  - [x] 6.1 Create `ModelRepository` and `CartRepository`
    - Create `app/src/main/java/com/example/ferosa_landscaping/data/repository/ModelRepository.kt` — coordinates API fetch + cache: check cache freshness, download if needed, return local File path
    - Create `app/src/main/java/com/example/ferosa_landscaping/data/repository/CartRepository.kt` — wraps addToCart API call with error handling
    - Create `app/src/main/java/com/example/ferosa_landscaping/util/ConnectivityMonitor.kt` — observes network state via ConnectivityManager
    - _Requirements: 11.3, 11.4, 11.7, 12.2, 12.3, 12.4, 12.5_

  - [x] 6.2 Create `ArViewModel` with state management
    - Create `app/src/main/java/com/example/ferosa_landscaping/ui/ar/ArViewModel.kt`
    - Expose StateFlows: `products`, `selectedProduct`, `placedModels`, `isLoading`, `error`, `isOffline`, `repositioningModel`, `selectedInfoModel`, `canPlace`
    - Implement actions: `loadProducts()`, `selectProduct()`, `placeModel()`, `deleteModel()`, `startRepositioning()`, `finishRepositioning()`, `cancelRepositioning()`, `addToCart()`, `showProductInfo()`, `dismissProductInfo()`
    - Enforce placement limit invariant: `placedModels.size <= 5`
    - Create `app/src/main/java/com/example/ferosa_landscaping/ui/ar/ArProductState.kt` with `ArProduct`, `PlacedModel`, and `ArScreenState` sealed class
    - _Requirements: 4.3, 4.4, 4.5, 5.1, 5.3, 5.4, 5.5, 6.1, 6.5_

  - [ ]* 6.3 Write property test for placement limit invariant
    - **Property 2: Placement Limit Invariant**
    - **Validates: Requirements 4.3, 4.4**

  - [ ]* 6.4 Write property test for delete frees placement slot
    - **Property 3: Delete Frees Placement Slot**
    - **Validates: Requirements 4.5**

  - [ ]* 6.5 Write property test for offline catalog shows only cached
    - **Property 12: Offline Catalog Shows Only Cached**
    - **Validates: Requirements 12.5**

- [x] 7. Checkpoint - Android ViewModel and data layers complete
  - Ensure all tests pass, ask the user if questions arise.

- [x] 8. Android: refactor ArActivity UI to use ViewModel
  - [x] 8.1 Refactor `ArActivity.kt` to inject `ArViewModel` and wire Compose state
    - Remove hardcoded `GARDEN_MODELS` list and `GardenModel` data class
    - Remove `PlacedElement` data class (replaced by `PlacedModel` in state)
    - Inject `ArViewModel` using `viewModel()` or manual creation
    - Collect ViewModel StateFlows in Compose (`collectAsState()`)
    - Wire loading, error, and ready states to existing UI structure
    - Preserve existing screenshot functionality and top bar
    - Preserve existing permission handling and lifecycle management
    - _Requirements: 1.1, 1.4, 1.5, 1.6, 8.1, 8.2, 8.3, 8.4, 8.5_

  - [x] 8.2 Create `CatalogDrawer` composable loading products from API
    - Create `app/src/main/java/com/example/ferosa_landscaping/ui/ar/components/CatalogDrawer.kt`
    - Horizontal LazyRow with thumbnail (loaded via Coil), product name (truncated to 25 chars with ellipsis), and price
    - Minimum 48x48 dp touch targets per Material Design
    - Show loading indicator when model is downloading after selection
    - Show offline banner when device is offline with cached-only catalog
    - Replace existing `ModelPickerStrip` usage in ArActivity
    - _Requirements: 7.1, 7.3, 7.4, 7.5, 7.6, 12.5_

  - [ ]* 8.3 Write property test for product name truncation
    - **Property 6: Product Name Truncation**
    - **Validates: Requirements 7.4**

  - [x] 8.4 Create `ProductInfoPanel` composable overlay
    - Create `app/src/main/java/com/example/ferosa_landscaping/ui/ar/components/ProductInfoPanel.kt`
    - Display product name, formatted price, "Add to Cart" button
    - Show success confirmation on cart addition
    - Show error message on cart failure, keep panel open
    - Dismiss when tapping outside
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

  - [ ]* 8.5 Write property test for product info panel completeness
    - **Property 4: Product Info Panel Completeness**
    - **Validates: Requirements 6.1**

- [x] 9. Android: gesture upgrade with raycasting
  - [x] 9.1 Implement raycasting-based model selection and real-time drag
    - Replace current `onLongPress` (selects last element) with `findPlacedModelAtPosition()` using SceneView node hit-testing to identify which specific model was touched
    - Implement `onSingleTapUp`: if tap hits placed model → show info panel; if tap hits surface with model loaded and canPlace → place model
    - Implement `onLongPress`: raycast to find tapped model → enter repositioning mode with visual indicator (outline/glow)
    - Implement `ACTION_MOVE` during repositioning: hit-test along finger position, move model in real time along surfaces
    - Implement `ACTION_UP` during repositioning: if over valid surface → finishRepositioning with new anchor; if no surface → cancelRepositioning (snap-back to original position)
    - Show model at reduced opacity when dragged over area with no detected surface
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 4.1, 4.2_

  - [ ]* 9.2 Write property test for model scaling accuracy
    - **Property 1: Model Scaling Accuracy**
    - **Validates: Requirements 3.2**

- [x] 10. Android: device compatibility check and placement limits
  - [x] 10.1 Create `ArCompatibilityChecker` and integrate with product screen
    - Create `app/src/main/java/com/example/ferosa_landscaping/util/ArCompatibilityChecker.kt`
    - Use `ArCoreApk.checkAvailability()` to determine device support
    - Hide "View in AR" button if ARCore not supported or product has no PlantModel
    - Prompt user to install ARCore Services if supported but not installed
    - Resolve check within 3 seconds, hide button until check completes
    - _Requirements: 10.1, 10.2, 10.3, 10.4_

  - [ ]* 10.2 Write property test for AR-enabled product filter
    - **Property 5: AR-Enabled Product Filter**
    - **Validates: Requirements 7.2**

- [x] 11. Checkpoint - Full feature integration
  - Ensure all tests pass, ask the user if questions arise.

- [x] 12. Integration wiring and polish
  - [x] 12.1 Wire end-to-end: AR launch → API fetch → cache → place → cart
    - Ensure ArActivity launch flow: compatibility check → permission → session init → product fetch → catalog display → model selection → download/cache → reticle → tap to place
    - Ensure add-to-cart flow: tap placed model → info panel → add to cart button → API call → confirmation
    - Ensure offline flow: no network → show cached products only → offline banner
    - Ensure session cleanup: back/close → release ARCore session, camera, anchors within 2 seconds; force-release after 5 seconds
    - Wire retry logic with exponential backoff (1s, 2s, 4s) for network failures, max 3 attempts
    - _Requirements: 1.1, 1.4, 1.5, 1.6, 2.1, 2.2, 2.3, 2.4, 2.5, 3.1, 3.3, 3.4, 3.5, 3.6, 4.6, 8.1, 8.2, 8.3, 8.4, 8.5, 11.2, 11.5, 12.4_

  - [ ]* 12.2 Write integration tests for end-to-end flows
    - Test model download → cache → reload cycle
    - Test add-to-cart request succeeds with valid auth
    - Test offline mode displays cached-only products
    - _Requirements: 11.3, 11.7, 12.2, 12.5_

- [x] 13. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- Laravel backend must be completed before Android networking tasks can be tested against real endpoints
- The existing `ArActivity.kt` structure (permission handling, lifecycle, screenshot) is preserved and refactored incrementally
- All new Android files go under `com.example.ferosa_landscaping` package in appropriate sub-packages (`data/api`, `data/cache`, `data/repository`, `ui/ar`, `util`)

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "4.1"] },
    { "id": 1, "tasks": ["1.2", "1.3", "4.2"] },
    { "id": 2, "tasks": ["1.4", "2.1", "4.3", "5.1"] },
    { "id": 3, "tasks": ["2.2", "2.3", "5.2", "5.3", "5.4", "6.1"] },
    { "id": 4, "tasks": ["6.2"] },
    { "id": 5, "tasks": ["6.3", "6.4", "6.5", "8.1"] },
    { "id": 6, "tasks": ["8.2", "8.4", "9.1", "10.1"] },
    { "id": 7, "tasks": ["8.3", "8.5", "9.2", "10.2"] },
    { "id": 8, "tasks": ["12.1"] },
    { "id": 9, "tasks": ["12.2"] }
  ]
}
```
