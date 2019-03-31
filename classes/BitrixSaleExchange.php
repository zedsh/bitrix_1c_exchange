<?php

namespace Classes;


use GuzzleHttp\Client;
use Classes\Exceptions\FailException;
use Classes\Exceptions\AuthException;


/**
 * Class BitrixSaleExchange
 * @package Classes
 * @property \SimpleXMLElement $xml
 */
class BitrixSaleExchange
{
    protected $client;
    protected $sessid;
    protected $xml;
    protected $params;

    public function __construct($bitrix_uri, $login, $password, $params = [])
    {
        $this->client = new Client([
            'base_uri' => $bitrix_uri,
            'timeout' => 2.0,
            'auth' => [$login, $password],
            'cookies' => true,
//            'debug' => true
        ]);

        $this->params = $params;
    }

    protected function decode($text)
    {
        return iconv('windows-1251', 'UTF-8', $text);
    }

    protected function checkSuccess(\GuzzleHttp\Psr7\Response $response)
    {
        $body = $response->getBody()->getContents();
        if (preg_match('/failure/m',$body)) {
            throw new FailException($this->decode($body));
        }
    }

    public function auth()
    {
        $params = $this->params + ['type' => 'sale', 'mode' => 'checkauth'];
        $response = $this->client->request('GET', 'bitrix/admin/1c_exchange.php', [
            'query' => $params
        ]);

        $step1 = (string)$response->getBody();

        if (!preg_match('/^sessid=(\S+)$/m', $step1, $matches)) {
            throw new AuthException($step1);
        }
        $this->sessid = $matches[1];
    }


    public function init()
    {
        $params = $this->params + ['type' => 'sale', 'mode' => 'init', 'sessid' => $this->sessid];
        $response = $this->client->request('GET',
            'bitrix/admin/1c_exchange.php', [
                'query' => $params
            ]);
        $this->checkSuccess($response);
    }

    public function query($params = [])
    {
        $params = $this->params + ['type' => 'sale', 'mode' => 'query', 'sessid' => $this->sessid];
        $response = $this->client->request('GET', 'bitrix/admin/1c_exchange.php', [
            'query' => $params
        ]);
        $xmlText = $response->getBody()->getContents();
        try {
            $this->xml = new \SimpleXMLElement($xmlText);
        } catch (\Exception $e) {
            throw new FailException($xmlText);
        }

    }

    public function getSaleIds()
    {
        $sales = $this->xml->xpath('/КоммерческаяИнформация/Документ/Ид');
        $saleIds = [];
        foreach ($sales as $sale) {
            $saleIds[] = (string)$sale;
        }

        return $saleIds;
    }

    public function success()
    {
        $params = $this->params + ['type' => 'sale', 'mode' => 'success', 'sessid' => $this->sessid];
        $response = $this->client->request('GET',
            '/bitrix/admin/1c_exchange.php',
            [
                'query' => $params
            ]
        );
        $body = $response->getBody()->getContents();

        if (!preg_match('/success/m', $body)) {
            throw new FailException($body);
        };

        print_r($body);


        return true;
    }
}