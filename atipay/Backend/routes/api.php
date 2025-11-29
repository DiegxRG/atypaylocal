<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Promotions\PromotionController;
use App\Http\Controllers\Api\AuthUsers\AuthUserController;
use App\Http\Controllers\Api\AtipayTransfers\AtipayTransferController;
use App\Http\Controllers\Api\Withdrawals\WithdrawalController;
use App\Http\Controllers\Api\Products\ProductController;
use App\Http\Controllers\Api\Commissions\CommissionSettingController;
use App\Http\Controllers\Api\Commissions\CommissionWithdrawController;
use App\Http\Controllers\Api\Referrals\ReferralController;
use App\Http\Controllers\Api\Commissions\CommissionSummaryController;
use App\Http\Controllers\Api\Commissions\CommissionHistoryController;
use App\Http\Controllers\Api\Investments\InvestmentController;
use App\Http\Controllers\Api\Investments\InvestmentWithdrawalController;
use App\Http\Controllers\Api\Purchases\PurchaseRequestController;
use App\Http\Controllers\Api\AtipayRecharges\PaymentMethodController;
use App\Http\Controllers\Api\AtipayRecharges\UserPaymentMethodController;
use App\Http\Controllers\Api\AtipayRecharges\AtipayRechargeController;
use App\Http\Controllers\Api\Reward\RewardController;
use App\Http\Controllers\Api\Qualification\QualificationController; // Nuevo Controlador
use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\IsUserAuth;
use App\Http\Controllers\Api\Closing\MonthlyClosingController;


// Rutas públicas
Route::post('register', [AuthUserController::class, 'registerUser']);
Route::post('login', [AuthUserController::class, 'loginUser']);

Route::middleware(IsUserAuth::class)->group(function () {
    Route::put('/parametry/state', [AuthUserController::class, 'updateState']);
    Route::get('/parametry/getState', [AuthUserController::class, 'getState']);
    Route::get('/parametry/users/quantity', [AuthUserController::class, 'getUserQuantity']);
    Route::put('/parametry/users/quantity', [AuthUserController::class, 'updateUserQuantity']);
    Route::get('/investment-withdrawals/user', [InvestmentWithdrawalController::class, 'getByUser']);
    
    // Authenticated User
    Route::controller(AuthUserController::class)->group(function () {
        Route::post('refresh-token', 'refreshToken');
        Route::post('logout', 'logout');
        Route::get('user', 'getUser');
    });

    // Edit Partner Profile (Auth)
    Route::put('partner/profile', [AuthUserController::class, 'updateOwnProfile']);
    
    // Buscar socio con su username, cell_phone, y codigo de referencia
    Route::get('partners/find/{identifier}', [AuthUserController::class, 'findUser']);

    // Ver si califique para ser un socio activo
    Route::get('purchase-requests', [PurchaseRequestController::class, 'index']);

    // Qualification Status (Auth)
    Route::get('user/qualification-status', [QualificationController::class, 'checkMyStatus']);
    Route::get('/my-monthly-status', [MonthlyClosingController::class, 'myStatus']);
    Route::get('/my-points-history', [MonthlyClosingController::class, 'getHistory']);
    


    // Atipay Transfers (Auth)
    Route::get('atipay-transfers/sent', [AtipayTransferController::class, 'sent']);  
    Route::get('atipay-transfers/received', [AtipayTransferController::class, 'received']); 
    Route::post('atipay-transfers', [AtipayTransferController::class, 'store']);
    Route::post('atipay-transfers/{id}/approve', [AtipayTransferController::class, 'approve']);
    Route::post('atipay-transfers/{id}/reject', [AtipayTransferController::class, 'reject']);
    Route::get('atipay-transfers/{id}', [AtipayTransferController::class, 'show']);

    // Promotions (Auth)
    Route::get('promotions', [PromotionController::class, 'index']);
    Route::get('promotions/{id}', [PromotionController::class, 'show']);

    // Withdrawals (Auth)
    Route::post('withdrawals', [WithdrawalController::class, 'store']);
    Route::get('withdrawals/my', [WithdrawalController::class, 'myWithdrawals']);

    // Atipay Recharges (Auth)
    Route::post('atipay-recharges', [AtipayRechargeController::class, 'store']);
    Route::get('atipay-recharges/my', [AtipayRechargeController::class, 'myRecharges']);
    
    // Ver todos los métodos de pago disponibles
    Route::get('payment-methods', [PaymentMethodController::class, 'index']);

    // Ver métodos de pago configurados por el usuario autenticado
    Route::get('user/payment-methods', [UserPaymentMethodController::class, 'index']);

    // Products (Auth)
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/my-purchase-requests', [ProductController::class, 'myPurchaseRequests']);
    Route::post('products/purchase', [ProductController::class, 'purchase']);

    // Reward (Auth)
    Route::get('/rewards', [RewardController::class, 'index']);
    Route::get('/rewards/my-requests', [RewardController::class, 'myRequests']);
    Route::get('/rewards/{id}', [RewardController::class, 'show']);
    Route::post('/rewards/{id}/request', [RewardController::class, 'requestRedeem']);

    // Canjear Recomenzas (Auth)
    Route::post('/rewards/{id}/redeem', [RewardController::class, 'redeem']);

    // Ver Red de afiliados propios (Auth)
    Route::get('referrals/my-network-count', [ReferralController::class, 'myReferralLevelsCount']);
    Route::get('referrals/my-network', [ReferralController::class, 'myReferralNetwork']);
    // Ver Ganancias por referidos (Auth)
    Route::get('/admin/referrals/network/{userId}', [ReferralController::class, 'referralNetworkForUser']);
    //Buscar usuarios (Admin)
    Route::get('/admin/referrals/search', [ReferralController::class, 'searchUsers']);

    
    // Inversiones (Auth)
    Route::get('investments', [InvestmentController::class, 'index']);
    Route::post('investments', [InvestmentController::class, 'store']);
    Route::get('investments/{id}/daily-gains', [InvestmentController::class, 'dailyGains']);
    Route::get('investments/{id}/monthly-gains', [InvestmentController::class, 'monthlyGains']);
    Route::get('investments/active', [InvestmentController::class, 'active']);
    Route::post('investments/{id}/withdraw', [InvestmentController::class, 'withdrawEarnings']);

    // Commissions Settings (Auth)
    Route::get('commissions/settings', [CommissionSettingController::class, 'index']);
    Route::post('commissions/withdraw', [CommissionWithdrawController::class, 'withdraw']);
    Route::get('commissions/history/unwithdrawn', [CommissionHistoryController::class, 'unwithdrawnHistory']);
    Route::get('commissions/network/summary', [CommissionSummaryController::class, 'myNetworkCommissions']);
    Route::get('commissions/withdrawals/history', [CommissionWithdrawController::class, 'history']);

    // Admin-only routes
    Route::middleware(IsAdmin::class)->group(function () {

        // Listar Users (Admin)
        Route::get('users', [AuthUserController::class, 'index']);

        // Edit Admin Profile (Admin)
        Route::put('admin/profile', [AuthUserController::class, 'updateOwnAdminProfile']);
        
        // Edit Partner Profile por (Admin)
        Route::put('admin/profile/partners/{id}', [AuthUserController::class, 'updatePartner']);
        
        Route::patch('admin/users/{id}/deactivate', [AuthUserController::class, 'deactivate']);
        Route::patch('admin/users/{id}/reactivate', [AuthUserController::class, 'reactivate']);

        // Promotions (admin)
        Route::post('promotions', [PromotionController::class, 'store']);
        Route::put('promotions/{id}', [PromotionController::class, 'update']);
        Route::delete('promotions/{id}', [PromotionController::class, 'destroy']);

        // Withdrawals (admin)
        Route::get('withdrawals', [WithdrawalController::class, 'index']);
        Route::get('withdrawals/{id}', [WithdrawalController::class, 'show']);
        Route::post('withdrawals/{id}/approve', [WithdrawalController::class, 'approve']);
        Route::post('withdrawals/{id}/reject', [WithdrawalController::class, 'reject']);

        // Atipay Recharges (admin)
        Route::get('atipay-recharges', [AtipayRechargeController::class, 'index']);
        Route::get('atipay-recharges/{id}', [AtipayRechargeController::class, 'show']);
        Route::post('atipay-recharges/{id}/approve', [AtipayRechargeController::class, 'approve']);
        Route::post('atipay-recharges/{id}/reject', [AtipayRechargeController::class, 'reject']);

        // Métodos de pago disponibles (admin)
        Route::post('payment-methods', [PaymentMethodController::class, 'store']);
        Route::put('payment-methods/{id}', [PaymentMethodController::class, 'update']);
        Route::delete('payment-methods/{id}', [PaymentMethodController::class, 'destroy']);

        // Métodos de pago del usuario (admin)
        Route::post('user/payment-methods', [UserPaymentMethodController::class, 'store']);
        Route::put('user/payment-methods/{id}', [UserPaymentMethodController::class, 'update']);
        Route::delete('user/payment-methods/{id}', [UserPaymentMethodController::class, 'destroy']);
    
        // Products (admin)
        Route::post('products', [ProductController::class, 'store']);
        Route::put('products/{id}', [ProductController::class, 'update']);
        Route::delete('products/{id}', [ProductController::class, 'destroy']);
        Route::get('products/purchase-requests', [ProductController::class, 'allPurchaseRequests']);
        Route::post('products/purchase-requests/{id}/approve', [ProductController::class, 'approvePurchase']);
        Route::post('products/purchase-requests/{id}/reject', [ProductController::class, 'rejectPurchase']);

        // Reward (admin)
        Route::post('/rewards', [RewardController::class, 'store']); 
        Route::put('/rewards/{id}', [RewardController::class, 'update']);
        Route::delete('/rewards/{id}', [RewardController::class, 'destroy']);
        Route::get('/reward-requests', [RewardController::class, 'requests']);
        Route::post('/reward-requests/{id}/approve', [RewardController::class, 'approveRequest']);
        Route::post('/reward-requests/{id}/reject', [RewardController::class, 'rejectRequest']);

        // Commissions Settings (admin)
        Route::post('commissions/settings', [CommissionSettingController::class, 'updateOrCreate']);
        Route::delete('commissions/settings/{level}', [CommissionSettingController::class, 'destroy']);

        // Qualification Settings (admin)
        Route::get('admin/qualification/settings', [QualificationController::class, 'getSettings']);
        Route::post('admin/qualification/update', [QualificationController::class, 'updateMinPoints']);
        Route::post('admin/force-closing', [MonthlyClosingController::class, 'forceClosing']);

        // Inversiones (admin)
        Route::get('investments/pending', [InvestmentController::class, 'pending']);
        Route::post('investments/{id}/approve', [InvestmentController::class, 'approve']);
        Route::post('investments/{id}/reject', [InvestmentController::class, 'reject']);
        
        // Resumen de inversiones activas con retornos diarios
        Route::get('admin/investments/active-summary', [InvestmentController::class, 'getActiveSummaryForAdmin']);
    });
    
    Route::get('products/{id}', [ProductController::class, 'show']);
});
