<?php

use Illuminate\Support\Facades\Route;
use Modules\Clipboard\Http\Controllers\ClipboardController;

Route::middleware(['auth', 'auth.object:CLIPBOARD_MANAGEMENT'])->group(function () {
    Route::resource('clipboard-items', ClipboardController::class)->parameters([
        'clipboard-items' => 'clipboardItem',
    ])->names('clipboard');
    
    Route::post('clipboard-items/{clipboardItem}/copy', [ClipboardController::class, 'copy'])->name('clipboard.copy');
    Route::post('clipboard-items/quick-save', [ClipboardController::class, 'quickSave'])->name('clipboard.quick-save');
    Route::post('clipboard-items/reorder', [ClipboardController::class, 'reorder'])->name('clipboard.reorder');
    Route::get('clipboard-items/api/recent', [ClipboardController::class, 'recent'])->name('clipboard.recent');
});
