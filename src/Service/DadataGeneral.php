<?php

declare(strict_types=1);

namespace Velhron\DadataBundle\Service;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Velhron\DadataBundle\Exception\DadataException;
use Velhron\DadataBundle\Model\Request\AbstractRequest;
use Velhron\DadataBundle\Model\Request\General\BalanceRequest;
use Velhron\DadataBundle\Model\Request\General\StatRequest;
use Velhron\DadataBundle\Model\Request\General\VersionRequest;
use Velhron\DadataBundle\Model\Response\General\StatResponse;

class DadataGeneral extends AbstractService
{
    /**
     * Возвращает текущий баланс счета.
     *
     * Возвращает сумму в рублях с точностью до копеек, десятичный разделитель — точка.
     *
     * @throws DadataException
     */
    public function balance(): float
    {
        $responseData = $this->query(new BalanceRequest());

        return $responseData['balance'] ?? 0.0;
    }

    /**
     * Возвращает агрегированную статистику за конкретный день по каждому из сервисов: стандартизация, подсказки, поиск дублей.
     *
     * Дата должна быть задана в формате YYYY-MM-DD. По умолчанию, сегодня.
     *
     * @param string $date - дата в формате YYYY-MM-DD
     *
     * @throws DadataException
     */
    public function stat(string $date = ''): StatResponse
    {
        $request = new StatRequest();
        $request->date = $date;
        $responseData = $this->query($request);

        return new StatResponse($responseData);
    }

    /**
     * Возвращает даты актуальности справочников (ФИАС, ЕГРЮЛ, банки и другие).
     *
     * @throws DadataException
     */
    public function version(): array
    {
        return $this->query(new VersionRequest());
    }

    /**
     * {@inheritdoc}
     */
    protected function query(AbstractRequest $request): array
    {
        try {
            $response = $this->httpClient->request($request->getMethod(), $request->getUrl(), [
                'headers' => [
                    'Authorization' => "Token {$this->token}",
                    'X-Secret' => $this->secret,
                ],
                'query' => $request->getBody(),
            ]);

            return json_decode($response->getContent(), true) ?? [];
        } catch (ExceptionInterface $exception) {
            throw new DadataException($exception);
        }
    }
}
