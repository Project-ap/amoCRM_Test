<?php

namespace App\Services;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Exceptions\AmoCRMMissedTokenException;
use AmoCRM\Exceptions\AmoCRMoAuthApiException;
use AmoCRM\Models\CustomFieldsValues\NumericCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\NumericCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\NumericCustomFieldValueModel;
use App\Exceptions\NotAmoAccountIdException;
use App\Models\AmoAuth;
use Carbon\Carbon;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;

class AmoServices
{
    protected AmoCRMApiClient $amoClient;
    private int $accountId;
    private AccessTokenInterface $accessToken;

    public function __construct(AmoCRMApiClient $amoClient)
    {
        $this->amoClient = $amoClient;
    }

    public function setAccountId(int $id): static
    {
        $this->accountId = $id;
        return $this;
    }

    /**
     * @return int
     * @throws NotAmoAccountIdException
     */
    public function getAccountId(): int
    {
        if (empty($this->accountId)) {
            throw new NotAmoAccountIdException('Не указан accountId');
        }
        return $this->accountId;
    }

    /**
     * @throws AmoCRMoAuthApiException
     * @throws AmoCRMApiException
     * @throws AmoCRMMissedTokenException
     */
    public function auth($referer, $code): void
    {
        $this->amoClient->setAccountBaseDomain($referer);
        /** @var AccessToken $token */
        $token = $this->amoClient->getOAuthClient()->getAccessTokenByCode($code);
        $this->amoClient->setAccessToken($token);
        $accountId = $this->amoClient->account()->getCurrent()->getId();
        AmoAuth::query()->updateOrCreate([
            'account_id' => $accountId
        ], [
            'access_token' => $token->getToken(),
            'refresh_token' => $token->getRefreshToken(),
            'expires' => Carbon::createFromTimestamp($token->getExpires())->toDateTimeString(),
            'base_domain' => $this->amoClient->getAccountBaseDomain(),
        ]);
    }

    /**
     * @return AmoCRMApiClient
     * @throws NotAmoAccountIdException
     */
    public function getAmoClient(): AmoCRMApiClient
    {
        $accountId = $this->getAccountId();
        if (empty($this->accessToken)) {
            /** @var AmoAuth $amoAuth */
            $amoAuth = AmoAuth::query()->find($accountId);
            $this->amoClient->setAccountBaseDomain($amoAuth->base_domain);
            $this->accessToken = new AccessToken([
                'baseDomain' => $amoAuth->base_domain,
                'refresh_token' => $amoAuth->refresh_token,
                'access_token' => $amoAuth->access_token,
                'expires' => Carbon::createFromTimeString($amoAuth->expires)->getTimestamp(),
            ]);
            $this->amoClient->setAccessToken($this->accessToken);
            $this->amoClient->getOAuthClient()->setAccessTokenRefreshCallback(
                function (AccessTokenInterface $accessToken, string $baseAccountDomain) use ($accountId) {
                    AmoAuth::query()->updateOrCreate([
                        'account_id' => $accountId
                    ], [
                        'access_token' => $accessToken->getToken(),
                        'refresh_token' => $accessToken->getRefreshToken(),
                        'expires' => Carbon::createFromTimestamp($accessToken->getExpires())->toDateTimeString(),
                        'base_domain' => $baseAccountDomain,
                    ]);
                });
        }
        return $this->amoClient;
    }

    /**
     * @param int $id
     * @param int $value
     * @return NumericCustomFieldValuesModel
     */
    public function setNumericCfv(int $id, int $value): NumericCustomFieldValuesModel
    {
        $numericCustomFieldValueModel = new NumericCustomFieldValuesModel();
        $numericCustomFieldValueModel->setFieldId($id);
        $numericCustomFieldValueModel->setValues(
            (new NumericCustomFieldValueCollection())
                ->add((new NumericCustomFieldValueModel())->setValue($value))
        );
        return $numericCustomFieldValueModel;
    }

    /**
     * @param CustomFieldsValuesCollection $customFieldsValuesCollection
     * @param int $fieldId
     * @return object|array|bool|int|string|null
     */
    public function getValueByCfvAndFieldId(CustomFieldsValuesCollection $customFieldsValuesCollection, int $fieldId): object|array|bool|int|string|null
    {

        $costCustomFieldValuesModel = $customFieldsValuesCollection?->getBy('fieldId', $fieldId);
        return $costCustomFieldValuesModel?->getValues()?->first()?->getValue();
    }

}
