<?php

use Illuminate\Support\Facades\Route;
use Modules\Company\Http\Controllers\CompanyController;

Route::middleware(['auth', 'auth.object:COMPANY_MANAGEMENT'])->group(function () {
    Route::resource('companies', CompanyController::class)->names('company');
    Route::get('api/companies/search', [CompanyController::class, 'search'])->name('company.search');
});
