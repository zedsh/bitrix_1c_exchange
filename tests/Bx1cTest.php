<?php

use Bitrix\Sale\Order;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    protected $stores = [
        'one' => ['id' => '78ee7232-ce4b-11e7-8e19-005056c00008', 'id2' => '021231'],
        'two' => ['id' => '52392d82-30ae-11e7-b34d-001e101f4da1', 'id2' => '02asdfsadfasdf9']
    ];
    protected $base_uri = 'http://site.root';
    protected $login = 'admin';
    protected $password = '******';

    protected function flush()
    {
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
    }

    public function testExchangeStore1()
    {

        \Classes\BitrixHelper::init();
        Bitrix\Main\Loader::includeModule('sale');
        $rsOrders = Order::getList(
            [
                'filter' => [
                    '!EXTERNAL_ORDER' => 'Y'
                ]
            ]
        );


        foreach ($rsOrders->fetchAll() as $order) {
            \Bitrix\Sale\Internals\OrderTable::update($order['ID'], [
                'UPDATED_1C' => 'N',
                'DATE_UPDATE' => new \Bitrix\Main\Type\DateTime("21.01.2020 15:30:10")
            ]);
        }

        \Bitrix\Sale\Exchange\Internals\ExchangeLogTable::deleteAll();

        $this->flush();

        $params = [
            'store' => $this->stores['one']['id'],
            'IB_ID' => $this->stores['one']['id2']
        ];

        $client1 = new \Classes\BitrixSaleExchange($this->base_uri, $this->login, $this->password,$params);
        $client1->auth();
        $client1->init();
        $client1->query();
        $this->assertTrue($client1->success());
        print_r($client1->getSaleIds());

    }

}
