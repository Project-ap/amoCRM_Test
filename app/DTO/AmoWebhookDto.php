<?php

namespace App\DTO;

class AmoWebhookDto
{
    public ?int $accountId;
    public ?int $leadId;
    public ?float $cost;
    public int $costFieldId = 283371;
    public ?int $budget;
    public ?float $profit;
    public int $profitFieldId = 283373;

    public function __construct(array $data)
    {
        $this->accountId = data_get($data, 'account.id');
        $this->leadId = data_get($data, 'leads.update.0.id', data_get($data, 'leads.add.0.id'));
        $this->budget = data_get($data, 'leads.update.0.price', data_get($data, 'leads.add.0.price'));
        $customFields = data_get($data, 'leads.update.0.custom_fields', data_get($data, 'leads.add.0.custom_fields'));
        $customFieldsCollection = collect($customFields);
        $costCustomFields = $customFieldsCollection->where('id', '=', $this->costFieldId)->first();
        $this->cost = data_get($costCustomFields, 'values.0.value');
        $profitCustomFields = $customFieldsCollection->where('id', '=', $this->profitFieldId)->first();
        $this->profit = data_get($profitCustomFields, 'values.0.value');
    }

    public static function make(array $data): AmoWebhookDto
    {
        return new self($data);
    }
}
