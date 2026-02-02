<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers;
use App\Http\Controllers\Auth;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1')->group(function () {
    Route::prefix('admin')->middleware('auth:api')->name('admin.')->group(function () {
        Route::apiResource('users', Admin\UserController::class)->middleware('permit');
        Route::apiResource('permissions', Admin\PermissionController::class, ['only' => ['index', 'show']])
             ->middleware('permit:browse_accesses');
        Route::apiResource('provinces', Admin\ProvinceController::class)->middleware('permit:crud=master_data');
        Route::apiResource('cities', Admin\CityController::class)->middleware('permit:crud=master_data');
        Route::apiResource('subdistricts', Admin\SubdistrictController::class)->middleware('permit:crud=master_data');
        Route::apiResource('villages', Admin\VillageController::class)->middleware('permit:crud=master_data');
        Route::apiResource('regions', Admin\RegionController::class)->middleware('permit:crud=master_data');
        Route::apiResource('institutions', Admin\InstitutionController::class, ['only' => ['index', 'show']])
             ->middleware('permit');
        Route::apiResource('menus', Admin\MenuController::class, ['only' => ['index']])
             ->middleware('permit:browse_accesses');
        Route::post('institutions/{id}', [Admin\InstitutionController::class, 'update'])
             ->name('institutions.update')
             ->middleware('permit');
        Route::put('institutions/{id}/verify', [Admin\InstitutionController::class, 'validation'])
             ->name('institutions.verify')
             ->middleware('permit');
        Route::apiResource('banners', Admin\BannerController::class)->middleware('permit');
        Route::apiResource('news', Admin\NewsController::class)->middleware('permit');
        Route::apiResource('videos', Admin\VideoController::class)->middleware('permit');
        Route::apiResource('galleries', Admin\GalleryController::class)->middleware('permit');
        Route::apiResource('email_templates', Admin\EmailTemplateController::class, ['only' => ['index', 'show', 'update']])
             ->middleware('permit');
        Route::apiResource('testimonies', Admin\TestimonyController::class)->middleware('permit');
        Route::apiResource('logs', Admin\ActivityLogController::class, ['only' => ['index', 'show']])->middleware('permit');
        Route::apiResource('faqs', Admin\FaqController::class)->middleware('permit');
        Route::apiResource('file_downloads', Admin\FileDownloadController::class)->middleware('permit');
        Route::apiResource('public_menus', Admin\PublicMenuController::class)->middleware('permit:crud=contents');
        Route::apiResource('pages', Admin\PageController::class)->middleware('permit:crud=contents');

        // Gallery Albums
        Route::get('gallery-albums', [Admin\GalleryController::class, 'albums'])->name('gallery.album');

        // Roles
        Route::apiResource('roles', Admin\RoleController::class, ['except' => 'show'])->middleware('permit');
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('{role}', [Admin\RoleController::class, 'show'])
                 ->name('show')
                 ->middleware('permit:browse_roles');
            Route::put('{id}/permissions', [Admin\AccessController::class, 'savePermissions'])
                 ->name('permissions.save')
                 ->middleware('permit:edit_accesses');
        });

        // Instrument Categories
        Route::middleware('permit:browse_instrument_categories')->group(function () {
            Route::apiResources([
                'instrument_components' => Admin\InstrumentComponentController::class,
            ], ['only' => 'index']);
        });
        Route::middleware('permit:read_instrument_categories')->group(function () {
            Route::apiResources([
                'instrument_components' => Admin\InstrumentComponentController::class,
            ], ['only' => 'show']);
        });
        Route::middleware('permit:add_instrument_categories')->group(function () {
            Route::apiResources([
                'instrument_components' => Admin\InstrumentComponentController::class,
            ], ['only' => 'store']);
        });
        Route::middleware('permit:edit_instrument_categories')->group(function () {
            Route::apiResources([
                'instrument_components' => Admin\InstrumentComponentController::class,
            ], ['only' => 'update']);
        });
        Route::middleware('permit:delete_instrument_categories')->group(function () {
            Route::apiResources([
                'instrument_components' => Admin\InstrumentComponentController::class,
            ], ['only' => 'destroy']);
        });

        // Instruments
        Route::apiResource('instruments', Admin\InstrumentController::class, ['only' => ['index', 'show']])
             ->middleware('permit');
        Route::prefix('instruments/{instrument}')->name('instruments.')->group(function () {
            Route::prefix('aspects')->name('aspects.')->group(function () {
                Route::post('bulk', [Admin\InstrumentAspectController::class, 'bulkStore'])
                     ->name('bulk_store')
                     ->middleware('permit:add_instruments');
                Route::put('bulk', [Admin\InstrumentAspectController::class, 'bulkUpdate'])
                     ->name('bulk_update')
                     ->middleware('permit:edit_instruments');
                Route::get('{aspect}', [Admin\InstrumentAspectController::class, 'show'])
                     ->name('show')
                     ->middleware('permit:read_instruments');
                Route::delete('{aspect}', [Admin\InstrumentAspectController::class, 'destroy'])
                     ->name('destroy')
                     ->middleware('permit:delete_instruments');
                Route::get('/', [Admin\InstrumentAspectController::class, 'index'])
                     ->name('index')
                     ->middleware('permit:browse_instruments');
            });
        });

        Route::apiResource('accreditations', Admin\AccreditationController::class, ['only' => ['index', 'store']])
             ->middleware('permit');
        Route::prefix('accreditations')->name('accreditations.')->group(function () {
            Route::prefix('{accreditation}')->group(function () {
                Route::get('instruments', [Admin\AccreditationController::class, 'browseInstruments'])
                     ->middleware('permit_any:browse_accreditations,browse_evaluations')
                     ->name('instruments.index');
                Route::get('contents', [Admin\AccreditationController::class, 'browseContents'])
                     ->middleware('permit_any:read_accreditations,browse_evaluations')
                     ->name('contents.index');
                Route::post('verify', [Admin\AccreditationController::class, 'verify'])
                     ->middleware('permit')
                     ->name('verify');
                Route::post('finalize', [Admin\AccreditationController::class, 'finalize'])
                     ->middleware('permit:add_accreditations')
                     ->name('finalize');
                Route::get('evaluation_assignments', [Admin\AccreditationController::class, 'getAssignments'])
                     ->name('evaluation_assignments')
                     ->middleware('permit_any:recap_accreditations,recap_evaluations');
                Route::post('evaluation_assignments', [Admin\AccreditationController::class, 'storeAssignments'])
                     ->name('evaluation_assignments')
                     ->middleware('permit:verify_accreditations');
                Route::post('process', [Admin\AccreditationController::class, 'processMeetingResult'])
                     ->name('process')
                     ->middleware('permit');
                Route::post('accept', [Admin\AccreditationController::class, 'accept'])
                     ->name('accept')
                     ->middleware('permit:add_accreditations');
                Route::get('/', [Admin\AccreditationController::class, 'show'])
                     ->name('show')
                     ->middleware('permit_any:read_accreditations,recap_accreditations,browse_evaluations');
            });
            Route::get('update/status/{id}', [Admin\AccreditationController::class, 'updateStatusViaNotification'])
                ->name('update.status')
                ->middleware('permit_any:read_accreditations');
        });

        Route::prefix('instrument')->name('instrument.')->group(function () {
          Route::prefix('{instrument}')->group(function () {
              Route::get('/', [Admin\InstrumentController::class, 'countInstrument'])
                   ->name('count_instrument');
          });
       });

        Route::apiResource('accreditation_simulations', Admin\AccreditationSimulationController::class, ['only' => ['store']])
             ->middleware('permit:crud=self_assessments');
        Route::prefix('accreditation_simulations')->name('accreditation_simulations.')->group(function () {
            Route::prefix('{accreditation_simulation}')->group(function () {
                Route::post('finalize', [Admin\AccreditationSimulationController::class, 'finalize'])
                     ->middleware('permit:add_self_assessments')
                     ->name('finalize');
            });
        });

        Route::prefix('evaluations')->name('evaluations.')->group(function () {
            Route::prefix('{evaluation}')->group(function () {
                Route::post('document_file', [Admin\EvaluationController::class, 'uploadDocument'])
                     ->middleware('permit:input_evaluations')
                     ->name('upload_document');
                Route::get('download_document', [Admin\EvaluationController::class, 'downloadDocument'])
                     ->middleware('permit:browse_evaluations')
                     ->name('download_document');
                Route::get('/', [Admin\EvaluationController::class, 'show'])
                     ->middleware('permit:browse_evaluations')
                     ->name('show');
               Route::get('show_institution', [Admin\EvaluationController::class, 'showInstitution'])
                     ->middleware('permit:browse_evaluations')
                     ->name('show_institution');
            });
            Route::post('/', [Admin\EvaluationController::class, 'store'])
                 ->name('store')
                 ->middleware('permit:input_evaluations');
            Route::get('/', [Admin\EvaluationController::class, 'index'])
                 ->name('index')
                 ->middleware('permit');
        });

        Route::apiResource('certifications', Admin\CertificationController::class, ['only' => ['index', 'show']]);
        Route::prefix('certifications')->name('certifications.')->group(function () {
          Route::post('/', [Admin\CertificationController::class, 'update'])->name('certification.update');
          Route::get('{id}/download/certificate', [Admin\CertificationController::class, 'downloadCertificate'])->name('download.certificate');
          Route::get('{id}/download/recommendation', [Admin\CertificationController::class, 'downloadRecommendation'])->name('download.recommendation');
        });
        
        Route::prefix('province_region')->name('province_region.')->group(function () {
            Route::get('/region/{region_id}', [Admin\ProvinceRegionController::class, 'index'])
               ->name('available');
            Route::get('/available', [Admin\ProvinceRegionController::class, 'available'])
               ->name('available');
            Route::get('/provinces', [Admin\ProvinceRegionController::class, 'provinceByRegion'])
               ->name('province.by.region');
        });

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('total_accreditations', [Admin\ReportController::class, 'totalAccreditations'])
                 ->name('total_accreditations')
                 ->middleware('permit:browse_reports');
            Route::get('total_accreditations_per_year', [Admin\ReportController::class, 'totalAccreditationsPerYear'])
                 ->name('total_accreditations_per_year')
                 ->middleware('permit:browse_reports');
            Route::get('total_accreditations_by_year', [Admin\ReportController::class, 'totalAccreditationsByYear'])
                 ->name('total_accreditations_by_year')
                 ->middleware('permit:browse_reports');
            Route::get('total_accreditations_by_province_in_year', [Admin\ReportController::class, 'totalAccreditationsByProvinceInYear'])
                 ->name('total_accreditations_by_province_in_year')
                 ->middleware('permit:browse_reports');
            Route::get('total_accreditations_by_province_per_year', [Admin\ReportController::class, 'totalAccreditationsByProvincePerYear'])
                 ->name('total_accreditations_by_province_per_year')
                 ->middleware('permit:browse_reports');
            Route::get('total_accredited_libraries', [Admin\ReportController::class, 'totalAccreditedLibraries'])
                 ->name('total_accredited_libraries')
                 ->middleware('permit:browse_reports');
            Route::get('total_accredited_libraries_within_year', [Admin\ReportController::class, 'totalAccreditedLibrariesWithinYear'])
                 ->name('total_accredited_libraries_within_year')
                 ->middleware('permit:browse_reports');
            Route::get('total_accredited_libraries_per_year', [Admin\ReportController::class, 'totalAccreditedLibrariesPerYear'])
                 ->name('total_accredited_libraries_per_year')
                 ->middleware('permit:browse_reports');
            Route::get('total_accredited_libraries_by_provinces_in_year', [Admin\ReportController::class, 'totalAccreditedLibrariesByProvincesInYear'])
                 ->name('total_accredited_libraries_by_provinces_in_year')
                 ->middleware('permit:browse_reports');
            Route::get('total_accredited_libraries_by_provinces_per_year', [Admin\ReportController::class, 'totalAccreditedLibrariesByProvincePerYear'])
                 ->name('total_accredited_libraries_by_provinces_per_year')
                 ->middleware('permit:browse_reports');
        });

        // Self
        Route::prefix('self')->name('self.')->group(function () {
            Route::get('permissions', [Admin\SelfController::class, 'indexPermissions'])->name('permissions');
            Route::get('menus', [Admin\SelfController::class, 'indexMenus'])->name('menus');
            Route::get('institution', [Admin\SelfController::class, 'showInstitution'])->name('institution');
            Route::post('institution', [Admin\SelfController::class, 'submitInstitution'])->name('institution.submit');
            Route::get('instrument', [Admin\SelfController::class, 'showInstrument'])->name('instrument');
            Route::get('notifications', [Admin\SelfController::class, 'indexNotifications'])->name('notifications');
            Route::get('notifications/all', [Admin\SelfController::class, 'allNotifications'])->name('notifications');
            Route::get('notifications/{notification}', [Admin\SelfController::class, 'readNotification'])->name('notifications.read');
            Route::get('evaluation_assignments', [Admin\SelfController::class, 'indexEvaluationAssignments'])
                 ->middleware('permit:browse_evaluations')
                 ->name('evaluation_assignments');
            Route::get('regions', [Admin\RegionController::class, 'index'])->name('regions');
            Route::get('provinces', [Admin\ProvinceController::class, 'index'])->name('provinces');
            Route::get('cities', [Admin\CityController::class, 'index'])->name('cities');
            Route::get('subdistricts', [Admin\SubdistrictController::class, 'index'])->name('subdistricts');
            Route::get('villages', [Admin\VillageController::class, 'index'])->name('villages');
            Route::get('roles', [Admin\RoleController::class, 'index'])->name('roles');
            Route::get('accreditation_actions', [Admin\AccreditationController::class, 'createActions'])->name('accreditation_actions');
            Route::get('pages/slug_availability/{slug}', [Admin\SelfController::class, 'PageSlugAvailability'])->name('pages.slug_availability');
            Route::get('accreditations/incomplete', [Admin\SelfController::class, 'incompleteAccreditation'])->name('accreditations.incomplete');
            Route::get('dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');
            Route::get('file_downloads', [Admin\FileDownloadController::class, 'index'])->name('file_downloads.index');
            Route::get('/', [Admin\SelfController::class, 'showUser'])->name('user');
            Route::post('/', [Admin\SelfController::class, 'updateProfile'])->name('profile.update');
        });

    });

    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('register', [Auth\RegisterController::class, 'register'])->name('register');
        Route::post('token', [Auth\AccessTokenController::class, 'issueToken'])->name('token');
        Route::post('verification', [Auth\VerificationController::class, 'verify'])->name('verification');
        Route::post('forgot_password', [Auth\ForgotPasswordController::class, 'forgot'])->name('forgot_password');
        Route::post('reset_password', [Auth\ForgotPasswordController::class, 'reset'])->name('reset_password');
    });

    Route::name('public.')->group(function () {
        Route::get('assessor', [Controllers\AssessorController::class ,'index'])->name('assessor.index');
        Route::get('assessor/{id}', [Controllers\AssessorController::class ,'show'])->name('assessor.show');
        Route::get('banners', [Controllers\BannerController::class, 'index'])->name('banners.index');
        Route::get('news', [Controllers\NewsController::class, 'index'])->name('news.index');
        Route::get('news/{id}', [Controllers\NewsController::class, 'show'])->name('news.show');
        Route::get('home/videos', [Controllers\VideoController::class, 'index'])->name('videos.home');
        Route::get('videos', [Controllers\VideoController::class, 'video'])->name('videos.index');
        Route::get('videos/{id}', [Controllers\VideoController::class, 'show'])->name('videos.show');
        Route::get('galleries', [Controllers\GalleryController::class, 'index'])->name('galleries.index');
        Route::get('albums', [Controllers\GalleryController::class, 'album'])->name('albums.index');
        Route::get('gallery/albums/{slug}', [Controllers\GalleryController::class, 'galleryByAlbums'])->name('gallery.albums.index');
        Route::get('testimonies', [Controllers\TestimonyController::class, 'index'])->name('testimonies.index');
        Route::get('faqs', [Controllers\FaqController::class, 'index'])->name('faqs.index');
        Route::get('file_downloads', [Controllers\FileDownloadController::class, 'index'])->name('file_downloads.index');
        Route::get('file_downloads/{file}', [Controllers\FileDownloadController::class, 'show'])->name('file_downloads.show');
        Route::get('provinces', [Admin\ProvinceController::class, 'index'])->name('provinces.index');
        Route::get('cities', [Admin\CityController::class, 'index'])->name('cities.index');
        Route::get('public_menus', [Controllers\PublicMenuController::class, 'index'])->name('public_menus.index');
        Route::get('pages/{page}', [Controllers\PageController::class, 'show'])
             ->where('page', '.*')
             ->name('pages.show');
        Route::get('infographics-mapping', [Controllers\InfographicsController::class, 'infographic'])->name('infographics.mapping');
        Route::get('infographics', [Controllers\InfographicsController::class, 'index'])->name('infographics');
        Route::get('accreditations', [Controllers\AccreditationController::class, 'browseAccredited'])->name('accreditations.index');
        Route::get('accreditations/total_by_category', [Controllers\AccreditationController::class, 'totalByCategory'])->name('accreditations.total_by_category');
        Route::apiResource('certifications', Admin\CertificationController::class, ['only' => ['index', 'show']]);

          // Route::prefix('certifications')->name('certifications.')->group(function () {
          //      Route::post('/', [Admin\CertificationController::class, 'update'])->name('certification.update');
          //      Route::get('{id}/download/certificate', [Admin\CertificationController::class, 'downloadCertificate'])->name('download.certificate');
          //      Route::get('{id}/download/recommendation', [Admin\CertificationController::class, 'downloadRecommendation'])->name('download.recommendation');
          // });
     });

    Route::prefix('storage_files')->name('storage.')->group(function () {
        Route::get('accreditations/{path}', [Controllers\StorageController::class, 'showAccreditationFile'])
             ->where('path', '.*')
             ->middleware('auth:api')
             ->name('accreditation');
        Route::get('evaluations/{path}', [Controllers\StorageController::class, 'showEvaluationFile'])
             ->where('path', '.*')
             ->middleware('auth:api')
             ->name('evaluation');
        Route::get('{path}', [Controllers\StorageController::class, 'showFile'])
             ->where('path', '.*')
             ->name('file');
    });
});
