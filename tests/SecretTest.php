<?php

require_once __DIR__.'/DaprTests.php';

/**
 * Class SecretTest
 * @covers \Dapr\Secret
 */
class SecretTest extends DaprTests
{
    public function testRetrieveSecret()
    {
        $this->get_client()->register_get(
            '/secrets/store/test',
            200,
            [
                'secret' => 'my_secret',
            ]
        );
        $secret = \Dapr\Secret::retrieve('store', 'test');
        $this->assertSame(['secret' => 'my_secret'], $secret);
    }

    public function testSecretNoExist()
    {
        $this->get_client()->register_get('/secrets/store/test', 204, null);
        $secret = \Dapr\Secret::retrieve('store', 'test');
        $this->assertSame(null, $secret);
    }
}
