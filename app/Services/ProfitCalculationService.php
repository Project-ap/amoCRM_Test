<?php

namespace App\Services;

use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Collections\Leads\LeadsCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Exceptions\AmoCRMMissedTokenException;
use AmoCRM\Exceptions\AmoCRMoAuthApiException;
use AmoCRM\Models\LeadModel;
use App\DTO\AmoWebhookDto;
use App\Exceptions\NotAmoAccountIdException;
use Illuminate\Support\Facades\Log;

class ProfitCalculationService
{
    protected AmoServices $amoServices;
    public function __construct(AmoServices $amoServices)
    {
        $this->amoServices = $amoServices;
        $this->amoServices->setAccountId(31667822);
    }

    /**
     * Создание сделки с заполненным полем Бюджет и пустым полем Себестоимость
     *
     * @throws AmoCRMApiException
     * @throws AmoCRMMissedTokenException
     * @throws NotAmoAccountIdException
     * @throws AmoCRMoAuthApiException
     */
    public function createLeadWithBudgetOnly($price): void
    {
        $newLead = new LeadModel();
        $newLead->setPrice($price);
        $this->amoServices->getAmoClient()->leads()->addOne($newLead);
    }

    /**
     * Обновление поля Бюджет у существующей сделки с заполненным полем себестоимость
     *
     * @throws AmoCRMApiException
     * @throws AmoCRMMissedTokenException
     * @throws NotAmoAccountIdException
     * @throws AmoCRMoAuthApiException
     */
    public function updateDealBudgetWithCostSet($leadId, $price, $cost): void
    {
        $apiClient = $this->amoServices->getAmoClient();
        $lead = $apiClient->leads()->getOne($leadId);
        $lead->setPrice($price);
        $leadCustomFieldsValues = new CustomFieldsValuesCollection();
        $leadCustomFieldsValues->add($this->amoServices->setNumericCfv(283371, $cost));
        $lead->setCustomFieldsValues($leadCustomFieldsValues);
        $apiClient->leads()->updateOne($lead);
    }

    /**
     * Обновление поля себестоимость в значение превышающее бюджет у сделки с заполненным полем себестоимость
     *
     * @throws AmoCRMApiException
     * @throws AmoCRMMissedTokenException
     * @throws NotAmoAccountIdException
     * @throws AmoCRMoAuthApiException
     */
    public function exceedBudgetByUpdatingCost($leadId): void
    {
        $costFieldId = 283371;
        $apiClient = $this->amoServices->getAmoClient();
        $lead = $apiClient->leads()->getOne($leadId);
        $price = $lead->getPrice();
        $cost = $this->amoServices->getValueByCfvAndFieldId($lead->getCustomFieldsValues(), $costFieldId);

        if (!is_null($cost) && $cost >= $price) {
            $diff = $cost - $price;
            $leadCustomFieldsValues = new CustomFieldsValuesCollection();
            $leadCustomFieldsValues->add($this->amoServices->setNumericCfv($costFieldId, $diff));
            $lead->setCustomFieldsValues($leadCustomFieldsValues);
            $apiClient->leads()->updateOne($lead);
        }
    }

    /**
     * Импорт 50 сделок из файла со случайными значениями бюджета и себестоимости (в том числе и пустыми)
     *
     * @throws AmoCRMApiException
     * @throws AmoCRMMissedTokenException
     * @throws NotAmoAccountIdException
     * @throws AmoCRMoAuthApiException
     */
    public function importLeadsWithRandomFinancials(array $data): void
    {
        $costFieldId = 283371;
        $leadsCollection = new LeadsCollection();
        foreach ($data as $leadData) {
            $lead = new LeadModel();
            if (!empty($leadData['name'])) {
                $lead->setName($leadData['name']);
            }
            $lead->setPrice($leadData['budget'] ?? null);
            $leadCustomFieldsValues = new CustomFieldsValuesCollection();
            $leadCustomFieldsValues->add($this->amoServices->setNumericCfv($costFieldId, $leadData['cost'] ?? null));
            $lead->setCustomFieldsValues($leadCustomFieldsValues);
            $leadsCollection->add($lead);
        }
        $this->amoServices->getAmoClient()->leads()->add($leadsCollection);
    }

    /**
     * Необходимо реализовать интеграцию, которая при создании или любом изменении сделки будет рассчитывать прибыль по формуле:
     *
     * «Бюджет сделки» - «Себестоимость» = «Прибыль».
     *
     * @throws AmoCRMApiException
     * @throws AmoCRMMissedTokenException
     * @throws NotAmoAccountIdException
     * @throws AmoCRMoAuthApiException
     */
    public function handleWebhook(AmoWebhookDto $amoWebhookDto): void
    {
        $profit = (float) ($amoWebhookDto->budget - $amoWebhookDto->cost);
        $lead = $this->amoServices->getAmoClient()->leads()->getOne($amoWebhookDto->leadId);
        if (empty($lead)) {
            return;
        }
        $leadProfitValue = (float) $this->amoServices->getValueByCfvAndFieldId($lead->getCustomFieldsValues(), $amoWebhookDto->profitFieldId);
        Log::info(var_export([
            '$amoWebhookDto->cost' => $amoWebhookDto->cost,
            '$amoWebhookDto->budget' => $amoWebhookDto->budget,
            '$profit' => $profit,
            '$leadProfitValue' => $leadProfitValue,
        ], true));
        if (true || $profit === $leadProfitValue) {
            return;
        }
        $leadCustomFieldsValues = new CustomFieldsValuesCollection();
        $leadCustomFieldsValues->add($this->amoServices->setNumericCfv($amoWebhookDto->profitFieldId, $profit));
        $lead->setCustomFieldsValues($leadCustomFieldsValues);
        $this->amoServices->getAmoClient()->leads()->updateOne($lead);
    }
}
