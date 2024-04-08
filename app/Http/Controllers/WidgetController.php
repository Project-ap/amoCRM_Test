<?php

namespace App\Http\Controllers;

use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Exceptions\AmoCRMMissedTokenException;
use AmoCRM\Exceptions\AmoCRMoAuthApiException;
use App\Exceptions\NotAmoAccountIdException;
use App\Jobs\ProcessAmoWebhook;
use App\Services\AmoServices;
use App\Services\ProfitCalculationService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WidgetController extends Controller
{
    /**
     * @throws AmoCRMoAuthApiException
     * @throws AmoCRMApiException
     * @throws AmoCRMMissedTokenException
     */
    public function auth(Request $request, AmoServices $amoServices): \Illuminate\Foundation\Application|\Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        $amoServices->auth($request->get('referer'), $request->get('code'));
        return response('Ok');
    }

    /**
     * @param ProfitCalculationService $profitCalculationService
     * @param int $price
     * @return Application|Response|\Illuminate\Contracts\Foundation\Application|ResponseFactory
     * @throws AmoCRMApiException
     * @throws AmoCRMMissedTokenException
     * @throws AmoCRMoAuthApiException
     * @throws NotAmoAccountIdException
     */
    public function createLeadWithBudgetOnly(ProfitCalculationService $profitCalculationService, int $price): Application|Response|\Illuminate\Contracts\Foundation\Application|ResponseFactory
    {
        $profitCalculationService->createLeadWithBudgetOnly($price);
        return response('Сделка создана с бюджетом ' . $price);
    }

    /**
     * @param ProfitCalculationService $profitCalculationService
     * @param int $leadId
     * @param int $price
     * @param int $cost
     * @return Application|Response|\Illuminate\Contracts\Foundation\Application|ResponseFactory
     * @throws AmoCRMApiException
     * @throws AmoCRMMissedTokenException
     * @throws AmoCRMoAuthApiException
     * @throws NotAmoAccountIdException
     */
    public function updateDealBudgetWithCostSet(ProfitCalculationService $profitCalculationService, int $leadId, int $price, int $cost): Application|Response|\Illuminate\Contracts\Foundation\Application|ResponseFactory
    {
        $profitCalculationService->updateDealBudgetWithCostSet($leadId, $price, $cost);
        return response('Сделка обновлена с бюджетом ' . $price);
    }

    /**
     * @param ProfitCalculationService $profitCalculationService
     * @param int $leadId
     * @return Application|Response|\Illuminate\Contracts\Foundation\Application|ResponseFactory
     * @throws AmoCRMApiException
     * @throws AmoCRMMissedTokenException
     * @throws AmoCRMoAuthApiException
     * @throws NotAmoAccountIdException
     */
    public function exceedBudgetByUpdatingCost(ProfitCalculationService $profitCalculationService, int $leadId): \Illuminate\Foundation\Application|\Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        $profitCalculationService->exceedBudgetByUpdatingCost($leadId);
        return response('Обновление поля себестоимость в значение превышающее бюджет');
    }

    /**
     * @param Request $request
     * @param ProfitCalculationService $profitCalculationService
     * @return JsonResponse
     */
    public function importLeads(Request $request, ProfitCalculationService $profitCalculationService): \Illuminate\Http\JsonResponse
    {
        // Получаем данные из запроса
        $data = $request->json()->all();
        try {
            $profitCalculationService->importLeadsWithRandomFinancials($data);
        } catch (AmoCRMMissedTokenException|NotAmoAccountIdException|AmoCRMApiException $e) {
            return response()->json(['message' => 'Не удалось импортировать сделки в amoCRM.']);
        }

        return response()->json(['message' => 'Все сделки успешно импортированы в amoCRM.']);
    }

    /**
     * @param Request $request
     * @return Application|bool|Response|\Illuminate\Contracts\Foundation\Application|ResponseFactory
     */
    public function webhook(Request $request): Application|bool|Response|\Illuminate\Contracts\Foundation\Application|ResponseFactory
    {
        Log::info(var_export([
            '$request->all()' => $request->all()
        ], true));
        ProcessAmoWebhook::dispatch($request->all())->onQueue('first');

        Log::info(var_export([
            'END $request->all()' => $request->all()
        ], true));

        return true;
    }
}
